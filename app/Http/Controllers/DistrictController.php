<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DistrictController extends Controller
{
    /**
     * Display a listing of the districts.
     */
    public function index()
    {
        $districts = District::with('city')->get();
        return response()->json(['districts' => $districts]);
    }

    /**
     * Store a newly created district.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'city_id' => 'required|exists:cities,city_id',
            'name' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if district with same name exists in the city
        $existingDistrict = District::where('city_id', $request->city_id)
            ->where('name', $request->name)
            ->first();

        if ($existingDistrict) {
            return response()->json([
                'error' => 'A district with this name already exists in the selected city'
            ], 422);
        }

        $district = District::create([
            'city_id' => $request->city_id,
            'name' => $request->name,
        ]);

        return response()->json([
            'district' => $district->load('city'),
            'message' => 'District created successfully'
        ], 201);
    }

    /**
     * Display the specified district.
     */
    public function show($id)
    {
        $district = District::with('city')->findOrFail($id);
        return response()->json(['district' => $district]);
    }

    /**
     * Update the specified district.
     */
    public function update(Request $request, $id)
    {
        $district = District::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'city_id' => 'exists:cities,city_id',
            'name' => 'string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->filled('city_id') && $request->filled('name')) {
            // Check if district with same name exists in the city (excluding this district)
            $existingDistrict = District::where('city_id', $request->city_id)
                ->where('name', $request->name)
                ->where('district_id', '!=', $id)
                ->first();

            if ($existingDistrict) {
                return response()->json([
                    'error' => 'A district with this name already exists in the selected city'
                ], 422);
            }
        }

        $district->update($request->only(['city_id', 'name']));

        return response()->json([
            'district' => $district->fresh('city'),
            'message' => 'District updated successfully'
        ]);
    }

    /**
     * Remove the specified district.
     */
    public function destroy($id)
    {
        $district = District::findOrFail($id);

        // Check if the district has associated client profiles, waste collector profiles, or collection requests
        if (
            $district->clientProfiles()->count() > 0 ||
            $district->wasteCollectorProfiles()->count() > 0 ||
            $district->collectionRequests()->count() > 0
        ) {
            return response()->json([
                'error' => 'Cannot delete district because it has associated profiles or collection requests'
            ], 400);
        }

        $district->delete();

        return response()->json(['message' => 'District deleted successfully']);
    }

    /**
     * Get client profiles in a district.
     */
    public function getClientProfiles($id)
    {
        $district = District::findOrFail($id);
        $clientProfiles = $district->clientProfiles()->with('user')->get();

        return response()->json(['client_profiles' => $clientProfiles]);
    }

    /**
     * Get waste collector profiles in a district.
     */
    public function getWasteCollectorProfiles($id)
    {
        $district = District::findOrFail($id);
        $collectorProfiles = $district->wasteCollectorProfiles()->with('user')->get();

        return response()->json(['collector_profiles' => $collectorProfiles]);
    }

    /**
     * Get collection requests in a district.
     */
    public function getCollectionRequests($id)
    {
        $district = District::findOrFail($id);
        $collectionRequests = $district->collectionRequests()
            ->with(['client.user', 'collector.user', 'wasteType'])
            ->get();

        return response()->json(['collection_requests' => $collectionRequests]);
    }
}