<?php
namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class XmlFeedClient
{
    public function __construct(
        private HttpClientInterface $http,
        private CacheInterface $cache,
        private ?LoggerInterface $logger = null,
    ) {}

    /**
     * Stáhne XML s cachingem (ETag/Last-Modified), retry a bezpečně ho vrátí jako SimpleXMLElement.
     *
     * @throws \RuntimeException
     */
    public function fetchSimpleXml(
        string $url,
        ?string $xsdPath = null,
        int $maxBytes = 5_000_000,     // ochrana před „bloat“ odpověďmi
        int $timeout = 10,             // s
        int $retries = 2,              // celkem 1 + 2 pokusy
        int $cacheTtl = 600            // s
    ): \SimpleXMLElement {
        $cacheKey = 'xml_payload_' . md5($url);

        // Cache „payload“ – respektuje Cache-Control/ETag přes CachingHttpClient.
        $payload = $this->cache->get($cacheKey, function ($item) use ($url, $timeout, $retries, $maxBytes, $xsdPath, $cacheTtl) {
            $item->expiresAfter($cacheTtl);
            $content = $this->downloadWithRetry($url, $timeout, $retries, $maxBytes);

            // Bezpečný parse (bez externích entit)
            libxml_use_internal_errors(true);
            if (str_contains($content, '<!DOCTYPE')) {
                throw new \RuntimeException('Doctype není povolen (ochrana proti XXE).');
            }

            $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
            if ($xml === false) {
                $err = array_map(fn($e) => trim($e->message ?? ''), libxml_get_errors());
                throw new \RuntimeException('XML parse error: ' . implode(' | ', array_filter($err)));
            }

            // Volitelná XSD validace (pokud chceš tvrdou validaci schématu)
            if ($xsdPath) {
                $dom = new \DOMDocument();
                if (!$dom->loadXML($content, LIBXML_NONET)) {
                    throw new \RuntimeException('Nelze načíst XML pro XSD validaci.');
                }
                if (!$dom->schemaValidate($xsdPath)) {
                    throw new \RuntimeException('XML neodpovídá XSD schématu.');
                }
            }

            return $content; // do cache ukládáme syrové XML
        });

        // Parse z cache (bez XSD – validace proběhla při naplnění cache)
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($payload, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
        if ($xml === false) {
            throw new \RuntimeException('XML v cache je poškozené.');
        }
        return $xml;
    }

    /**
     * Stáhne obsah s retry + backoff a limitem na počet bajtů, validuje Content-Type.
     */
    private function downloadWithRetry(string $url, int $timeout, int $retries, int $maxBytes): string
    {
        $attempt = 0;
        $lastEx = null;

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
                if ($status === 304) {
                    // CachingHttpClient vrací 304 a zároveň vyřeší návrat z cache,
                    // ale pro jistotu přečteme payload přes getContent(false) – necháme cache vrstvu pracovat.
                    return $response->getContent(false);
                }

                if ($status < 200 || $status >= 300) {
                    if (in_array($status, [429, 500, 502, 503, 504], true) && $attempt < $retries) {
                        $this->sleepBackoff($attempt);
                        $attempt++;
                        continue;
                    }
                    throw new \RuntimeException("HTTP $status při stahování $url");
                }

                $this->assertXmlContentType($response);

                // Streamované čtení s limitem
                $buffer = '';
                foreach ($this->http->stream($response) as $chunk) {
                    if ($chunk->isTimeout()) {
                        // čekáme dál
                        continue;
                    }
                    $buffer .= $chunk->getContent();
                    if (strlen($buffer) > $maxBytes) {
                        throw new \RuntimeException("Odpověď přesáhla limit {$maxBytes} bajtů.");
                    }
                }
                return $buffer;
            } catch (TransportExceptionInterface|\RuntimeException $e) {
                $lastEx = $e;
                if ($attempt < $retries) {
                    $this->logger?->warning('XML fetch retry', ['url' => $url, 'attempt' => $attempt + 1, 'error' => $e->getMessage()]);
                    $this->sleepBackoff($attempt);
                    $attempt++;
                    continue;
                }
                break;
            }
        }
        throw new \RuntimeException('Selhalo stahování XML: ' . ($lastEx?->getMessage() ?? 'neznámá chyba'), 0, $lastEx);
    }

    private function sleepBackoff(int $attempt): void
    {
        // 200ms, 400ms, 800ms...
        usleep((200 * (2 ** $attempt)) * 1000);
    }

    private function assertXmlContentType(ResponseInterface $response): void
    {
        $headers = $response->getHeaders(false);
        $ct = strtolower($headers['content-type'][0] ?? '');
        // akceptujeme „xml“ v hlavním typu i v suffixu (application/rss+xml apod.)
        if ($ct && !str_contains($ct, 'xml')) {
            throw new \RuntimeException("Neočekávaný Content-Type: {$ct}");
        }
    }
}
