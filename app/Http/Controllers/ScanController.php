<?php

declare(strict_types=1);

// Copyright (c) 2026 Fedaa Mohamed. All rights reserved.

namespace App\Http\Controllers;

use App\Domain\SiteCheck\Dto\ScanInput;
use App\Domain\SiteCheck\ScanService;
use App\Http\Requests\ScanRequest;
use App\Models\Scan;
use App\Services\ScanOwnership;
use App\Support\Network\UnsafeUrlException;
use App\Support\Network\UrlErrorMessages;
use Illuminate\Http\RedirectResponse;
use Throwable;

final class ScanController extends Controller
{
    public function store(
        ScanRequest $request,
        ScanService $scanner,
        ScanOwnership $ownership,
        UrlErrorMessages $errors,
    ): RedirectResponse {
        return $this->execute($request->scanInput(), $scanner, $ownership, $errors);
    }

    public function rescan(
        Scan $scan,
        ScanService $scanner,
        ScanOwnership $ownership,
        UrlErrorMessages $errors,
    ): RedirectResponse {
        abort_unless($ownership->owns($scan) && ! $scan->isDemo(), 404);

        $selectors = $scan->report['meta']['dkim_selectors'] ?? [];
        $selectors = is_array($selectors) ? array_values(array_filter($selectors, 'is_string')) : [];

        return $this->execute(new ScanInput($scan->input, $selectors), $scanner, $ownership, $errors);
    }

    private function execute(
        ScanInput $input,
        ScanService $scanner,
        ScanOwnership $ownership,
        UrlErrorMessages $errors,
    ): RedirectResponse {
        if (function_exists('set_time_limit')) {
            @set_time_limit((int) config('sitecheck.limits.scan_total_seconds', 60) + 15);
        }

        try {
            $report = $scanner->run($input);
        } catch (UnsafeUrlException $exception) {
            return back()->withInput()->withErrors(['target' => $errors->for($exception->reason)]);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->withErrors(['target' => __('sitecheck.errors.scan_failed')]);
        }

        $scan = Scan::create([
            'owner_token' => $ownership->token(),
            'input' => $input->raw,
            'host' => $report->host,
            'url' => $report->url,
            'overall_score' => $report->overallScore(),
            'grade' => $report->overallGrade(),
            'section_scores' => array_map(
                static fn ($section): int => $section->score(),
                $report->sections,
            ),
            'report' => $report->toArray(),
            'locale' => app()->getLocale(),
            'duration_ms' => $report->durationMs,
            'scanned_at' => $report->scannedAt,
        ]);

        return redirect()
            ->route('scans.show', $scan)
            ->with('status', __('sitecheck.flash.scan_done'));
    }
}
