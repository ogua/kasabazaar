<?php

namespace Database\Seeders\Concerns;

use App\Models\Branch;
use App\Models\Staff;
use App\Models\StaffRole;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

trait SeedsEcommerceDefaults
{
    protected function ecommerceBranch(): Branch
    {
        return Branch::firstOrCreate(
            ['slug' => 'kasabazaar-main'],
            [
                'name' => 'Kasabazaar Main Branch',
                'country' => 'Ghana',
                'state' => 'Greater Accra',
                'address' => 'Spintex Road, Accra, Ghana',
                'email' => 'main@kasabazaar.com',
                'phone' => '+233200000000',
            ]
        );
    }

    protected function ecommerceAdmin(): User
    {
        $branch = $this->ecommerceBranch();

        $admin = User::firstOrCreate(
            ['email' => 'admin@kasabazaar.com'],
            [
                'name' => 'Kasabazaar Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'branch_id' => $branch->id,
                'email_verified_at' => now(),
            ]
        );

        $admin->branches()->syncWithoutDetaching([$branch->id]);

        return $admin;
    }

    protected function ecommerceDriver(): Staff
    {
        $branch = $this->ecommerceBranch();
        $driverRole = StaffRole::where('code', 'DRIVER')->first();

        return Staff::firstOrCreate(
            ['email' => 'driver@kasabazaar.com'],
            [
                'branch_id' => $branch->id,
                'staff_role_id' => $driverRole?->id,
                'name' => 'Kwame Mensah',
                'phone' => '+233241234567',
                'position' => 'Delivery Driver',
                'employment_status' => 'active',
                'hire_date' => now()->subYear(),
            ]
        );
    }

    /**
     * @return Collection<int, User>
     */
    protected function ecommerceCustomers(int $count = 12): Collection
    {
        $branch = $this->ecommerceBranch();
        $customers = new Collection;

        for ($i = 1; $i <= $count; $i++) {
            $customer = User::firstOrCreate(
                ['email' => "customer{$i}@kasabazaar.test"],
                [
                    'name' => fake()->name(),
                    'phone' => fake()->numerify('+2332#######'),
                    'password' => Hash::make('password'),
                    'role' => 'customer',
                    'branch_id' => $branch->id,
                    'email_verified_at' => now(),
                ]
            );

            $customer->branches()->syncWithoutDetaching([$branch->id]);
            $customers->push($customer);
        }

        return $customers;
    }

    /**
     * @return array<int, array{region: string, cities: array<int, string>}>
     */
    protected function ghanaLocations(): array
    {
        return [
            ['region' => 'Greater Accra', 'cities' => ['Accra', 'Tema', 'Madina', 'Adenta', 'Spintex']],
            ['region' => 'Ashanti', 'cities' => ['Kumasi', 'Obuasi', 'Ejisu']],
            ['region' => 'Western', 'cities' => ['Takoradi', 'Sekondi']],
            ['region' => 'Central', 'cities' => ['Cape Coast', 'Winneba']],
            ['region' => 'Eastern', 'cities' => ['Koforidua', 'Nkawkaw']],
        ];
    }
}
