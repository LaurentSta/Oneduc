<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutilEtat extends Model
{
    protected $fillable = [
        'cle',
        'actif',
        'modifie_par',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    public function modificateur()
    {
        return $this->belongsTo(User::class, 'modifie_par');
    }
}
