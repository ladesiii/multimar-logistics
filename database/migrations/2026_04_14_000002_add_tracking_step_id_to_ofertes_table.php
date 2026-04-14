<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ofertes') || Schema::hasColumn('ofertes', 'tracking_step_id')) {
            return;
        }

        Schema::table('ofertes', function (Blueprint $table) {
            $table->integer('tracking_step_id')->nullable();
            $table->foreign('tracking_step_id')
                ->references('id')
                ->on('tracking_steps')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ofertes') || ! Schema::hasColumn('ofertes', 'tracking_step_id')) {
            return;
        }

        Schema::table('ofertes', function (Blueprint $table) {
            $table->dropForeign(['tracking_step_id']);
            $table->dropColumn('tracking_step_id');
        });
    }
};
