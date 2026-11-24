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

final class HistoryController extends Controller
{
    public function index(Request $request, ScanOwnership $ownership): View
    {
        $query = trim((string) $request->query('q', ''));
        if (mb_strlen($query) > 100) {
            $query = mb_substr($query, 0, 100);
        }

        $token = $ownership->token();

        $scope = static function (Builder $builder) use ($token): void {
            $builder->where('owner_token', $token);

            if (config('sitecheck.demo_mode')) {
                $builder->orWhere('owner_token', DemoData::OWNER_TOKEN);
            }
        };

        $builder = Scan::query()
            ->where($scope)
            ->orderByDesc('scanned_at')
            ->orderByDesc('id');

        if ($query !== '') {
            $builder->where(function (Builder $inner) use ($query): void {
                $inner->where('host', 'like', '%'.$query.'%')
                    ->orWhere('input', 'like', '%'.$query.'%');
            });
        }

        $scans = $builder->paginate(8)->withQueryString();

        $hasDemo = config('sitecheck.demo_mode')
            && Scan::query()->where('owner_token', DemoData::OWNER_TOKEN)->exists();

        return view('history.index', [
            'scans' => $scans,
            'query' => $query,
            'hasDemo' => $hasDemo,
        ]);
    }
}
