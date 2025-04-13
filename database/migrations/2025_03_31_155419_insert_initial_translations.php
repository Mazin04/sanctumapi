<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::table('translations')->insert([
            'key' => 'example1',
            'language' => 'en',
            'translation' => 'Example 1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    
        DB::table('translations')->insert([
            'key' => 'example1',
            'language' => 'es',
            'translation' => 'Ejemplo 1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    
    public function down()
    {
        // Opcionalmente puedes agregar código para revertir la migración si es necesario.
    }
    
};
