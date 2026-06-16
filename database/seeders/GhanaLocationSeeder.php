<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Database\Seeder;

class GhanaLocationSeeder extends Seeder
{
    public function run(): void
    {
        $ghana = Country::firstOrCreate(
            ['iso2' => 'GH'],
            ['name' => 'Ghana', 'phone_code' => '+233']
        );

        $regions = [
            'Greater Accra' => [
                'Accra', 'Tema', 'Ashaiman', 'Madina', 'Teshie', 'Nungua', 'Dansoman',
                'Adenta', 'Lashibi', 'Kasoa', 'Weija', 'Dome', 'Achimota', 'Legon',
                'Osu', 'Labadi', 'Cantonments', 'Airport Residential', 'East Legon',
                'Spintex', 'Baatsona', 'Boundary Road', 'Ashiaman',
            ],
            'Ashanti' => [
                'Kumasi', 'Obuasi', 'Ejisu', 'Konongo', 'Mampong', 'Bekwai',
                'Asante Mampong', 'Juaben', 'Agogo', 'Offinso', 'Effiduase',
                'Asokore Mampong', 'Suame', 'Bantama', 'Adum', 'Asafo',
            ],
            'Western' => [
                'Takoradi', 'Sekondi', 'Tarkwa', 'Axim', 'Prestea', 'Bogoso',
                'Agona Nkwanta', 'Elubo', 'Half Assini', 'Shama',
            ],
            'Central' => [
                'Cape Coast', 'Winneba', 'Kasoa', 'Mankessim', 'Saltpond',
                'Assin Fosu', 'Dunkwa-on-Offin', 'Elmina', 'Anomabo',
            ],
            'Eastern' => [
                'Koforidua', 'Nkawkaw', 'Suhum', 'Akim Oda', 'Akosombo',
                'Nsawam', 'Aburi', 'Somanya', 'Asamankese', 'Begoro',
            ],
            'Volta' => [
                'Ho', 'Keta', 'Hohoe', 'Aflao', 'Kpando', 'Denu',
                'Sogakope', 'Akatsi', 'Anloga',
            ],
            'Northern' => [
                'Tamale', 'Yendi', 'Salaga', 'Savelugu', 'Tolon',
                'Damongo', 'Bole', 'Gushegu',
            ],
            'Upper East' => [
                'Bolgatanga', 'Navrongo', 'Bawku', 'Zebilla', 'Paga', 'Sandema',
            ],
            'Upper West' => [
                'Wa', 'Lawra', 'Jirapa', 'Nandom', 'Tumu', 'Hamile',
            ],
            'Bono' => [
                'Sunyani', 'Berekum', 'Dormaa Ahenkro', 'Bechem', 'Wenchi',
            ],
            'Bono East' => [
                'Techiman', 'Nkoranza', 'Kintampo', 'Atebubu', 'Yeji',
            ],
            'Ahafo' => [
                'Goaso', 'Kukuom', 'Mim', 'Hwidiem', 'Akrodie',
            ],
            'Savannah' => [
                'Damongo', 'Bole', 'Sawla', 'Yapei', 'Buipe',
            ],
            'North East' => [
                'Nalerigu', 'Walewale', 'Gambaga', 'Chereponi',
            ],
            'Oti' => [
                'Dambai', 'Nkwanta', 'Jasikan', 'Kadjebi', 'Worawora',
            ],
            'Western North' => [
                'Sefwi Wiawso', 'Bibiani', 'Enchi', 'Juaboso', 'Sewfi Bekwai',
            ],
        ];

        foreach ($regions as $regionName => $cities) {
            $state = State::firstOrCreate(
                ['name' => $regionName, 'country_id' => $ghana->id],
                ['country_code' => 'GH']
            );

            foreach ($cities as $cityName) {
                City::firstOrCreate(
                    ['name' => $cityName, 'state_id' => $state->id],
                    ['country_id' => $ghana->id, 'country_code' => 'GH']
                );
            }
        }
    }
}
