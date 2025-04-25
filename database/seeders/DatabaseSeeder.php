<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Désactiver la vérification des clés étrangères
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Vider les tables avant de les remplir
        DB::table('districts')->truncate();
        DB::table('cities')->truncate();
        DB::table('users')->truncate();

        // Réactiver la vérification des clés étrangères
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Créer un utilisateur admin par défaut
        User::create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Admin',
            'last_name' => 'System',
            'role' => 'admin'
        ]);

        // Créer les villes
        $cities = [
            'Douala',
            'Yaoundé',
            'Garoua',
            'Bamenda',
            'Maroua',
            'Nkongsamba',
            'Bafoussam',
            'Ngaoundéré',
            'Bertoua',
            'Loum'
        ];

        foreach ($cities as $city) {
            DB::table('cities')->insert([
                'name' => $city
            ]);
        }

        // Créer les districts pour chaque ville
        $douala = DB::table('cities')->where('name', 'Douala')->first();
        $yaounde = DB::table('cities')->where('name', 'Yaoundé')->first();

        // Districts de Douala
        $doualaDistricts = [
            'Akwa',
            'Bonanjo',
            'Deido',
            'Bepanda',
            'New-Bell',
            'Bonapriso',
            'Bonaberi',
            'Makepe'
        ];

        foreach ($doualaDistricts as $district) {
            DB::table('districts')->insert([
                'city_id' => $douala->city_id,
                'name' => $district
            ]);
        }

        // Districts de Yaoundé
        $yaoundeDistricts = [
            'Bastos',
            'Nlongkak',
            'Mvan',
            'Nsam',
            'Mvog-Mbi',
            'Biyem-Assi',
            'Mimboman',
            'Ngoa-Ekelle'
        ];

        foreach ($yaoundeDistricts as $district) {
            DB::table('districts')->insert([
                'city_id' => $yaounde->city_id,
                'name' => $district
            ]);
        }

        // Districts génériques pour les autres villes
        $otherCities = DB::table('cities')
            ->whereNotIn('name', ['Douala', 'Yaoundé'])
            ->get();

        foreach ($otherCities as $city) {
            $districts = [
                'Centre-ville',
                'Quartier 1',
                'Quartier 2',
                'Quartier 3',
                'Quartier 4'
            ];

            foreach ($districts as $district) {
                DB::table('districts')->insert([
                    'city_id' => $city->city_id,
                    'name' => $district
                ]);
            }
        }
    }
}
