<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reunion extends Model
{
    use HasFactory;

    protected $table = 'reunions';


    protected $fillable = [
        'objet',
        'description',
        'ordre_du_jour',
        'date_debut',
        'date_fin',
        'lieu',
        'type',
        'statut',
        'organisation_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_debut' => 'datetime',
        'date_fin'   => 'datetime',
        'type'       => 'string',
        'statut'     => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Default attribute values
     *
     * @var array
     */
    protected $attributes = [
        'type'   => 'presentiel',
        'statut' => 'brouillon',
    ];

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function invitations()
    {
        return $this->hasMany(Invitation::class);
    }

}