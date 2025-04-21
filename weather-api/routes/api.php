<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

use App\Http\Controllers\WeatherController;

Route::get('/current', [WeatherController::class, 'getCurrentWeather']);
Route::get('/forecast', [WeatherController::class, 'getForecast']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
