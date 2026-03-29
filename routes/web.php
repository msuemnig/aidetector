<?php

use App\Http\Controllers\AnalyserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AnalyserController::class, 'index'])->name('analyser.index');
Route::post('/analyse', [AnalyserController::class, 'analyse'])->name('analyser.analyse');
Route::get('/about', [AnalyserController::class, 'about'])->name('analyser.about');
Route::get('/testing', [AnalyserController::class, 'testing'])->name('analyser.testing');
