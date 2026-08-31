<?php

use App\Models\ExpenseCategory;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        ExpenseCategory::updateOrCreate(
            ['code' => 'DEMURRAGE'],
            [
                'name' => 'Demurrage',
                'description' => 'Shipping-line demurrage and container detention charges',
                'is_active' => true,
            ]
        );
    }

    public function down(): void
    {
        ExpenseCategory::where('code', 'DEMURRAGE')->forceDelete();
    }
};
