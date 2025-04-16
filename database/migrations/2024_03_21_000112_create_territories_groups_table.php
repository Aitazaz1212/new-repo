<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('territories_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->onDelete('set null');
            $table->timestamps();

            // Add index for warehouse relationship
            $table->index('warehouse_id');
        });

        // Add foreign key to territories table for group_id
        Schema::table('territories', function (Blueprint $table) {
            $table->foreignId('group_id')->nullable()->change();
            $table->foreign('group_id')
                ->references('id')
                ->on('territories_groups')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        // Remove foreign key from territories table first
        Schema::table('territories', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
        });

        Schema::dropIfExists('territories_groups');
    }
}; 