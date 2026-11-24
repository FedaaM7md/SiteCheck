<?php

declare(strict_types=1);

namespace App\Support\Network;

final class UrlErrorMessages
{
    public function for(string $reason): string
    {
        $key = 'sitecheck.errors.url.'.$reason;

        return trans()->has($key)
            ? (string) __($key)
            : (string) __('sitecheck.errors.url.generic');
    }
}
