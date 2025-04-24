<?php

namespace App\Http\Controllers;

use App\Models\CollectionRequest;
use App\Models\ClientProfile;
use App\Models\WasteCollectorProfile;
use App\Models\RecurringCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

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
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:client_profiles,client_id',
            'waste_type_id' => 'required|exists:waste_types,waste_type_id',
            'district_id' => 'required|exists:districts,district_id',
            'collection_type' => 'required|in:ponctuelle,périodique',
            'scheduled_date' => 'required|date|after:now',
            'notes' => 'nullable|string',
            // Champs pour la collecte périodique
            'frequency' => 'required_if:collection_type,périodique|in:quotidien,hebdomadaire,bi-hebdomadaire,mensuel',
            'end_date' => 'required_if:collection_type,périodique|date|after:scheduled_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $collectionRequest = CollectionRequest::create([
            'client_id' => $request->client_id,
            'waste_type_id' => $request->waste_type_id,
            'district_id' => $request->district_id,
            'collection_type' => $request->collection_type,
            'scheduled_date' => $request->scheduled_date,
            'notes' => $request->notes,
            'status' => 'en attente'
        ]);

        // Créer une collecte périodique si nécessaire
        if ($request->collection_type === 'périodique') {
            RecurringCollection::create([
                'request_id' => $collectionRequest->request_id,
                'frequency' => $request->frequency,
                'start_date' => $request->scheduled_date,
                'end_date' => $request->end_date,
                'day_of_week' => $request->day_of_week,
                'day_of_month' => $request->day_of_month,
            ]);
        }

        return response()->json([
            'collection_request' => $collectionRequest->load([
                'client.user',
                'wasteType',
                'district.city',
                'recurringCollection'
            ]),
            'message' => 'Collection request created successfully'
        ], 201);
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
            ->get();

        return response()->json(['collection_requests' => $requests]);
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
}