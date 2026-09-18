<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eine Stellschraube der Seite, abgelegt als Schluessel und Wert.
 *
 * Der Schluessel ist der Primaerschluessel, deshalb kein auto-increment und
 * kein Integer - sonst sucht Eloquent nach einer Spalte `id`.
 */
class Setting extends Model
{
    /** Das gewaehlte Titelbild der Startseite (eine Media-ID). */
    public const HOME_MEDIA = 'home_media_id';

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::find($key)?->value ?? $default;
    }

    /**
     * Legt den Wert an oder schreibt ihn um. null loescht die Einstellung,
     * damit "nicht gesetzt" und "auf null gesetzt" dasselbe bleiben.
     */
    public static function set(string $key, ?string $value): void
    {
        if ($value === null) {
            static::where('key', $key)->delete();

            return;
        }

        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
