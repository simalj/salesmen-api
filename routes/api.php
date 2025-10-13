<?php

use App\Http\Controllers\CodelistController;
use App\Http\Controllers\SalesmanController;
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

// Salesmen CRUD API
Route::apiResource('salesmen', SalesmanController::class)->parameters([
    'salesmen' => 'salesman' // URL parameter will be {salesman} instead of {salesmen}
]);

// Codelists API
Route::get('codelists', [CodelistController::class, 'index']);