<?php

declare(strict_types=1);

// Copyright (c) 2026 Fedaa Mohamed. All rights reserved.

namespace App\Models;

use App\Services\DemoData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Scan extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'owner_token',
        'input',
        'host',
        'url',
        'overall_score',
        'grade',
        'section_scores',
        'report',
        'locale',
        'duration_ms',
        'scanned_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'section_scores' => 'array',
            'report' => 'array',
            'overall_score' => 'integer',
            'duration_ms' => 'integer',
            'scanned_at' => 'datetime',
        ];
    }

    /** @return array<string, int> */
    public function sectionScores(): array
    {
        return is_array($this->section_scores) ? $this->section_scores : [];
    }

    public function scoreTone(): string
    {
        return match (true) {
            $this->overall_score >= 90 => 'excellent',
            $this->overall_score >= 75 => 'good',
            $this->overall_score >= 55 => 'fair',
            default => 'poor',
        };
    }

    public function isDemo(): bool
    {
        return DemoData::isDemoToken($this->owner_token);
    }
}
