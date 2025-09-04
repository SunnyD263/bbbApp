<?php
namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class XmlFeedClient
{
    public function __construct(
        private HttpClientInterface $http,
        private CacheInterface $cache,
        private ?LoggerInterface $logger = null,
        private int $maxBytesDefault = 5_000_000, // <- bude přepsáno z .env
    ) {}

    public function fetchSimpleXml(
        string $url,
        ?string $xsdPath = null,
        ?int $maxBytes = null,      // <- volitelné, přepíše default
        int $timeout = 10,
        int $retries = 2,
        int $cacheTtl = 600
    ): \SimpleXMLElement {
        $maxBytes = $maxBytes ?? $this->maxBytesDefault;

        $cacheKey = 'xml_payload_' . md5($url);
        $payload = $this->cache->get($cacheKey, function ($item) use ($url, $timeout, $retries, $maxBytes, $xsdPath, $cacheTtl) {
            $item->expiresAfter($cacheTtl);

            $content = $this->downloadWithRetry($url, $timeout, $retries, $maxBytes);

            // bezpečnost + parse check (zkráceno)
            libxml_use_internal_errors(true);
            if (str_contains($content, '<!DOCTYPE')) {
                throw new \RuntimeException('Doctype není povolen.');
            }

            $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
            if ($xml === false) {
                throw new \RuntimeException('XML parse error.');
            }

            // volitelná XSD validace...
            if ($xsdPath) {
                $dom = new \DOMDocument();
                $dom->loadXML($content, LIBXML_NONET);
                if (!$dom->schemaValidate($xsdPath)) {
                    throw new \RuntimeException('XML neodpovídá XSD.');
                }
            }

            return $content; // ukládáme syrový payload
        });

        // vrátíme SimpleXML z payloadu v cache
        $xml = simplexml_load_string($payload, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
        if ($xml === false) {
            throw new \RuntimeException('XML v cache je poškozené.');
        }
        return $xml;
    }

    private function downloadWithRetry(string $url, int $timeout, int $retries, int $maxBytes): string
    {
        $attempt = 0; $lastEx = null;

        while ($attempt <= $retries) {
            try {
                $response = $this->http->request('GET', $url, [
                    'timeout' => $timeout,
                    'headers' => [
                        'Accept' => 'application/xml, text/xml, */*+xml;q=0.9',
                        'User-Agent' => 'AppXmlClient/1.0',
                    ],
                ]);

                $status = $response->getStatusCode(false);
                if ($status < 200 || $status >= 300) {
                    if (in_array($status, [429, 500, 502, 503, 504], true) && $attempt < $retries) {
                        usleep((200 * (2 ** $attempt)) * 1000);
                        $attempt++; continue;
                    }
                    throw new \RuntimeException("HTTP $status při stahování $url");
                }

                $this->assertXmlContentType($response);

                $buffer = '';
                foreach ($this->http->stream($response) as $chunk) {
                    if ($chunk->isTimeout()) continue;
                    $buffer .= $chunk->getContent();
                    if (strlen($buffer) > $maxBytes) {
                        throw new \RuntimeException("Odpověď přesáhla limit {$maxBytes} bajtů.");
                    }
                }
                return $buffer;

            } catch (\Throwable $e) {
                $lastEx = $e;
                if ($attempt < $retries) {
                    $this->logger?->warning('XML fetch retry', ['url' => $url, 'attempt' => $attempt + 1, 'error' => $e->getMessage()]);
                    usleep((200 * (2 ** $attempt)) * 1000);
                    $attempt++; continue;
                }
                break;
            }
        }
        throw new \RuntimeException('Selhalo stahování XML: ' . ($lastEx?->getMessage() ?? 'neznámá chyba'), 0, $lastEx);
    }

    private function assertXmlContentType(ResponseInterface $response): void
    {
        $ct = strtolower($response->getHeaders(false)['content-type'][0] ?? '');
        if ($ct && !str_contains($ct, 'xml')) {
            throw new \RuntimeException("Neočekávaný Content-Type: {$ct}");
        }
    }
}
