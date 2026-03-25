<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drawing_requests', function (Blueprint $table): void {
            $table->index(
                ['assigned_to_user_id', 'status', 'required_date'],
                'dr_assignee_status_required_idx'
            );
        });

        Schema::table('notifications', function (Blueprint $table): void {
            $table->index(
                ['notifiable_type', 'notifiable_id', 'read_at'],
                'notifications_notifiable_read_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('drawing_requests', function (Blueprint $table): void {
            $table->dropIndex('dr_assignee_status_required_idx');
        });

        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropIndex('notifications_notifiable_read_idx');
        });
    }
};
