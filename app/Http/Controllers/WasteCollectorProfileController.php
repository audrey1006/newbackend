<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WasteCollectorProfile;
use App\Models\District;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class WasteCollectorProfileController extends Controller
{
    /**
     * Display a listing of waste collector profiles.
     */
    public function index()
    {
        $collectorProfiles = WasteCollectorProfile::with(['user', 'district.city'])->get();
        return response()->json(['collector_profiles' => $collectorProfiles]);
    }

    /**
     * Store a newly created waste collector profile.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,user_id|unique:waste_collector_profiles,user_id',
            'district_id' => 'required|exists:districts,district_id',
            'photo' => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if the user role is eboueur
        $user = User::findOrFail($request->user_id);
        if ($user->role !== 'eboueur') {
            return response()->json(['error' => 'This user is not a waste collector'], 400);
        }

        $data = [
            'user_id' => $request->user_id,
            'district_id' => $request->district_id,
            'is_available' => $request->filled('is_available') ? $request->is_available : true,
        ];

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('collectors', 'public');
            $data['photo_url'] = Storage::url($path);
        }

        $collectorProfile = WasteCollectorProfile::create($data);

        return response()->json([
            'collector_profile' => $collectorProfile->load(['user', 'district.city']),
            'message' => 'Waste collector profile created successfully'
        ], 201);
    }

    /**
     * Display the specified waste collector profile.
     */
    public function show($id)
    {
        $collectorProfile = WasteCollectorProfile::with(['user', 'district.city'])->findOrFail($id);
        return response()->json(['collector_profile' => $collectorProfile]);
    }

    /**
     * Update the specified waste collector profile.
     */
    public function update(Request $request, $id)
    {
        $collectorProfile = WasteCollectorProfile::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'district_id' => 'exists:districts,district_id',
            'photo' => 'nullable|image|max:2048',
            'is_available' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->only(['district_id', 'is_available']);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($collectorProfile->photo_url) {
                $oldPath = str_replace('/storage', 'public', $collectorProfile->photo_url);
                Storage::delete($oldPath);
            }

            $path = $request->file('photo')->store('collectors', 'public');
            $data['photo_url'] = Storage::url($path);
        }

        $collectorProfile->update($data);

        return response()->json([
            'collector_profile' => $collectorProfile->fresh(['user', 'district.city']),
            'message' => 'Waste collector profile updated successfully'
        ]);
    }

    /**
     * Remove the specified waste collector profile.
     */
    public function destroy($id)
    {
        $collectorProfile = WasteCollectorProfile::findOrFail($id);

        // Delete photo if exists
        if ($collectorProfile->photo_url) {
            $path = str_replace('/storage', 'public', $collectorProfile->photo_url);
            Storage::delete($path);
        }

        $collectorProfile->delete();

        return response()->json(['message' => 'Waste collector profile deleted successfully']);
    }

    /**
     * Get the collection requests for a waste collector.
     */
    public function getCollectionRequests($id)
    {
        $collectorProfile = WasteCollectorProfile::findOrFail($id);
        $collectionRequests = $collectorProfile->collectionRequests()
            ->with(['client.user', 'wasteType', 'district.city', 'recurringCollection'])
            ->get();

        return response()->json(['collection_requests' => $collectionRequests]);
    }

    /**
     * Get the ratings for a waste collector.
     */
    public function getRatings($id)
    {
        $collectorProfile = WasteCollectorProfile::findOrFail($id);
        $ratings = $collectorProfile->ratings()->with(['client.user', 'collectionRequest'])->get();

        return response()->json(['ratings' => $ratings]);
    }

    /**
     * Update the availability status of a waste collector.
     */
    public function updateAvailability(Request $request, $id)
    {
        $collectorProfile = WasteCollectorProfile::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'is_available' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $collectorProfile->is_available = $request->is_available;
        $collectorProfile->save();

        return response()->json([
            'is_available' => $collectorProfile->is_available,
            'message' => 'Availability status updated successfully'
        ]);
    }

    /**
     * Find available waste collectors in a specific district.
     */
    public function findAvailableCollectors($districtId)
    {
        $collectors = WasteCollectorProfile::where('district_id', $districtId)
            ->where('is_available', true)
            ->with(['user', 'district.city'])
            ->get();

        return response()->json(['collectors' => $collectors]);
    }

    /**
     * Register a new waste collector (create user and profile).
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
            'photo' => 'nullable|image|max:2048',
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
            'role' => 'eboueur',
        ]);

        $data = [
            'user_id' => $user->user_id,
            'district_id' => $request->district_id,
            'is_available' => true,
        ];

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('collectors', 'public');
            $data['photo_url'] = Storage::url($path);
        }

        // Create waste collector profile
        $collectorProfile = WasteCollectorProfile::create($data);

        return response()->json([
            'user' => $user,
            'collector_profile' => $collectorProfile->load('district.city'),
            'message' => 'Waste collector registered successfully'
        ], 201);
    }
}