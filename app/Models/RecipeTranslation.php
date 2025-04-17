<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecipeTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'language',
        'name',
        'description',
    ];
    
    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    public function translations()
    {
        return $this->hasMany(RecipeStepTranslation::class);
    }
}
