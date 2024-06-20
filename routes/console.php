<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\DispatchTaikoTx;
Schedule::call(function () {
    $minutes = rand(4, 8);
    \Log::info('proxima tx debe salir en:' .$minutes." minutos");
    DispatchTaikoTx::dispatch()->delay(now()->addMinutes($minutes)); 
})->everyFiveMinutes();
