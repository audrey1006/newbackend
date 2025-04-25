<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ClientProfile;
use App\Models\WasteCollectorProfile;
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

            // Validation de base pour tous les utilisateurs
            $baseValidation = [
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:8',
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'phone_number' => 'required|string',
                'address' => 'required|string',
                'district_id' => 'required|exists:districts,district_id',
                'role' => 'required|in:client,eboueur'
            ];

            $validated = $request->validate($baseValidation);

            DB::beginTransaction();

            try {
                // Création de l'utilisateur
                $user = User::create([
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'phone_number' => $validated['phone_number'],
                    'address' => $validated['address'],
                    'role' => $validated['role']
                ]);

                // Création du profil selon le rôle
                if ($validated['role'] === 'client') {
                    $profile = ClientProfile::create([
                        'user_id' => $user->user_id,
                        'district_id' => $validated['district_id']
                    ]);
                    $user->load('clientProfile.district.city');
                } else if ($validated['role'] === 'eboueur') {
                    $profile = WasteCollectorProfile::create([
                        'user_id' => $user->user_id,
                        'district_id' => $validated['district_id'],
                        'is_available' => true
                    ]);
                    $user->load('wasteCollectorProfile.district.city');
                }

                DB::commit();

                $token = $user->createToken('auth_token')->plainTextToken;

                return response()->json([
                    'message' => 'Registration successful',
                    'user' => $user,
                    'token' => $token
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
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

        // Charger le profil approprié selon le rôle
        if ($user->role === 'client') {
            $user->load('clientProfile.district.city');
        } elseif ($user->role === 'eboueur') {
            $user->load('wasteCollectorProfile.district.city');
        }

        return response()->json($user);
    }
}