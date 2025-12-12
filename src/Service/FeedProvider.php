<?php

namespace App\Service;
use App\Domain\FeedKind;

final class FeedProvider
{
    public function __construct(
        private XmlFeedClient $client,
        private FeedRegistry $registry,
    ) {}

    public function fetch(FeedKind $kind): \SimpleXMLElement
    {
        $url = $this->registry->url($kind);
        $source = $this->registry->source($kind);
        $auth = null;
        if ($source === 'Activa') {
            $auth = [
                $_ENV['FEED_ACTIVA_USER'] ?? null,
                $_ENV['FEED_ACTIVA_PWD'] ?? null,
            ];
        }

        return $this->client->fetchSimpleXml($url, $auth);
    }
}
