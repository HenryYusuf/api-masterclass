<?php

use App\Http\Controllers\Api\V1\TicketController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// {domain}/api/tickets/{id}/{action(store/update/delete)}
// universal resource locator
// tickets
// users

Route::apiResource('tickets', TicketController::class)->middleware('auth:sanctum');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
