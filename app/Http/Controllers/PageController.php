<?php

declare(strict_types=1);

// Copyright (c) 2026 Fedaa Mohamed. All rights reserved.

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

final class PageController extends Controller
{
    public function about(): View
    {
        return $this->show('about');
    }

    public function privacy(): View
    {
        return $this->show('privacy');
    }

    public function terms(): View
    {
        return $this->show('terms');
    }

    public function help(): View
    {
        return $this->show('help');
    }

    public function changelog(): View
    {
        return $this->show('changelog');
    }

    private function show(string $page): View
    {
        return view('pages.show', ['page' => $page]);
    }
}
