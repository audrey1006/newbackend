<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ClientProfile;
use App\Models\District;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClientProfileController extends Controller
{
    /**
     * Display a listing of client profiles.
     */
    public function index()
    {
        $clientProfiles = ClientProfile::with(['user', 'district.city'])->get();
        return response()->json(['client_profiles' => $clientProfiles]);
    }

    /**
     * Store a newly created client profile.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,user_id|unique:client_profiles,user_id',
            'district_id' => 'required|exists:districts,district_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if the user role is client
        $user = User::findOrFail($request->user_id);
        if ($user->role !== 'client') {
            return response()->json(['error' => 'This user is not a client'], 400);
        }

        $clientProfile = ClientProfile::create([
            'user_id' => $request->user_id,
            'district_id' => $request->district_id,
        ]);

        return response()->json([
            'client_profile' => $clientProfile->load(['user', 'district.city']),
            'message' => 'Client profile created successfully'
        ], 201);
    }

    /**
     * Display the specified client profile.
     */
    public function show($id)
    {
        $clientProfile = ClientProfile::with(['user', 'district.city'])->findOrFail($id);
        return response()->json(['client_profile' => $clientProfile]);
    }

    /**
     * Update the specified client profile.
     */
    public function update(Request $request, $id)
    {
        $clientProfile = ClientProfile::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'district_id' => 'exists:districts,district_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $clientProfile->update($request->only('district_id'));

        return response()->json([
            'client_profile' => $clientProfile->fresh(['user', 'district.city']),
            'message' => 'Client profile updated successfully'
        ]);
    }

    /**
     * Remove the specified client profile.
     */
    public function destroy($id)
    {
        $clientProfile = ClientProfile::findOrFail($id);
        $clientProfile->delete();

        return response()->json(['message' => 'Client profile deleted successfully']);
    }

    /**
     * Get the collection requests for a client.
     */
    public function getCollectionRequests($id)
    {
        $clientProfile = ClientProfile::findOrFail($id);
        $collectionRequests = $clientProfile->collectionRequests()
            ->with(['collector.user', 'wasteType', 'district.city', 'recurringCollection'])
            ->get();

        return response()->json(['collection_requests' => $collectionRequests]);
    }

    /**
     * Get the ratings given by a client.
     */
    public function getRatings($id)
    {
        $clientProfile = ClientProfile::findOrFail($id);
        $ratings = $clientProfile->ratings()->with(['collector.user', 'collectionRequest'])->get();

        return response()->json(['ratings' => $ratings]);
    }

    /**
     * Register a new client (create user and profile).
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'phone_number' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'district_id' => 'required|exists:districts,district_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create user
        $user = User::create([
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'role' => 'client',
        ]);

        // Create client profile
        $clientProfile = ClientProfile::create([
            'user_id' => $user->user_id,
            'district_id' => $request->district_id,
        ]);

        return response()->json([
            'user' => $user,
            'client_profile' => $clientProfile->load('district.city'),
            'message' => 'Client registered successfully'
        ], 201);
    }
}