<?php

namespace App\Support;

use Carbon\CarbonInterface;

final readonly class TimelineEntry
{
    /**
     * @param  string  $type  registered | attachment | visit | prescription | invoice | payment | appointment | lab
     * @param  string  $icon  semantic icon key, mapped by each template
     * @param  string  $color  badge color name
     */
    public function __construct(
        public string $type,
        public CarbonInterface $date,
        public string $title,
        public ?string $subtitle = null,
        public string $icon = 'document',
        public string $color = 'slate',
        public ?string $url = null,
        public ?string $amount = null,
    ) {}
}
