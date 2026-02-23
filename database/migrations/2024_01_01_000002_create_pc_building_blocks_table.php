<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pc_building_blocks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('pc_canvas_id')->constrained('pc_canvases')->onDelete('cascade');
            $table->string('block_type');
            $table->string('label');
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->index(['pc_canvas_id', 'block_type'], 'pc_bb_canvas_type_idx');
            $table->index('uuid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pc_building_blocks');
    }
};
