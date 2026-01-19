<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'participant_id',
        'reunion_id',
        'email',
        'statut',
        'commentaire',
        'date_presence',
        'note',
    ];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'participant_id');
    }

    public function reunion(): BelongsTo
    {
        return $this->belongsTo(Reunion::class);
    }
}
