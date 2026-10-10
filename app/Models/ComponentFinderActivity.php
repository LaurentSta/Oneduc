<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Zone de clic enregistrée par un formateur (image + zones), réutilisable :
 * un jeu lancé pour un groupe (ComponentFinderSession) ou un bloc de leçon
 * en reçoit une copie.
 */
class ComponentFinderActivity extends Model
{
    protected $fillable = [
        'formateur_id',
        'title',
        'image_path',
        'zones',
    ];

    protected $casts = [
        'zones' => 'array',
    ];

    public function formateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'formateur_id');
    }

    public function getImageUrlAttribute(): string
    {
        return asset('storage/'.$this->image_path);
    }
}
