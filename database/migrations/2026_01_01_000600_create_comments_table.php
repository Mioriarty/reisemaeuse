<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->string('author_name');
            // Never rendered publicly; only so we can reply privately.
            $table->string('author_email')->nullable();
            $table->text('body');
            // Rate limiting only. Pruned after 7 days by the scheduler, so we
            // do not keep anything personal around longer than we need it.
            $table->string('ip_hash', 64)->nullable()->index();
            $table->timestamps();

            $table->index(['post_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
