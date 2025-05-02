<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SetupStorage extends Command
{
    protected $signature = 'storage:setup';
    protected $description = 'Configure le stockage pour les photos de profil';

    public function handle()
    {
        $this->info('Configuration du stockage...');

        // Créer le dossier profiles s'il n'existe pas
        if (!Storage::disk('public')->exists('profiles')) {
            Storage::disk('public')->makeDirectory('profiles');
            $this->info('Dossier profiles créé avec succès.');
        } else {
            $this->info('Le dossier profiles existe déjà.');
        }

        // Vérifier les permissions
        $path = Storage::disk('public')->path('profiles');
        if (is_writable($path)) {
            $this->info('Les permissions du dossier sont correctes.');
        } else {
            $this->warn('Le dossier n\'est pas accessible en écriture. Veuillez vérifier les permissions.');
        }

        // Créer le lien symbolique s'il n'existe pas
        if (!file_exists(public_path('storage'))) {
            $this->call('storage:link');
            $this->info('Lien symbolique créé avec succès.');
        } else {
            $this->info('Le lien symbolique existe déjà.');
        }

        $this->info('Configuration terminée.');
    }
}