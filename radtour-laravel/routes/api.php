<?php

use App\Http\Controllers\Api\PlaceController;
use App\Http\Controllers\Api\RouteController;
use Illuminate\Support\Facades\Route;

Route::post('/route', RouteController::class)->middleware('throttle:20,1');
Route::post('/places/search', [PlaceController::class, 'search'])->middleware('throttle:30,1');
Route::post('/places/details', [PlaceController::class, 'details'])->middleware('throttle:20,1');
Route::post('/places/photo', [PlaceController::class, 'photo'])->middleware('throttle:20,1');
Route::get('/places/usage', [PlaceController::class, 'usage']);
Route::post('/places/overpass', [PlaceController::class, 'overpass'])->middleware('throttle:30,1');
