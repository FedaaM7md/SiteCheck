<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\SiteCheck\Scoring\ScoreCalculator;
use App\Models\Scan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Scan>
 */
final class ScanFactory extends Factory
{
    protected $model = Scan::class;

    public function definition(): array
    {
        $host = $this->faker->domainName();
        $score = $this->faker->numberBetween(40, 100);

        return [
            'owner_token' => Str::random(32),
            'input' => $host,
            'host' => $host,
            'url' => 'https://'.$host.'/',
            'overall_score' => $score,
            'grade' => ScoreCalculator::grade($score),
            'section_scores' => [
                'dns' => $score,
                'ssl' => $score,
                'email' => $score,
                'http' => $score,
                'seo' => $score,
            ],
            'report' => $this->report($host, $score),
            'locale' => 'ar',
            'duration_ms' => 1200,
            'scanned_at' => now(),
        ];
    }

    public function forHost(string $host, ?int $score = null): static
    {
        return $this->state(function (array $attributes) use ($host, $score): array {
            $score ??= (int) ($attributes['overall_score'] ?? 80);

            return [
                'input' => $host,
                'host' => $host,
                'url' => 'https://'.$host.'/',
                'overall_score' => $score,
                'grade' => ScoreCalculator::grade($score),
                'section_scores' => array_fill_keys(['dns', 'ssl', 'email', 'http', 'seo'], $score),
                'report' => $this->report($host, $score),
            ];
        });
    }

    /** @return array<string, mixed> */
    private function report(string $host, int $score): array
    {
        $sections = [];
        $findingMap = [
            'dns' => ['id' => 'dns_a', 'params' => []],
            'ssl' => ['id' => 'ssl_https', 'params' => []],
            'email' => ['id' => 'email_mx', 'params' => ['count' => 1]],
            'http' => ['id' => 'http_status', 'params' => ['status' => 200]],
            'seo' => ['id' => 'seo_title', 'params' => ['length' => 42]],
        ];

        foreach ($findingMap as $key => $finding) {
            $sections[$key] = [
                'key' => $key,
                'score' => $score,
                'grade' => ScoreCalculator::grade($score),
                'error' => null,
                'findings' => [
                    [
                        'id' => $finding['id'],
                        'section' => $key,
                        'severity' => 'pass',
                        'weight' => 1,
                        'params' => $finding['params'],
                        'data' => [],
                    ],
                ],
                'details' => [],
            ];
        }

        return [
            'schema' => 1,
            'generated_at' => gmdate('c'),
            'duration_ms' => 1200,
            'target' => ['input' => $host, 'host' => $host, 'url' => 'https://'.$host.'/'],
            'overall' => [
                'score' => $score,
                'grade' => ScoreCalculator::grade($score),
                'tone' => ScoreCalculator::tone($score),
            ],
            'sections' => $sections,
            'meta' => ['pinned_ip' => '93.184.216.34', 'resolved_ips' => ['93.184.216.34']],
        ];
    }
}
