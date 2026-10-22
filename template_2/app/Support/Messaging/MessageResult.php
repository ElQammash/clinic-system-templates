<?php

namespace App\Support\Messaging;

final readonly class MessageResult
{
    /**
     * @param  string  $status  prepared (user must open $url) | sent | failed
     */
    public function __construct(
        public string $status,
        public ?string $url = null,
        public ?string $reference = null,
        public ?string $error = null,
    ) {}

    public static function link(string $url): self
    {
        return new self('prepared', url: $url);
    }

    public static function sent(?string $reference = null): self
    {
        return new self('sent', reference: $reference);
    }

    public static function failed(string $error): self
    {
        return new self('failed', error: $error);
    }
}
