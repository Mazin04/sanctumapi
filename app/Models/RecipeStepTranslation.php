<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecipeStepTranslation extends Model
{
    use HasFactory;

    public function recipeStep()
    {
        return $this->belongsTo(RecipeStep::class);
    }
}
