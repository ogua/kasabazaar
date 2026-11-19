<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Database\Seeder;

/**
 * Adds the US states (and their main cities) that the imported world data is missing, so
 * receivers and clients in them can be picked in the state / city dropdowns.
 *
 * Safe to re-run: a state or city that already exists (same name, any case) is left alone,
 * and only the missing cities are added to it.
 *
 *   php artisan db:seed --class=MissingUsStatesSeeder
 */
class MissingUsStatesSeeder extends Seeder
{
    /**
     * @var array<string, list<string>>
     */
    public const STATES = [
        'Pennsylvania' => [
            'Philadelphia', 'Pittsburgh', 'Allentown', 'Erie', 'Reading', 'Scranton', 'Bethlehem', 'Lancaster',
            'Harrisburg', 'Altoona', 'York', 'State College', 'Wilkes-Barre', 'Chester', 'Norristown',
            'Easton', 'Lebanon', 'Hazleton', 'Williamsport', 'Johnstown', 'King of Prussia', 'Levittown',
            'Upper Darby', 'Bensalem', 'Lower Merion', 'Abington', 'Bristol', 'Cheltenham', 'Pottstown',
            'West Chester', 'Media', 'Doylestown', 'Exton', 'Phoenixville', 'Hershey', 'Carlisle',
            'Chambersburg', 'Gettysburg', 'Butler', 'New Castle', 'Monroeville', 'McKeesport',
            'Bethel Park', 'Cranberry Township', 'Greensburg', 'Washington', 'Sharon', 'Meadville',
            'Stroudsburg', 'Pottsville', 'Sunbury', 'Lock Haven', 'Indiana', 'Uniontown', 'Kingston',
        ],
        'Maine' => [
            'Portland', 'Lewiston', 'Bangor', 'South Portland', 'Auburn', 'Biddeford', 'Sanford', 'Saco',
            'Augusta', 'Westbrook', 'Waterville', 'Brunswick', 'Scarborough', 'Windham', 'Gorham',
            'York', 'Kennebunk', 'Falmouth', 'Cape Elizabeth', 'Orono', 'Presque Isle', 'Brewer',
            'Bath', 'Ellsworth', 'Caribou', 'Old Town', 'Rockland', 'Belfast', 'Bar Harbor', 'Camden',
            'Kittery', 'Freeport', 'Skowhegan', 'Farmington', 'Houlton', 'Gardiner', 'Hallowell',
            'Yarmouth', 'Topsham', 'Wells',
        ],
    ];

    public function run(): void
    {
        $country = Country::query()->where('iso2', 'US')->first();

        if ($country === null) {
            $this->command?->warn('United States not found in `countries` — seed the world data first (WorldSeeder).');

            return;
        }

        foreach (self::STATES as $stateName => $cities) {
            $state = State::query()
                ->where('country_id', $country->id)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($stateName)])
                ->first()
                ?? State::query()->create([
                    'country_id' => $country->id,
                    'name' => $stateName,
                    'country_code' => 'US',
                ]);

            $existing = City::query()
                ->where('state_id', $state->id)
                ->pluck('name')
                ->map(fn (string $name): string => mb_strtolower($name))
                ->all();

            $missing = collect($cities)
                ->unique()
                ->reject(fn (string $city): bool => in_array(mb_strtolower($city), $existing, true))
                ->map(fn (string $city): array => [
                    'country_id' => $country->id,
                    'state_id' => $state->id,
                    'country_code' => 'US',
                    'name' => $city,
                ])
                ->values()
                ->all();

            if ($missing !== []) {
                City::query()->insert($missing);
            }

            $this->command?->info(sprintf('%s: %s, %d cities added.', $stateName, $state->wasRecentlyCreated ? 'state added' : 'state already present', count($missing)));
        }
    }
}
