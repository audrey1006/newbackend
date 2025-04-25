<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ClientProfile;
use App\Models\District;
use App\Models\city;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required'
            ]);

            if (!Auth::attempt($validated)) {
                return response()->json([
                    'message' => 'Invalid credentials'
                ], 401);
            }

            $user = User::where('email', $validated['email'])->first();

            // Charger les relations appropriées selon le rôle
            if ($user->role === 'client') {
                $user->load('clientProfile.district.city');
            } elseif ($user->role === 'eboueur') {
                $user->load('wasteCollectorProfile.district.city');
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login successful',
                'user' => $user,
                'token' => $token
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Login error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Login failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function register(Request $request)
    {
        try {
            Log::info('Registration attempt with data:', $request->all());

            // Validate request data
            $validated = $request->validate([
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:8',
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'phone_number' => 'required|string',
                'address' => 'required|string',
                'city_id' => 'required|exists:cities,city_id',
                'district_id' => [
                    'required',
                    'exists:districts,district_id',
                    function ($attribute, $value, $fail) use ($request) {
                        // Vérifier que le district appartient bien à la ville sélectionnée
                        $district = District::find($value);
                        if (!$district || $district->city_id != $request->city_id) {
                            $fail('Le district sélectionné n\'appartient pas à la ville choisie.');
                        }
                    },
                ],
                'role' => 'required|in:admin,client,eboueur'
            ]);

            DB::beginTransaction();

            try {
                // Create user
                $userData = [
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'phone_number' => $validated['phone_number'],
                    'address' => $validated['address'],
                    'role' => $validated['role']
                ];

                Log::info('Attempting to create user with data:', $userData);

                $user = User::create($userData);

                // Ensure user was created and has an ID
                if (!$user || !$user->user_id) {
                    Log::error('Failed to create user. User object:', ['user' => $user]);
                    throw new \Exception('Failed to create user - no user_id generated');
                }

                // Create client profile if role is client
                if ($validated['role'] === 'client') {
                    Log::info('Creating client profile for user:', ['user_id' => $user->user_id]);

                    $clientProfile = ClientProfile::create([
                        'user_id' => $user->user_id,
                        'district_id' => $validated['district_id']
                    ]);

                    if (!$clientProfile) {
                        Log::error('Failed to create client profile');
                        throw new \Exception('Failed to create client profile');
                    }
                }

                DB::commit();

                $token = $user->createToken('auth_token')->plainTextToken;

                return response()->json([
                    'message' => 'Registration successful',
                    'user' => $user->load('clientProfile.district.city'),
                    'token' => $token
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error during user creation:', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Registration failed:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
                'details' => env('APP_DEBUG') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        $data = [
            'id' => $user->user_id,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'phone_number' => $user->phone_number,
            'address' => $user->address,
            'role' => $user->role
        ];

        // Charger le profil approprié selon le rôle
        if ($user->role === 'client') {
            $data['profile'] = $user->clientProfile()->with('district.city')->first();
        } elseif ($user->role === 'eboueur') {
            $data['profile'] = $user->wasteCollectorProfile()->with('district.city')->first();
        }

        return response()->json($data);
    }
}