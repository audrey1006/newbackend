<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DistrictSeeder extends Seeder
{
    public function run(): void
    {
        // Récupérer toutes les villes
        $cities = DB::table('cities')->get();

        foreach ($cities as $city) {
            // Pour Douala
            if ($city->name === 'Douala') {
                $districts = [
                    'Akwa',
                    'Bonanjo',
                    'Deido',
                    'Bepanda',
                    'New-Bell',
                    'Bonapriso',
                    'Bonaberi',
                    'Makepe',
                ];
            }
            // Pour Yaoundé
            else if ($city->name === 'Yaoundé') {
                $districts = [
                    'Bastos',
                    'Nlongkak',
                    'Mvan',
                    'Nsam',
                    'Mvog-Mbi',
                    'Biyem-Assi',
                    'Mimboman',
                    'Ngoa-Ekelle',
                ];
            }
            // Pour les autres villes, créer des districts génériques
            else {
                $districts = [
                    'Centre-ville',
                    'Quartier 1',
                    'Quartier 2',
                    'Quartier 3',
                    'Quartier 4',
                ];
            }

            // Insérer les districts pour chaque ville
            foreach ($districts as $district) {
                DB::table('districts')->insert([
                    'city_id' => $city->city_id,
                    'name' => $district,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}