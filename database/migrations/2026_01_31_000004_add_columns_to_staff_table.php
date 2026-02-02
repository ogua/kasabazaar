<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, ensure users table has a primary key (required for foreign key references)
        $usersPrimaryKey = DB::select("SHOW KEYS FROM users WHERE Key_name = 'PRIMARY'");
        if (empty($usersPrimaryKey)) {
            Schema::table('users', function (Blueprint $table) {
                $table->primary('id');
            });
        }

        // Ensure staff table has a primary key (required for foreign key references)
        $staffPrimaryKey = DB::select("SHOW KEYS FROM staff WHERE Key_name = 'PRIMARY'");
        if (empty($staffPrimaryKey)) {
            Schema::table('staff', function (Blueprint $table) {
                $table->primary('id');
            });
        }

        Schema::table('staff', function (Blueprint $table) {
            // Only add columns if they don't exist
            if (!Schema::hasColumn('staff', 'staff_role_id')) {
                $table->uuid('staff_role_id')->nullable()->after('branch_id');
            }
            if (!Schema::hasColumn('staff', 'user_id')) {
                $table->uuid('user_id')->nullable()->after('staff_role_id');
            }
            if (!Schema::hasColumn('staff', 'salary')) {
                $table->decimal('salary', 12, 2)->nullable()->after('position');
            }
            if (!Schema::hasColumn('staff', 'hire_date')) {
                $table->date('hire_date')->nullable()->after('salary');
            }
            if (!Schema::hasColumn('staff', 'employment_status')) {
                $table->enum('employment_status', ['active', 'inactive', 'terminated', 'on_leave'])->default('active')->after('hire_date');
            }
            if (!Schema::hasColumn('staff', 'employee_id')) {
                $table->string('employee_id')->unique()->nullable()->after('id');
            }
            if (!Schema::hasColumn('staff', 'notes')) {
                $table->text('notes')->nullable()->after('employment_status');
            }
            if (!Schema::hasColumn('staff', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Add foreign keys separately to avoid issues
        Schema::table('staff', function (Blueprint $table) {
            // Check if foreign keys already exist before adding
            $existingForeignKeys = collect(DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'staff'
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            "))->pluck('CONSTRAINT_NAME')->toArray();

            if (!in_array('staff_staff_role_id_foreign', $existingForeignKeys)) {
                $table->foreign('staff_role_id')->references('id')->on('staff_roles')->nullOnDelete();
            }
            if (!in_array('staff_user_id_foreign', $existingForeignKeys)) {
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            // Check and drop foreign keys if they exist
            $existingForeignKeys = collect(DB::select("
                SELECT CONSTRAINT_NAME
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'staff'
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            "))->pluck('CONSTRAINT_NAME')->toArray();

            if (in_array('staff_staff_role_id_foreign', $existingForeignKeys)) {
                $table->dropForeign(['staff_role_id']);
            }
            if (in_array('staff_user_id_foreign', $existingForeignKeys)) {
                $table->dropForeign(['user_id']);
            }
        });

        Schema::table('staff', function (Blueprint $table) {
            $columnsToDrop = [];
            foreach (['staff_role_id', 'user_id', 'salary', 'hire_date', 'employment_status', 'employee_id', 'notes', 'deleted_at'] as $column) {
                if (Schema::hasColumn('staff', $column)) {
                    $columnsToDrop[] = $column;
                }
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
