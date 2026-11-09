<?php

use App\Http\Controllers\DeviceController;
use Illuminate\Support\Facades\Route;

Route::get('devices', [DeviceController::class, 'index']);
Route::post('devices', [DeviceController::class, 'store']);
Route::get('devices/{device}', [DeviceController::class, 'show']);
Route::post('devices/{device}/ping', [DeviceController::class, 'ping']);
