<?php

use App\Http\Controllers\PublicQueueApiController;
use Illuminate\Support\Facades\Route;

Route::match(['get', 'post'], '/waktu-tunggu/check', [PublicQueueApiController::class, 'checkJson'])
    ->name('api.queue.check');

