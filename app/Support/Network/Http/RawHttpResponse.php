<?php

declare(strict_types=1);

namespace App\Support\Network\Http;

final class RawHttpResponse
{
    /**
     * @param  array<string, list<string>>  $headers  lower-case header name => values
     */
    public function __construct(
        public readonly int $status,
        public readonly array $headers,
        public readonly string $body,
        public readonly bool $truncated,
        public readonly int $totalTimeMs,
        public readonly int $connectTimeMs,
        public readonly string $primaryIp,
    ) {}

    /** @return list<string> */
    public function headerValues(string $name): array
    {
        return $this->headers[strtolower($name)] ?? [];
    }

    public function header(string $name): ?string
    {
        $values = $this->headerValues($name);

        return $values === [] ? null : $values[0];
    }

    public function location(): ?string
    {
        return $this->header('location');
    }

    public function contentType(): string
    {
        return strtolower((string) $this->header('content-type'));
    }

    public function isHtml(): bool
    {
        $type = $this->contentType();

        return $type === '' || str_starts_with($type, 'text/html') || str_starts_with($type, 'application/xhtml');
    }
}
