<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime', 100);
            $table->unsignedInteger('size')->default(0);
            $table->unsignedInteger('width')->default(0);
            $table->unsignedInteger('height')->default(0);
            // Stored so the layout can reserve the exact box before the image
            // loads - this is what keeps mobile scrolling free of layout shift.
            $table->decimal('aspect_ratio', 8, 4)->default(1);
            $table->string('dominant_color', 7)->default('#e6e4de');
            $table->string('alt')->nullable();
            $table->string('caption')->nullable();
            $table->timestamp('taken_at')->nullable();
            // width => relative path, per format. See MediaService.
            $table->json('variants')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
