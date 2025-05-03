<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ClientProfile;
use App\Models\WasteCollectorProfile;
use App\Models\CollectionRequest;
use App\Models\Error;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AdminController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function getDashboardStats()
    {
        try {
            $stats = [
                'total_clients' => ClientProfile::count(),
                'total_collectors' => WasteCollectorProfile::count(),
                'total_requests' => CollectionRequest::count(),
                'pending_requests' => CollectionRequest::where('status', 'en attente')->count(),
                'in_progress_requests' => CollectionRequest::where('status', 'acceptée')->count(),
                'completed_requests' => CollectionRequest::where('status', 'effectuée')->count(),
                'collection_by_type' => DB::table('collection_requests')
                    ->join('waste_types', 'collection_requests.waste_type_id', '=', 'waste_types.waste_type_id')
                    ->select('waste_types.name', DB::raw('count(*) as count'))
                    ->groupBy('waste_types.waste_type_id', 'waste_types.name')
                    ->get(),
                'recent_activities' => CollectionRequest::with(['client.user', 'collector.user', 'wasteType'])
                    ->orderBy('created_at', 'desc')
                    ->take(5)
                    ->get()
            ];

            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error fetching dashboard statistics'], 500);
        }
    }

    /**
     * Get all collection requests with details
     */
    public function getAllCollectionRequests()
    {
        try {
            $requests = CollectionRequest::with([
                'client.user',
                'collector.user',
                'wasteType',
                'district.city'
            ])->orderBy('created_at', 'desc')->get();

            return response()->json($requests);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error fetching collection requests'], 500);
        }
    }

    /**
     * Get all clients with their profiles
     */
    public function getAllClients()
    {
        try {
            $clients = ClientProfile::with(['user', 'district.city'])->get();
            return response()->json($clients);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error fetching clients'], 500);
        }
    }

    /**
     * Get all waste collectors with their profiles
     */
    public function getAllCollectors()
    {
        try {
            $collectors = WasteCollectorProfile::with(['user', 'district.city'])
                ->get();

            // Adapter la structure pour le frontend
            $result = $collectors->map(function ($collector) {
                return [
                    'collector_id' => $collector->collector_id,
                    'user_id' => $collector->user->user_id ?? null,
                    'photo_path' => $collector->photo_path ?? $collector->photo_url ?? null,
                    'first_name' => $collector->user->first_name ?? '',
                    'last_name' => $collector->user->last_name ?? '',
                    'email' => $collector->user->email ?? '',
                    'phone_number' => $collector->user->phone_number ?? '',
                    'district' => $collector->district->name ?? '',
                    'city' => $collector->district->city->name ?? '',
                ];
            });

            return response()->json(['collectors' => $result]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error fetching collectors'], 500);
        }
    }

    /**
     * Get collection requests statistics by date
     */
    public function getCollectionStats(Request $request)
    {
        try {
            $stats = CollectionRequest::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "effectuée" THEN 1 ELSE 0 END) as completed'),
                DB::raw('SUM(CASE WHEN status = "en attente" THEN 1 ELSE 0 END) as pending'),
                DB::raw('SUM(CASE WHEN status = "acceptée" THEN 1 ELSE 0 END) as in_progress')
            )
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->take(30)
                ->get();

            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error fetching collection statistics'], 500);
        }
    }

    /**
     * Get collector performance statistics
     */
    public function getCollectorPerformance()
    {
        try {
            $performance = WasteCollectorProfile::with(['user'])
                ->select(
                    'waste_collector_profiles.*',
                    DB::raw('(SELECT COUNT(*) FROM collection_requests WHERE collector_id = waste_collector_profiles.collector_id) as total_collections'),
                    DB::raw('(SELECT COUNT(*) FROM collection_requests WHERE collector_id = waste_collector_profiles.collector_id AND status = "effectuée") as completed_collections')
                )
                ->get();

            return response()->json($performance);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error fetching collector performance'], 500);
        }
    }

    /**
     * Update collector status
     */
    public function updateCollectorStatus(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'is_available' => 'required|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $collector = WasteCollectorProfile::findOrFail($id);
            $collector->is_available = $request->is_available;
            $collector->save();

            return response()->json([
                'message' => 'Collector status updated successfully',
                'collector' => $collector
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error updating collector status'], 500);
        }
    }

    /**
     * Delete a collector
     */
    public function deleteCollector($id)
    {
        try {
            $collector = WasteCollectorProfile::findOrFail($id);

            // Vérifier si l'éboueur a des collectes en cours
            $hasActiveCollections = CollectionRequest::where('collector_id', $id)
                ->whereIn('status', ['en attente', 'acceptée'])
                ->exists();

            if ($hasActiveCollections) {
                return response()->json([
                    'error' => 'Cannot delete collector with active collections'
                ], 400);
            }

            $collector->delete();

            return response()->json(['message' => 'Collector deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error deleting collector'], 500);
        }
    }

    /**
     * Delete a client
     */
    public function deleteClient($id)
    {
        try {
            $client = ClientProfile::findOrFail($id);

            // Vérifier si le client a des demandes en cours
            $hasActiveRequests = CollectionRequest::where('client_id', $id)
                ->whereIn('status', ['en attente', 'acceptée'])
                ->exists();

            if ($hasActiveRequests) {
                return response()->json([
                    'error' => 'Cannot delete client with active requests'
                ], 400);
            }

            $client->delete();

            return response()->json(['message' => 'Client deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error deleting client'], 500);
        }
    }

    /**
     * Create a new user (admin, client, or collector)
     */
    public function createUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,client,eboueur,collector',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->role = $request->role;
        $user->save();
        return response()->json(['message' => 'User created successfully', 'user' => $user]);
    }

    /**
     * Update an existing user
     */
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'role' => 'sometimes|in:admin,client,eboueur,collector',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        if ($request->has('name'))
            $user->name = $request->name;
        if ($request->has('email'))
            $user->email = $request->email;
        if ($request->has('role'))
            $user->role = $request->role;
        $user->save();
        return response()->json(['message' => 'User updated successfully', 'user' => $user]);
    }

    /**
     * Delete a user
     */
    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }

    /**
     * Change the role of a user
     */
    public function changeUserRole(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'role' => 'required|in:admin,client,eboueur,collector',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $user = User::findOrFail($id);
        $user->role = $request->role;
        $user->save();
        return response()->json(['message' => 'User role updated successfully', 'user' => $user]);
    }

    /**
     * Reset the password of a user
     */
    public function resetUserPassword(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $user = User::findOrFail($id);
        $user->password = bcrypt($request->password);
        $user->save();
        return response()->json(['message' => 'User password reset successfully']);
    }

    /**
     * Get all users (admin, client, eboueur)
     */
    public function getAllUsers()
    {
        try {
            $users = User::all();
            return response()->json(['users' => $users]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error fetching users'], 500);
        }
    }

    /**
     * Get pending orders
     */
    public function getPendingOrders()
    {
        try {
            $orders = CollectionRequest::with(['client.user', 'wasteType', 'district.city'])
                ->where('status', 'en attente')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json($orders);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error fetching pending orders'], 500);
        }
    }

    /**
     * Get in-progress orders
     */
    public function getInProgressOrders()
    {
        try {
            $orders = CollectionRequest::with(['client.user', 'collector.user', 'wasteType', 'district.city'])
                ->where('status', 'acceptée')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json($orders);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error fetching in-progress orders'], 500);
        }
    }

    /**
     * Get completed orders
     */
    public function getCompletedOrders()
    {
        try {
            $orders = CollectionRequest::with(['client.user', 'collector.user', 'wasteType', 'district.city'])
                ->where('status', 'effectuée')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json($orders);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error fetching completed orders'], 500);
        }
    }

    /**
     * Get all errors
     */
    public function getErrors()
    {
        try {
            $errors = Error::orderBy('created_at', 'desc')->get();
            return response()->json($errors);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error fetching errors'], 500);
        }
    }

    /**
     * Resolve an error
     */
    public function resolveError($id)
    {
        try {
            $error = Error::findOrFail($id);
            $error->status = 'résolu';
            $error->resolved_at = Carbon::now();
            $error->save();

            return response()->json(['message' => 'Error resolved successfully', 'error' => $error]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error resolving error'], 500);
        }
    }

    /**
     * Get detailed statistics
     */
    public function getStatistics()
    {
        try {
            $stats = [
                'commandesParMois' => $this->getOrdersByMonth(),
                'clientsParVille' => $this->getClientsByCity(),
                'revenusParMois' => $this->getRevenueByMonth(),
                'totalCommandes' => CollectionRequest::count(),
                'totalClients' => ClientProfile::count(),
                'revenuTotal' => CollectionRequest::where('status', 'effectuée')->sum('montant'),
                'commandesEnAttente' => CollectionRequest::where('status', 'en attente')->count(),
                'commandesEnCours' => CollectionRequest::where('status', 'acceptée')->count()
            ];

            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error fetching statistics'], 500);
        }
    }

    /**
     * Get orders by month
     */
    private function getOrdersByMonth()
    {
        $orders = CollectionRequest::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('YEAR(created_at) as year'),
            DB::raw('COUNT(*) as count')
        )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $labels = [];
        $data = [];

        foreach ($orders as $order) {
            $date = Carbon::createFromDate($order->year, $order->month, 1);
            $labels[] = $date->format('M Y');
            $data[] = $order->count;
        }

        return [
            'labels' => $labels,
            'data' => $data
        ];
    }

    /**
     * Get clients by city
     */
    private function getClientsByCity()
    {
        $clients = ClientProfile::join('districts', 'client_profiles.district_id', '=', 'districts.district_id')
            ->join('cities', 'districts.city_id', '=', 'cities.city_id')
            ->select('cities.name', DB::raw('COUNT(*) as count'))
            ->groupBy('cities.city_id', 'cities.name')
            ->get();

        return [
            'labels' => $clients->pluck('name'),
            'data' => $clients->pluck('count')
        ];
    }

    /**
     * Get revenue by month
     */
    private function getRevenueByMonth()
    {
        $revenue = CollectionRequest::where('status', 'effectuée')
            ->select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('YEAR(created_at) as year'),
                DB::raw('SUM(montant) as total')
            )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $labels = [];
        $data = [];

        foreach ($revenue as $rev) {
            $date = Carbon::createFromDate($rev->year, $rev->month, 1);
            $labels[] = $date->format('M Y');
            $data[] = $rev->total;
        }

        return [
            'labels' => $labels,
            'data' => $data
        ];
    }
}