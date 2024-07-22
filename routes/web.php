<?php

use App\Livewire\Gherkinize;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DownloadController;

Route::get('/gherkinize', Gherkinize::class);
Route::get('/download', [DownloadController::class, 'download'])->name('download');
