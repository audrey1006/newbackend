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

// Routes publiques
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Routes protégées
Route::middleware('auth:sanctum')->group(function () {
    // Profil utilisateur
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Gestion des villes et districts
    Route::get('/cities', [CityController::class, 'index']);
    Route::get('/cities/{city_id}/districts', [DistrictController::class, 'getDistrictsByCity']);

    // Types de déchets
    Route::get('/waste-types', [WasteTypeController::class, 'index']);

    // Créneaux horaires
    Route::get('/time-slots', [CollectionRequestController::class, 'getTimeSlots']);

    // Demandes de collecte
    Route::prefix('collection-requests')->group(function () {
        // CRUD de base
        Route::get('/', [CollectionRequestController::class, 'index']);
        Route::post('/', [CollectionRequestController::class, 'store']);
        Route::get('/{id}', [CollectionRequestController::class, 'show']);
        Route::put('/{id}', [CollectionRequestController::class, 'update']);
        Route::delete('/{id}', [CollectionRequestController::class, 'destroy']);

        // Actions spécifiques
        Route::put('/{id}/cancel', [CollectionRequestController::class, 'cancel']);
        Route::put('/{id}/status', [CollectionRequestController::class, 'updateStatus']);

        // Filtres spécifiques
        Route::get('/client/{clientId}', [CollectionRequestController::class, 'getClientRequests']);
        Route::get('/collector/{collectorId}', [CollectionRequestController::class, 'getCollectorRequests']);
        Route::get('/district/{districtId}', [CollectionRequestController::class, 'getDistrictRequests']);
    });
});
