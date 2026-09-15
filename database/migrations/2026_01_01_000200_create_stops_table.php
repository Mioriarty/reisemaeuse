<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stops', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('country');
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->date('arrived_on')->nullable();
            $table->date('departed_on')->nullable();
            $table->text('note')->nullable();
            // The route polyline is drawn through the stops in this order.
            $table->unsignedInteger('position')->default(0)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stops');
    }
};
