<?php

namespace Database\Seeders;

use App\Models\DeliveryAddress;
use Database\Seeders\Concerns\SeedsEcommerceDefaults;
use Illuminate\Database\Seeder;

class DeliveryAddressSeeder extends Seeder
{
    use SeedsEcommerceDefaults;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = $this->ecommerceCustomers();
        $locations = $this->ghanaLocations();

        foreach ($customers as $customer) {
            if (DeliveryAddress::where('user_id', $customer->id)->exists()) {
                continue;
            }

            $addressCount = fake()->numberBetween(1, 2);

            for ($i = 0; $i < $addressCount; $i++) {
                $location = fake()->randomElement($locations);

                DeliveryAddress::create([
                    'user_id' => $customer->id,
                    'full_name' => $customer->name,
                    'phone' => $customer->phone ?? fake()->numerify('+2332#######'),
                    'alternative_phone' => fake()->boolean(30) ? fake()->numerify('+2332#######') : null,
                    'email' => $customer->email,
                    'country' => 'Ghana',
                    'region' => $location['region'],
                    'city' => fake()->randomElement($location['cities']),
                    'suburb' => fake()->citySuffix(),
                    'street' => fake()->streetName(),
                    'house_number' => (string) fake()->buildingNumber(),
                    'digital_address' => strtoupper(fake()->lexify('??')).'-'.fake()->numerify('###-####'),
                    'landmark' => fake()->boolean(40) ? 'Near '.fake()->company().' office' : null,
                    'postal_code' => null,
                    'latitude' => fake()->latitude(5.0, 6.2),
                    'longitude' => fake()->longitude(-0.5, 0.2),
                    'delivery_notes' => fake()->boolean(30) ? 'Please call on arrival.' : null,
                    'is_default' => $i === 0,
                ]);
            }
        }
    }
}
