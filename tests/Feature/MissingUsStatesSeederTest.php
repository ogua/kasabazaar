<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Database\Seeders\MissingUsStatesSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * MissingUsStatesSeeder adds Pennsylvania and Maine (and their cities) to the world data.
 */
class MissingUsStatesSeederTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_adds_pennsylvania_and_maine_with_their_cities(): void
    {
        $us = $this->unitedStates();

        $this->seed(MissingUsStatesSeeder::class);

        foreach (MissingUsStatesSeeder::STATES as $name => $cities) {
            $state = State::query()->where('country_id', $us->id)->where('name', $name)->first();

            $this->assertNotNull($state, "{$name} was not added.");
            $this->assertSame('US', $state->country_code);
            $this->assertSame(count(array_unique($cities)), $state->cities()->count());
            $this->assertTrue($state->cities()->where('country_id', $us->id)->where('country_code', 'US')->exists());
        }

        $this->assertTrue(City::query()->where('name', 'Philadelphia')->whereHas('state', fn ($q) => $q->where('name', 'Pennsylvania'))->exists());
        $this->assertTrue(City::query()->where('name', 'Portland')->whereHas('state', fn ($q) => $q->where('name', 'Maine'))->exists());
    }

    public function test_running_it_twice_adds_nothing_new(): void
    {
        $this->unitedStates();

        $this->seed(MissingUsStatesSeeder::class);
        $states = State::query()->count();
        $cities = City::query()->count();

        $this->seed(MissingUsStatesSeeder::class);

        $this->assertSame($states, State::query()->count());
        $this->assertSame($cities, City::query()->count());
    }

    public function test_an_existing_state_is_reused_and_only_its_missing_cities_are_added(): void
    {
        $us = $this->unitedStates();
        $maine = State::query()->create(['country_id' => $us->id, 'name' => 'MAINE', 'country_code' => 'US']);
        City::query()->create(['country_id' => $us->id, 'state_id' => $maine->id, 'country_code' => 'US', 'name' => 'portland']);

        $this->seed(MissingUsStatesSeeder::class);

        $this->assertSame(1, State::query()->where('country_id', $us->id)->whereRaw('LOWER(name) = ?', ['maine'])->count());
        $this->assertSame(1, $maine->cities()->whereRaw('LOWER(name) = ?', ['portland'])->count());
        $this->assertSame(count(MissingUsStatesSeeder::STATES['Maine']), $maine->cities()->count());
    }

    private function unitedStates(): Country
    {
        $existing = Country::query()->where('iso2', 'US')->first();

        if ($existing) {
            return $existing;
        }

        $id = Country::query()->toBase()->insertGetId([
            'iso2' => 'US',
            'iso3' => 'USA',
            'name' => 'United States',
            'status' => 1,
            'phone_code' => '1',
            'region' => 'Americas',
            'subregion' => 'Northern America',
        ]);

        return Country::query()->findOrFail($id);
    }
}
