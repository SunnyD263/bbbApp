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
        return $this->client->fetchSimpleXml($url);
    }
}
