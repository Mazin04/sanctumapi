<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecipeTranslation extends Model
{
    use HasFactory;
    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    public function translations()
    {
        return $this->hasMany(RecipeStepTranslation::class);
    }
}
