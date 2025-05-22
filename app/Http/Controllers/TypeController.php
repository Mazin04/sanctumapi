<?php

namespace App\Http\Controllers;

use App\Models\Type;
use App\Models\TypeTranslation;
use Illuminate\Http\Request;

class TypeController extends Controller
{
    public function getTypes(Request $request)
    {
        $lang = $request->input('lang', 'es');
        $types = Type::with(['translations' => function ($query) use ($lang) {
            $query->where('language', $lang);
        }])->get()
            ->map(function ($type) {
                return [
                    'id' => $type->id,
                    'name' => $type->translations->first()->name ?? null,
                ];
            });
        return response()->json($types);
    }
}
