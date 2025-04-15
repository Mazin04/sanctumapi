<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Type extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * Get the recipes associated with the type.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function recipes()
    {
        return $this->belongsToMany(Recipe::class, 'recipe_types');
    }

    public function translations()
    {
        return $this->hasMany(TypeTranslation::class);
    }    
}
