<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('detailer')->after('name');
            $table->string('phone', 50)->nullable()->after('email');
            $table->string('title', 100)->nullable()->after('phone');
            $table->boolean('active')->default(true)->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'phone', 'title', 'active']);
        });
    }
};
