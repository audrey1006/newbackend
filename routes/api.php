<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CollectionRequestController;
use App\Http\Controllers\DistrictController;
use App\Http\Controllers\WasteTypeController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\RecurringCollectionController;
use App\Http\Controllers\ClientProfileController;
use App\Http\Controllers\WasteCollectorProfileController;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::apiResource('collection-requests', CollectionRequestController::class);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/collection-requests', [CollectionRequestController::class, 'store']);
    Route::get('/collection-requests', [CollectionRequestController::class, 'index']);
    Route::get('/collection-requests/{id}', [CollectionRequestController::class, 'show']);
    Route::put('/collection-requests/{id}', [CollectionRequestController::class, 'update']);
    Route::delete('/collection-requests/{id}', [CollectionRequestController::class, 'destroy']);
    Route::put('/collection-requests/{id}/cancel', [CollectionRequestController::class, 'cancel']);
    // Routes additionnelles spécifiques
    Route::get('/client-requests/{clientId}', [CollectionRequestController::class, 'getClientRequests']);
    Route::get('/collector-requests/{collectorId}', [CollectionRequestController::class, 'getCollectorRequests']);
    Route::get('/district-requests/{districtId}', [CollectionRequestController::class, 'getDistrictRequests']);
    Route::put('/collection-requests/{id}/status', [CollectionRequestController::class, 'updateStatus']);

    Route::get('/cities', [CityController::class, 'index']);
    Route::get('/cities/{city_id}/districts', [DistrictController::class, 'getDistrictsByCity']);

    Route::get('/me', [AuthController::class, 'me']);
        
    });
