<?php

namespace App\Http\Controllers;

use App\Models\RecurringCollection;
use App\Models\CollectionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class RecurringCollectionController extends Controller
{
    /**
     * Display a listing of recurring collections.
     */
    public function index()
    {
        $recurringCollections = RecurringCollection::with([
            'collectionRequest' => function ($query) {
                $query->with(['client.user', 'collector.user', 'wasteType', 'district.city']);
            }
        ])->get();

        return response()->json(['recurring_collections' => $recurringCollections]);
    }

    /**
     * Store a newly created recurring collection.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'request_id' => 'required|exists:collection_requests,request_id',
            'frequency' => 'required|in:quotidien,hebdomadaire,bi-hebdomadaire,mensuel',
            'day_of_week' => 'required_if:frequency,hebdomadaire,bi-hebdomadaire|integer|between:1,7',
            'day_of_month' => 'required_if:frequency,mensuel|integer|between:1,31',
            'start_date' => 'required|date|after:now',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Vérifier si la demande de collecte est de type périodique
        $collectionRequest = CollectionRequest::findOrFail($request->request_id);
        if ($collectionRequest->collection_type !== 'périodique') {
            return response()->json([
                'error' => 'The collection request must be of type périodique'
            ], 400);
        }

        $recurringCollection = RecurringCollection::create([
            'request_id' => $request->request_id,
            'frequency' => $request->frequency,
            'day_of_week' => $request->day_of_week,
            'day_of_month' => $request->day_of_month,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        return response()->json([
            'recurring_collection' => $recurringCollection->load([
                'collectionRequest' => function ($query) {
                    $query->with(['client.user', 'collector.user', 'wasteType', 'district.city']);
                }
            ]),
            'message' => 'Recurring collection created successfully'
        ], 201);
    }

    /**
     * Display the specified recurring collection.
     */
    public function show($id)
    {
        $recurringCollection = RecurringCollection::with([
            'collectionRequest' => function ($query) {
                $query->with(['client.user', 'collector.user', 'wasteType', 'district.city']);
            }
        ])->findOrFail($id);

        return response()->json(['recurring_collection' => $recurringCollection]);
    }

    /**
     * Update the specified recurring collection.
     */
    public function update(Request $request, $id)
    {
        $recurringCollection = RecurringCollection::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'frequency' => 'in:quotidien,hebdomadaire,bi-hebdomadaire,mensuel',
            'day_of_week' => 'required_if:frequency,hebdomadaire,bi-hebdomadaire|integer|between:1,7',
            'day_of_month' => 'required_if:frequency,mensuel|integer|between:1,31',
            'start_date' => 'date|after:now',
            'end_date' => 'nullable|date|after:start_date',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $recurringCollection->update($request->only([
            'frequency',
            'day_of_week',
            'day_of_month',
            'start_date',
            'end_date',
            'is_active'
        ]));

        return response()->json([
            'recurring_collection' => $recurringCollection->fresh([
                'collectionRequest' => function ($query) {
                    $query->with(['client.user', 'collector.user', 'wasteType', 'district.city']);
                }
            ]),
            'message' => 'Recurring collection updated successfully'
        ]);
    }

    /**
     * Remove the specified recurring collection.
     */
    public function destroy($id)
    {
        $recurringCollection = RecurringCollection::findOrFail($id);
        $recurringCollection->delete();

        return response()->json(['message' => 'Recurring collection deleted successfully']);
    }

    /**
     * Toggle the active status of a recurring collection.
     */
    public function toggleActive($id)
    {
        $recurringCollection = RecurringCollection::findOrFail($id);
        $recurringCollection->is_active = !$recurringCollection->is_active;
        $recurringCollection->save();

        return response()->json([
            'is_active' => $recurringCollection->is_active,
            'message' => 'Recurring collection status updated successfully'
        ]);
    }

    /**
     * Get upcoming recurring collections.
     */
    public function getUpcoming()
    {
        $recurringCollections = RecurringCollection::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->with([
                'collectionRequest' => function ($query) {
                    $query->with(['client.user', 'collector.user', 'wasteType', 'district.city']);
                }
            ])
            ->get();

        return response()->json(['recurring_collections' => $recurringCollections]);
    }

    /**
     * Get recurring collections for a specific client.
     */
    public function getClientRecurringCollections($clientId)
    {
        $recurringCollections = RecurringCollection::whereHas('collectionRequest', function ($query) use ($clientId) {
            $query->where('client_id', $clientId);
        })->with([
                    'collectionRequest' => function ($query) {
                        $query->with(['client.user', 'collector.user', 'wasteType', 'district.city']);
                    }
                ])->get();

        return response()->json(['recurring_collections' => $recurringCollections]);
    }

    /**
     * Get recurring collections for a specific collector.
     */
    public function getCollectorRecurringCollections($collectorId)
    {
        $recurringCollections = RecurringCollection::whereHas('collectionRequest', function ($query) use ($collectorId) {
            $query->where('collector_id', $collectorId);
        })->with([
                    'collectionRequest' => function ($query) {
                        $query->with(['client.user', 'collector.user', 'wasteType', 'district.city']);
                    }
                ])->get();

        return response()->json(['recurring_collections' => $recurringCollections]);
    }
}