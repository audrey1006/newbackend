<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use App\Models\CollectionRequest;
use App\Models\WasteCollectorProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class RatingController extends Controller
{
    /**
     * Display a listing of ratings.
     */
    public function index()
    {
        $ratings = Rating::with(['client.user', 'collector.user', 'collectionRequest'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['ratings' => $ratings]);
    }

    /**
     * Store a newly created rating.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'request_id' => 'required|exists:collection_requests,request_id',
            'client_id' => 'required|exists:client_profiles,client_id',
            'collector_id' => 'required|exists:waste_collector_profiles,collector_id',
            'score' => 'required|integer|between:1,5',
            'comment' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Vérifier que la demande de collecte est terminée
        $collectionRequest = CollectionRequest::findOrFail($request->request_id);
        if ($collectionRequest->status !== 'effectuée') {
            return response()->json([
                'error' => 'Can only rate completed collection requests'
            ], 400);
        }

        // Vérifier que le client et l'éboueur sont bien associés à la demande
        if (
            $collectionRequest->client_id !== $request->client_id ||
            $collectionRequest->collector_id !== $request->collector_id
        ) {
            return response()->json([
                'error' => 'Client and collector must be associated with the collection request'
            ], 400);
        }

        // Vérifier qu'il n'y a pas déjà une évaluation pour cette demande
        $existingRating = Rating::where('request_id', $request->request_id)->first();
        if ($existingRating) {
            return response()->json([
                'error' => 'A rating already exists for this collection request'
            ], 400);
        }

        $rating = Rating::create([
            'request_id' => $request->request_id,
            'client_id' => $request->client_id,
            'collector_id' => $request->collector_id,
            'score' => $request->score,
            'comment' => $request->comment,
        ]);

        // Mettre à jour la note moyenne de l'éboueur
        $this->updateCollectorRating($request->collector_id);

        return response()->json([
            'rating' => $rating->load(['client.user', 'collector.user', 'collectionRequest']),
            'message' => 'Rating created successfully'
        ], 201);
    }

    /**
     * Display the specified rating.
     */
    public function show($id)
    {
        $rating = Rating::with(['client.user', 'collector.user', 'collectionRequest'])
            ->findOrFail($id);

        return response()->json(['rating' => $rating]);
    }

    /**
     * Update the specified rating.
     */
    public function update(Request $request, $id)
    {
        $rating = Rating::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'score' => 'integer|between:1,5',
            'comment' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $rating->update($request->only(['score', 'comment']));

        // Mettre à jour la note moyenne de l'éboueur
        $this->updateCollectorRating($rating->collector_id);

        return response()->json([
            'rating' => $rating->fresh(['client.user', 'collector.user', 'collectionRequest']),
            'message' => 'Rating updated successfully'
        ]);
    }

    /**
     * Remove the specified rating.
     */
    public function destroy($id)
    {
        $rating = Rating::findOrFail($id);
        $collectorId = $rating->collector_id;

        $rating->delete();

        // Mettre à jour la note moyenne de l'éboueur
        $this->updateCollectorRating($collectorId);

        return response()->json(['message' => 'Rating deleted successfully']);
    }

    /**
     * Get ratings for a specific collector.
     */
    public function getCollectorRatings($collectorId)
    {
        $ratings = Rating::where('collector_id', $collectorId)
            ->with(['client.user', 'collectionRequest'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['ratings' => $ratings]);
    }

    /**
     * Get ratings given by a specific client.
     */
    public function getClientRatings($clientId)
    {
        $ratings = Rating::where('client_id', $clientId)
            ->with(['collector.user', 'collectionRequest'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['ratings' => $ratings]);
    }

    /**
     * Get rating statistics for a collector.
     */
    public function getCollectorStats($collectorId)
    {
        $stats = Rating::where('collector_id', $collectorId)
            ->select([
                DB::raw('COUNT(*) as total_ratings'),
                DB::raw('AVG(score) as average_score'),
                DB::raw('COUNT(CASE WHEN score = 5 THEN 1 END) as five_star_count'),
                DB::raw('COUNT(CASE WHEN score = 4 THEN 1 END) as four_star_count'),
                DB::raw('COUNT(CASE WHEN score = 3 THEN 1 END) as three_star_count'),
                DB::raw('COUNT(CASE WHEN score = 2 THEN 1 END) as two_star_count'),
                DB::raw('COUNT(CASE WHEN score = 1 THEN 1 END) as one_star_count'),
            ])
            ->first();

        return response()->json(['statistics' => $stats]);
    }

    /**
     * Update the average rating of a collector.
     */
    private function updateCollectorRating($collectorId)
    {
        $averageRating = Rating::where('collector_id', $collectorId)
            ->avg('score');

        WasteCollectorProfile::where('collector_id', $collectorId)
            ->update(['rating' => $averageRating ?: 0]);
    }
}