<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashbook_director_account', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('date');
            $table->string('particulars');
            $table->decimal('op_balance', 12, 2)->default(0);
            $table->decimal('debit', 12, 2)->default(0);
            $table->decimal('credit', 12, 2)->default(0);
            $table->decimal('cl_balance', 12, 2)->default(0); // auto-computed
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashbook_director_account');
    }
};
