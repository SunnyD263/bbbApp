<?php
namespace App\Service;

use App\Domain\FeedKind;

final class FeedRegistry
{
    /** @var array<string,string> */
    private array $urls = [];

    /** @var array<string,string> */
    private array $sources = [];

    public function set(string $kind, string $url, string $source): void
    {
        $this->urls[$kind] = $url;
        $this->sources[$kind] = $source;
    }

    public function url(FeedKind|string $kind): string
    {
        $key = $kind instanceof \UnitEnum ? $kind->value : $kind;

        if (!isset($this->urls[$key])) {
            throw new \InvalidArgumentException("Unknown feed kind: {$key}");
        }

        return $this->urls[$key];
    }

    public function source(FeedKind|string $kind): string
    {
        $key = $kind instanceof \UnitEnum ? $kind->value : $kind;

        if (!isset($this->sources[$key])) {
            throw new \InvalidArgumentException("Unknown feed source: {$key}");
        }

        return $this->sources[$key];
    }
}