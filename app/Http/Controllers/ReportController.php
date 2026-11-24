<?php

declare(strict_types=1);

// Copyright (c) 2026 Fedaa Mohamed. All rights reserved.

namespace App\Http\Controllers;

use App\Http\Presenters\ReportPresenter;
use App\Models\Scan;
use App\Services\ScanOwnership;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class ReportController extends Controller
{
    public function __construct(private readonly ScanOwnership $ownership) {}

    public function show(Scan $scan, ReportPresenter $presenter): View
    {
        $this->authorizeScan($scan);

        $report = is_array($scan->report) ? $scan->report : [];

        return view('scans.show', [
            'scan' => $scan,
            'report' => $presenter->present($report),
        ]);
    }

    public function print(Scan $scan, ReportPresenter $presenter): View
    {
        $this->authorizeScan($scan);

        $report = is_array($scan->report) ? $scan->report : [];

        return view('scans.print', [
            'scan' => $scan,
            'report' => $presenter->present($report),
        ]);
    }

    public function exportJson(Scan $scan, ReportPresenter $presenter): JsonResponse
    {
        $this->authorizeScan($scan);

        $report = is_array($scan->report) ? $scan->report : [];
        $filename = sprintf(
            'fedaa-sitecheck-%s-%d.json',
            preg_replace('/[^a-z0-9.\-]/i', '-', $scan->host) ?: 'report',
            $scan->id,
        );

        return response()->json(
            $presenter->export($report),
            200,
            [
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                'X-Content-Type-Options' => 'nosniff',
            ],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );
    }

    public function destroy(Scan $scan): Response
    {
        abort_unless($this->ownership->owns($scan) && ! $scan->isDemo(), 404);

        $scan->delete();

        return redirect()
            ->route('history.index')
            ->with('status', __('sitecheck.flash.scan_deleted'));
    }

    private function authorizeScan(Scan $scan): void
    {
        $isDemo = (bool) config('sitecheck.demo_mode') && $scan->isDemo();

        abort_unless($this->ownership->owns($scan) || $isDemo, 404);
    }
}
