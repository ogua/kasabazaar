<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the existing foreign key that references 'users' table
        DB::statement('ALTER TABLE pickup_schedules DROP FOREIGN KEY pickup_schedules_assigned_to_foreign');

        // Add new foreign key that references 'staff' table
        DB::statement('ALTER TABLE pickup_schedules ADD CONSTRAINT pickup_schedules_assigned_to_foreign FOREIGN KEY (assigned_to) REFERENCES staff(id) ON DELETE SET NULL');
    }

    public function down(): void
    {
        // Drop the foreign key that references 'staff' table
        DB::statement('ALTER TABLE pickup_schedules DROP FOREIGN KEY pickup_schedules_assigned_to_foreign');

        // Restore the original foreign key that references 'users' table
        DB::statement('ALTER TABLE pickup_schedules ADD CONSTRAINT pickup_schedules_assigned_to_foreign FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL');
    }
};
