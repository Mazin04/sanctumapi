<?php

namespace App\Http\Controllers;

use App\Models\Translation;
use Illuminate\Http\Request;

class TranslationController extends Controller
{
    public function getTranslations($language)
    {
        // Obtener todas las traducciones para un idioma
        $translations = Translation::where('language', $language)->get();

        // Convertir a un formato de clave => valor
        $translationsArray = $translations->pluck('translation', 'key')->toArray();

        return response()->json($translationsArray);
    }
}
