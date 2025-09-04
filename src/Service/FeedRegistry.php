<?php
namespace App\Service;

use App\Domain\FeedKind;

final class FeedRegistry
{
    /** @var array<string,string> */
    private array $urls = [];

    public function set(string $kind, string $url): void
    {
        $this->urls[$kind] = $url;
    }

    public function url(FeedKind|string $kind): string
    {
        $key = $kind instanceof \UnitEnum ? $kind->value : $kind;
        if (!isset($this->urls[$key])) {
            throw new \InvalidArgumentException("Unknown feed kind: {$key}");
        }
        return $this->urls[$key];
    }
}