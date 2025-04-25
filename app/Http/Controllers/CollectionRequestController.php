<?php

namespace App\Http\Controllers;

use App\Models\CollectionRequest;
use App\Models\ClientProfile;
use App\Models\WasteCollectorProfile;
use App\Models\RecurringCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\District;
use App\Models\City;
use App\Models\WasteType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CollectionRequestController extends Controller
{
    /**
     * Display a listing of collection requests.
     */
    public function index(Request $request)
    {
        $query = CollectionRequest::with([
            'client.user',
            'collector.user',
            'wasteType',
            'district.city',
            'recurringCollection'
        ]);

        // Filtrage par statut
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filtrage par type de collecte
        if ($request->has('collection_type')) {
            $query->where('collection_type', $request->collection_type);
        }

        // Filtrage par date
        if ($request->has('date')) {
            $query->whereDate('scheduled_date', $request->date);
        }

        $collectionRequests = $query->get();
        return response()->json(['collection_requests' => $collectionRequests]);
    }

    /**
     * Store a newly created collection request.
     */
    public function store(Request $request)
    {
        try {
            $user = auth()->user();
            Log::info('User attempting to create collection request:', ['user_id' => $user->user_id, 'role' => $user->role]);

            if ($user->role !== 'client') {
                return response()->json([
                    'message' => 'Only clients can create collection requests'
                ], 403);
            }

            $clientProfile = ClientProfile::where('user_id', $user->user_id)->first();
            Log::info('Client profile search result:', ['client_profile' => $clientProfile]);

            if (!$clientProfile) {
                return response()->json([
                    'message' => 'Client profile not found',
                    'user_id' => $user->user_id
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'waste_type_id' => 'required|exists:waste_types,waste_type_id',
                'district_id' => 'required|exists:districts,district_id',
                'collection_type' => 'required|in:ponctuelle,périodique',
                'time_slot_id' => 'required|exists:collection_time_slots,time_slot_id',
                'collection_dates' => 'required|array|min:1',
                'collection_dates.*' => 'required|date|after:now',
                'notes' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Vérifier que les dates sont uniques
            if (count($request->collection_dates) !== count(array_unique($request->collection_dates))) {
                return response()->json([
                    'message' => 'Les dates de collecte doivent être uniques'
                ], 422);
            }

            // Si c'est une collecte ponctuelle, vérifier qu'il n'y a qu'une seule date
            if ($request->collection_type === 'ponctuelle' && count($request->collection_dates) > 1) {
                return response()->json([
                    'message' => 'Une collecte ponctuelle ne peut avoir qu\'une seule date'
                ], 422);
            }

            DB::beginTransaction();

            try {
                // Créer la demande de collecte
                $collectionRequest = CollectionRequest::create([
                    'client_id' => $clientProfile->client_id,
                    'waste_type_id' => $request->waste_type_id,
                    'district_id' => $request->district_id,
                    'collection_type' => $request->collection_type,
                    'notes' => $request->notes,
                    'status' => 'en attente'
                ]);

                // Créer les jours de collecte
                foreach ($request->collection_dates as $date) {
                    DB::table('collection_days')->insert([
                        'request_id' => $collectionRequest->request_id,
                        'time_slot_id' => $request->time_slot_id,
                        'collection_date' => $date,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }

                DB::commit();

                // Charger les relations pour la réponse
                $collectionRequest->load([
                    'client.district',
                    'wasteType',
                    'collectionDays.timeSlot'
                ]);

                return response()->json([
                    'message' => 'Collection request created successfully',
                    'data' => $collectionRequest
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Error creating collection request:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified collection request.
     */
    public function show($id)
    {
        $collectionRequest = CollectionRequest::with([
            'client.user',
            'collector.user',
            'wasteType',
            'district.city',
            'recurringCollection'
        ])->findOrFail($id);

        return response()->json(['collection_request' => $collectionRequest]);
    }

    /**
     * Update the specified collection request.
     */
    public function update(Request $request, $id)
    {
        $collectionRequest = CollectionRequest::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'waste_type_id' => 'exists:waste_types,waste_type_id',
            'scheduled_date' => 'date|after:now',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $collectionRequest->update($request->only([
            'waste_type_id',
            'scheduled_date',
            'notes'
        ]));

        return response()->json([
            'collection_request' => $collectionRequest->fresh([
                'client.user',
                'collector.user',
                'wasteType',
                'district.city',
                'recurringCollection'
            ]),
            'message' => 'Collection request updated successfully'
        ]);
    }

    /**
     * Update the status of a collection request.
     */
    public function updateStatus(Request $request, $id)
    {
        $collectionRequest = CollectionRequest::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:en attente,acceptée,en cours,effectuée,annulée',
            'collector_id' => 'required_if:status,acceptée|exists:waste_collector_profiles,collector_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = ['status' => $request->status];

        if ($request->status === 'acceptée' && $request->has('collector_id')) {
            $data['collector_id'] = $request->collector_id;
        }

        if ($request->status === 'effectuée') {
            $data['completed_date'] = now();
        }

        $collectionRequest->update($data);

        return response()->json([
            'collection_request' => $collectionRequest->fresh([
                'client.user',
                'collector.user',
                'wasteType',
                'district.city',
                'recurringCollection'
            ]),
            'message' => 'Collection request status updated successfully'
        ]);
    }

    /**
     * Remove the specified collection request.
     */
    public function destroy($id)
    {
        $collectionRequest = CollectionRequest::findOrFail($id);

        // Vérifier si la demande peut être supprimée
        if (in_array($collectionRequest->status, ['en cours', 'effectuée'])) {
            return response()->json([
                'error' => 'Cannot delete a collection request that is in progress or completed'
            ], 400);
        }

        $collectionRequest->delete();

        return response()->json(['message' => 'Collection request deleted successfully']);
    }

    /**
     * Get collection requests for a specific client.
     */
    public function getClientRequests($clientId)
    {
        $requests = CollectionRequest::where('client_id', $clientId)
            ->with([
                'collector.user',
                'wasteType',
                'district.city',
                'recurringCollection'
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'collection_requests' => $requests
        ]);


    }

    /**
     * Get collection requests for a specific collector.
     */
    public function getCollectorRequests($collectorId)
    {
        $requests = CollectionRequest::where('collector_id', $collectorId)
            ->with([
                'client.user',
                'wasteType',
                'district.city',
                'recurringCollection'
            ])
            ->get();

        return response()->json(['collection_requests' => $requests]);
    }

    /**
     * Get collection requests in a specific district.
     */
    public function getDistrictRequests($districtId)
    {
        $requests = CollectionRequest::where('district_id', $districtId)
            ->with([
                'client.user',
                'collector.user',
                'wasteType',
                'recurringCollection'
            ])
            ->get();

        return response()->json(['collection_requests' => $requests]);
    }


    public function cancel($id)
    {
        $request = CollectionRequest::findOrFail($id);

        if ($request->status !== 'en attente') {
            return response()->json([
                'error' => 'Seules les demandes en attente peuvent être annulées'
            ], 400);
        }

        $request->update(['status' => 'annulée']);

        return response()->json([
            'message' => 'Demande de collecte annulée avec succès',
            'collection_request' => $request
        ]);
    }

    /**
     * Get available time slots
     */
    public function getTimeSlots()
    {
        $timeSlots = DB::table('collection_time_slots')
            ->where('is_active', true)
            ->orderBy('collection_time')
            ->get();

        return response()->json([
            'time_slots' => $timeSlots
        ]);
    }

}