<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CityController extends Controller
{
    /**
     * Display a listing of the cities.
     */
    public function index()
    {
        $cities = City::with('districts')->get();
        return response()->json(['cities' => $cities]);
    }

    /**
     * Store a newly created city.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:cities,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $city = City::create([
            'name' => $request->name,
        ]);

        return response()->json(['city' => $city, 'message' => 'City created successfully'], 201);
    }

    /**
     * Display the specified city.
     */
    public function show($id)
    {
        $city = City::with('districts')->findOrFail($id);
        return response()->json(['city' => $city]);
    }

    /**
     * Update the specified city.
     */
    public function update(Request $request, $id)
    {
        $city = City::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:cities,name,' . $city->city_id . ',city_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $city->update([
            'name' => $request->name,
        ]);

        return response()->json(['city' => $city, 'message' => 'City updated successfully']);
    }

    /**
     * Remove the specified city.
     */
    public function destroy($id)
    {
        $city = City::findOrFail($id);

        // Check if city has districts
        if ($city->districts()->count() > 0) {
            return response()->json([
                'error' => 'Cannot delete city because it has associated districts. Delete them first.'
            ], 400);
        }

        $city->delete();

        return response()->json(['message' => 'City deleted successfully']);
    }

    /**
     * Get all districts for a specific city.
     */
    public function getDistricts($id)
    {
        $city = City::findOrFail($id);
        $districts = $city->districts;

        return response()->json(['districts' => $districts]);
    }
}