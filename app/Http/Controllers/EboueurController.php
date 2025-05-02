<?php

namespace App\Http\Controllers;

use App\Models\WasteCollectorProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EboueurController extends Controller
{
    public function uploadPhoto(Request $request)
    {
        try {
            $request->validate([
                'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048'
            ]);

            $user = Auth::user();
            $profile = WasteCollectorProfile::where('user_id', $user->user_id)->first();

            if (!$profile) {
                return response()->json([
                    'message' => 'Profil éboueur non trouvé'
                ], 404);
            }

            if ($request->hasFile('photo')) {
                // Supprimer l'ancienne photo si elle existe
                if ($profile->photo_url) {
                    Storage::delete('public/photos/' . basename($profile->photo_url));
                }

                // Stocker la nouvelle photo
                $path = $request->file('photo')->store('public/photos');
                $url = Storage::url($path);

                // Mettre à jour le profil avec l'URL de la photo
                $profile->update([
                    'photo_url' => $url
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Photo téléchargée avec succès',
                    'photo_url' => $url
                ]);
            }

            return response()->json([
                'message' => 'Aucune photo n\'a été fournie'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Erreur lors du téléchargement de la photo:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erreur lors du téléchargement de la photo',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}