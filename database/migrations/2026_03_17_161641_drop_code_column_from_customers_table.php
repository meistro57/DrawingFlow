<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('customers', 'code')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique('customers_code_unique');
            $table->dropColumn('code');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('customers', 'code')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->string('code', 50)->unique()->nullable()->after('name');
        });
    }
};
