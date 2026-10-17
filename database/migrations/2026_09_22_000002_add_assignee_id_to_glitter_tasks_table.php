<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('glitter_tasks', function (Blueprint $table): void {
            $table->foreignId('assignee_id')
                ->nullable()
                ->after('created_by')
                ->constrained('users')
                ->nullOnDelete();
            $table->index('assignee_id');
        });
    }

    public function down(): void
    {
        Schema::table('glitter_tasks', function (Blueprint $table): void {
            $table->dropForeign(['assignee_id']);
            $table->dropIndex(['assignee_id']);
            $table->dropColumn('assignee_id');
        });
    }
};
