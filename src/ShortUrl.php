<?php

namespace Clickem\UrlShortener;

class ShortUrl
{
    public function __construct(
        public readonly int    $id,
        public readonly string $code,
        public readonly string $short_url,
        public readonly string $original_url,
        public readonly ?string $expires_at,
        public readonly string $created_at,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id:           $data['id'],
            code:         $data['code'],
            short_url:    $data['short_url'],
            original_url: $data['original_url'],
            expires_at:   $data['expires_at'] ?? null,
            created_at:   $data['created_at'],
        );
    }

    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'code'         => $this->code,
            'short_url'    => $this->short_url,
            'original_url' => $this->original_url,
            'expires_at'   => $this->expires_at,
            'created_at'   => $this->created_at,
        ];
    }

    public function __toString(): string
    {
        return $this->short_url;
    }
}
