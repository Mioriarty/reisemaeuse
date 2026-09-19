<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Einzelne Stellschrauben der Seite, die sonst nirgends hingehoeren - bisher
 * nur das Titelbild der Startseite. Bewusst Schluessel und Wert statt einer
 * Tabelle mit einer Zeile und vielen Spalten: jede weitere Einstellung ist
 * damit ein Eintrag und keine Migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
