<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashbook_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('date');
            $table->string('pv_no')->nullable();
            $table->string('details');
            $table->string('chq_ref')->nullable(); // BL / Momo / Chq 000005 / null
            $table->decimal('bank_debit', 12, 2)->default(0);
            $table->decimal('momo_debit', 12, 2)->default(0);
            $table->decimal('bank_credit', 12, 2)->default(0);
            $table->decimal('momo_credit', 12, 2)->default(0);
            $table->decimal('bank_balance', 12, 2)->default(0); // auto-computed
            $table->decimal('momo_balance', 12, 2)->default(0); // auto-computed
            $table->string('cost_center'); // CashbookCostCenter enum value
            $table->tinyInteger('month'); // 1–12
            $table->smallInteger('year');

            // Receipt analysis columns
            $table->decimal('op_balance', 12, 2)->nullable();
            $table->decimal('sales', 12, 2)->nullable();
            $table->decimal('dir_transfer', 12, 2)->nullable();
            $table->decimal('shipping_fee', 12, 2)->nullable();
            $table->decimal('service_fee', 12, 2)->nullable();
            $table->decimal('momo_interest', 12, 2)->nullable();
            $table->decimal('property_management', 12, 2)->nullable();
            $table->decimal('refund', 12, 2)->nullable();
            $table->decimal('contra_receipt', 12, 2)->nullable();

            // Expenditure analysis columns
            $table->decimal('import_duty', 12, 2)->nullable();
            $table->decimal('shipping_expenses', 12, 2)->nullable();
            $table->decimal('salaries_wages', 12, 2)->nullable();
            $table->decimal('bank_charges', 12, 2)->nullable();
            $table->decimal('ssnit', 12, 2)->nullable();
            $table->decimal('paye', 12, 2)->nullable();
            $table->decimal('withholding_tax', 12, 2)->nullable();
            $table->decimal('momo_charges', 12, 2)->nullable();
            $table->decimal('contra_payment', 12, 2)->nullable();
            $table->decimal('transportation', 12, 2)->nullable();
            $table->decimal('materials', 12, 2)->nullable();
            $table->decimal('donation', 12, 2)->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['month', 'year']);
            $table->index(['date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashbook_entries');
    }
};
