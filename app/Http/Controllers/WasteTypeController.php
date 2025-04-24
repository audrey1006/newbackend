<?php

namespace App\Http\Controllers;

use App\Models\WasteType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WasteTypeController extends Controller
{
    /**
     * Display a listing of the waste types.
     */
    public function index()
    {
        $wasteTypes = WasteType::all();
        return response()->json(['waste_types' => $wasteTypes]);
    }

    /**
     * Store a newly created waste type.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50|unique:waste_types,name',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $wasteType = WasteType::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json(['waste_type' => $wasteType, 'message' => 'Waste type created successfully'], 201);
    }

    /**
     * Display the specified waste type.
     */
    public function show($id)
    {
        $wasteType = WasteType::findOrFail($id);
        return response()->json(['waste_type' => $wasteType]);
    }

    /**
     * Update the specified waste type.
     */
    public function update(Request $request, $id)
    {
        $wasteType = WasteType::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:50|unique:waste_types,name,' . $wasteType->waste_type_id . ',waste_type_id',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $wasteType->update($request->only(['name', 'description']));

        return response()->json(['waste_type' => $wasteType, 'message' => 'Waste type updated successfully']);
    }

    /**
     * Remove the specified waste type.
     */
    public function destroy($id)
    {
        $wasteType = WasteType::findOrFail($id);

        // Check if the waste type has associated collection requests
        if ($wasteType->collectionRequests()->count() > 0) {
            return response()->json([
                'error' => 'Cannot delete waste type because it has associated collection requests'
            ], 400);
        }

        $wasteType->delete();

        return response()->json(['message' => 'Waste type deleted successfully']);
    }

    /**
     * Get the collection requests for a waste type.
     */
    public function getCollectionRequests($id)
    {
        $wasteType = WasteType::findOrFail($id);
        $collectionRequests = $wasteType->collectionRequests()
            ->with(['client.user', 'collector.user', 'district.city'])
            ->get();

        return response()->json(['collection_requests' => $collectionRequests]);
    }
}