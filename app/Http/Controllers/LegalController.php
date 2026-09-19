<?php

namespace App\Http\Controllers;

use App\Support\Seo;
use Illuminate\Support\Facades\View;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Impressum and Datenschutzerklärung.
 *
 * Both are legally required for a German site. The text lives in Blade
 * partials so it can be edited without touching React or rebuilding assets.
 */
class LegalController extends Controller
{
    public function imprint(): Response
    {
        Seo::set('Impressum');

        return Inertia::render('Legal', [
            'title' => 'Impressum',
            'html' => View::make('legal.impressum')->render(),
        ]);
    }

    public function privacy(): Response
    {
        Seo::set('Datenschutzerklärung');

        return Inertia::render('Legal', [
            'title' => 'Datenschutzerklärung',
            'html' => View::make('legal.datenschutz')->render(),
        ]);
    }
}
