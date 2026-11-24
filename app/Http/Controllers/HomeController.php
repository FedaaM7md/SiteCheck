<?php

declare(strict_types=1);

// Copyright (c) 2026 Fedaa Mohamed. All rights reserved.

namespace App\Http\Controllers;

use App\Models\Scan;
use App\Services\DemoData;
use App\Services\ScanOwnership;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class HomeController extends Controller
{
    public function __invoke(Request $request, ScanOwnership $ownership): View
    {
        $token = $ownership->token();

        $scope = static function (Builder $query) use ($token): void {
            $query->where('owner_token', $token);

            if (config('sitecheck.demo_mode')) {
                $query->orWhere('owner_token', DemoData::OWNER_TOKEN);
            }
        };

        $recent = Scan::query()
            ->where($scope)
            ->orderByDesc('scanned_at')
            ->orderByDesc('id')
            ->limit(3)
            ->get();

        $total = Scan::query()->where($scope)->count();

        return view('home', [
            'recent' => $recent,
            'total' => $total,
            'lastScan' => $recent->first(),
            'prefill' => (string) $request->query('target', ''),
        ]);
    }
}
