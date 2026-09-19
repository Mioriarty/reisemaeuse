<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            // One of the patterns in App\Enums\BlockType.
            $table->string('type', 40);
            $table->unsignedInteger('position')->default(0);
            // Shape depends on the type: text, captions, media ids, variant flags.
            $table->json('data');
            $table->timestamps();

            $table->index(['post_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_blocks');
    }
};
