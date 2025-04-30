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
Route::post('/waste-collector/register', [WasteCollectorProfileController::class, 'register']);

// Routes publiques pour l'inscription
Route::get('/cities', [CityController::class, 'index']);
Route::get('/cities/{city_id}/districts', [DistrictController::class, 'getDistrictsByCity']);

// Routes protégées
Route::middleware('auth:sanctum')->group(function () {
    // Profil utilisateur
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Types de déchets
    Route::get('/waste-types', [WasteTypeController::class, 'index']);

    // Créneaux horaires
    Route::get('/time-slots', [CollectionRequestController::class, 'getTimeSlots']);

    // Espace Éboueur
    Route::prefix('waste-collector')->group(function () {
        Route::get('/profile', [WasteCollectorProfileController::class, 'getProfile']);
        Route::get('/collection-requests/{id}', [WasteCollectorProfileController::class, 'getCollectionRequests']);
        Route::put('/availability/{id}', [WasteCollectorProfileController::class, 'updateAvailability']);
        Route::post('/upload-photo', [WasteCollectorProfileController::class, 'uploadPhoto']);
    });

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
        Route::get('/collector/{collectorId}/status/{status}', [CollectionRequestController::class, 'getCollectorRequestsByStatus']);
        Route::get('/district/{districtId}', [CollectionRequestController::class, 'getDistrictRequests']);
        Route::get('/city/{cityId}', [CollectionRequestController::class, 'getCityRequests']);
        Route::get('/city/{cityId}/pending', [CollectionRequestController::class, 'getCityPendingRequests']);
        Route::get('/assigned/{collectorId}', [CollectionRequestController::class, 'getAssignedRequests']);
    });
});
