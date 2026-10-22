<?php

namespace App\Support\Messaging;

final readonly class OutgoingMessage
{
    /** @param  string  $to  international number without "+" (e.g. 201012345678) */
    public function __construct(
        public string $to,
        public string $body,
    ) {}
}
