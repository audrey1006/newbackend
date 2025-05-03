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
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PhotoController;

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

    // Routes administrateur
    Route::prefix('admin')->group(function () {
        // Dashboard et statistiques
        Route::get('/dashboard-stats', [AdminController::class, 'getDashboardStats']);
        Route::get('/statistiques', [AdminController::class, 'getStatistics']);

        // Commandes
        Route::get('/commandes/en-attente', [AdminController::class, 'getPendingOrders']);
        Route::get('/commandes/en-cours', [AdminController::class, 'getInProgressOrders']);
        Route::get('/commandes/effectuees', [AdminController::class, 'getCompletedOrders']);

        // Gestion des erreurs
        Route::get('/erreurs', [AdminController::class, 'getErrors']);
        Route::put('/erreurs/{id}/resoudre', [AdminController::class, 'resolveError']);

        // Gestion des clients
        Route::get('/clients', [AdminController::class, 'getAllClients']);
        Route::get('/clients/{id}', [AdminController::class, 'getClientDetails']);
        Route::put('/clients/{id}', [AdminController::class, 'updateClient']);
        Route::delete('/clients/{id}', [AdminController::class, 'deleteClient']);

        // Gestion des éboueurs
        Route::get('/collectors', [AdminController::class, 'getAllCollectors']);
        Route::put('/collectors/{id}/status', [AdminController::class, 'updateCollectorStatus']);
        Route::delete('/collectors/{id}', [AdminController::class, 'deleteCollector']);

        // Gestion des utilisateurs
        Route::get('/users', [AdminController::class, 'getAllUsers']);
        Route::post('/users', [AdminController::class, 'createUser']);
        Route::put('/users/{id}', [AdminController::class, 'updateUser']);
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);
        Route::put('/users/{id}/role', [AdminController::class, 'changeUserRole']);
        Route::put('/users/{id}/reset-password', [AdminController::class, 'resetUserPassword']);
    });

    // Upload de photo
    Route::post('/upload-photo', [PhotoController::class, 'uploadPhoto']);

    // Historique des commandes du client
    Route::get('/user/{userId}/collection-requests', [CollectionRequestController::class, 'getUserRequests']);
});
