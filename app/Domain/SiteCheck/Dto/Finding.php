<?php

declare(strict_types=1);

namespace App\Domain\SiteCheck\Dto;

use Illuminate\Contracts\Translation\Translator;

final class Finding
{
    public const PASS = 'pass';

    public const WARN = 'warn';

    public const FAIL = 'fail';

    public const INFO = 'info';

    /**
     * @param  array<string, string|int|float>  $params  placeholders for the translated text
     * @param  array<string, mixed>  $data  raw evidence shown in the report tables
     */
    public function __construct(
        public readonly string $id,
        public readonly string $section,
        public readonly string $severity,
        public readonly int $weight = 1,
        public readonly array $params = [],
        public readonly array $data = [],
    ) {}

    public static function pass(string $id, string $section, int $weight = 1, array $params = [], array $data = []): self
    {
        return new self($id, $section, self::PASS, $weight, $params, $data);
    }

    public static function warn(string $id, string $section, int $weight = 1, array $params = [], array $data = []): self
    {
        return new self($id, $section, self::WARN, $weight, $params, $data);
    }

    public static function fail(string $id, string $section, int $weight = 1, array $params = [], array $data = []): self
    {
        return new self($id, $section, self::FAIL, $weight, $params, $data);
    }

    public static function info(string $id, string $section, array $params = [], array $data = []): self
    {
        return new self($id, $section, self::INFO, 0, $params, $data);
    }

    public function isScored(): bool
    {
        return $this->severity !== self::INFO && $this->weight > 0;
    }

    public function factor(): float
    {
        return match ($this->severity) {
            self::PASS => 1.0,
            self::WARN => 0.5,
            default => 0.0,
        };
    }

    /** @return array<string, mixed> */
    public function toArray(bool $localize = false): array
    {
        $payload = [
            'id' => $this->id,
            'section' => $this->section,
            'severity' => $this->severity,
            'weight' => $this->weight,
            'params' => $this->params,
            'data' => $this->data,
        ];

        if ($localize) {
            $payload['title'] = $this->title();
            $payload['detail'] = $this->detail();
            if ($this->severity !== self::PASS && $this->severity !== self::INFO) {
                $payload['fix'] = $this->fix();
            }
        }

        return $payload;
    }

    public function title(): string
    {
        return $this->translate('title');
    }

    public function detail(): string
    {
        return $this->translate('detail');
    }

    public function fix(): ?string
    {
        $translator = app(Translator::class);
        $base = $this->key('fix');
        $specific = $base.'.'.$this->severity;

        if ($translator->has($specific)) {
            return (string) __($specific, $this->params);
        }

        return $translator->has($base) ? (string) __($base, $this->params) : null;
    }

    private function translate(string $suffix): string
    {
        $translator = app(Translator::class);
        $base = $this->key($suffix);
        $specific = $base.'.'.$this->severity;
        $key = $translator->has($specific) ? $specific : $base;
        $value = __($key, $this->params);

        return is_string($value) ? $value : $key;
    }

    private function key(string $suffix): string
    {
        return 'sitecheck.findings.'.$this->id.'.'.$suffix;
    }
}
