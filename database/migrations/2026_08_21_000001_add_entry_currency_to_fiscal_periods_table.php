<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fiscal_periods', function (Blueprint $table) {
            // The currency a manually keyed year's balances were entered in. The
            // accountant's Ghana books are kept in GHS while the statements present in
            // USD, so without this the keyed figures would be read as USD and the
            // statement would be overstated by the exchange rate and mislabelled.
            // Derived years are unaffected — they translate per record.
            $table->string('entry_currency', 3)->default('USD')->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('fiscal_periods', function (Blueprint $table) {
            $table->dropColumn('entry_currency');
        });
    }
};
