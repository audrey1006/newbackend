<?php

namespace App\Http\Controllers;

use App\Models\WasteCollectorProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PhotoController extends Controller
{
    public function uploadPhoto(Request $request)
    {
        try {
            // Validation
            $validator = Validator::make($request->all(), [
                'photo' => 'required|image|mimes:jpeg,png,jpg|max:5120',
                'user_id' => 'required|exists:users,id',
                'type' => 'required|in:profile'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $file = $request->file('photo');
            $userId = $request->user_id;

            // Créer le dossier s'il n'existe pas
            if (!Storage::disk('public')->exists('profiles')) {
                Storage::disk('public')->makeDirectory('profiles');
            }

            // Générer un nom unique
            $fileName = 'profile_' . $userId . '_' . time() . '.' . $file->extension();
            // Stocker le fichier dans storage/app/public/profiles
            $path = $file->storeAs('profiles', $fileName, 'public');
            $photoPath = '/storage/' . $path; // Chemin public

            // Mise à jour du profil
            $profile = WasteCollectorProfile::updateOrCreate(
                ['user_id' => $userId],
                ['photo_path' => $photoPath]
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Photo uploaded successfully',
                'photo_path' => $profile->photo_path,
                'photo_url' => url($profile->photo_path)
            ], 200);

        } catch (\Exception $e) {
            Log::error('Upload error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error uploading photo',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}