<?php

use App\Http\Controllers\HistoryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScanController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/history', [HistoryController::class, 'index'])->name('history.index');

Route::get('/about', [PageController::class, 'about'])->name('pages.about');
Route::get('/privacy', [PageController::class, 'privacy'])->name('pages.privacy');
Route::get('/terms', [PageController::class, 'terms'])->name('pages.terms');
Route::get('/help', [PageController::class, 'help'])->name('pages.help');
Route::get('/changelog', [PageController::class, 'changelog'])->name('pages.changelog');

Route::post('/scan', [ScanController::class, 'store'])
    ->middleware('throttle:scan')
    ->name('scan.store');

Route::post('/locale', LocaleController::class)->name('locale.update');

Route::prefix('scans')->name('scans.')->group(function (): void {
    Route::get('/{scan}', [ReportController::class, 'show'])->name('show');
    Route::get('/{scan}/print', [ReportController::class, 'print'])->name('print');
    Route::get('/{scan}/export.json', [ReportController::class, 'exportJson'])->name('export');
    Route::post('/{scan}/rescan', [ScanController::class, 'rescan'])
        ->middleware('throttle:scan')
        ->name('rescan');
    Route::delete('/{scan}', [ReportController::class, 'destroy'])->name('destroy');
});
