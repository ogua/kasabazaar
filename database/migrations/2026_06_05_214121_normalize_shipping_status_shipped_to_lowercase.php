<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('shipments')->where('status', 'Shipped')->update(['status' => 'shipped']);
    }

    public function down(): void
    {
        DB::table('shipments')->where('status', 'shipped')->update(['status' => 'Shipped']);
    }
};
