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

    // Relationships
    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    // Optional: scopes
    public function scopePlanifiees($query)
    {
        return $query->where('statut', 'planifiee');
    }

    public function scopeEnCours($query)
    {
        return $query->where('statut', 'en_cours');
    }

    public function scopeTerminees($query)
    {
        return $query->where('statut', 'terminee');
    }

    public function scopeBrouillons($query)
    {
        return $query->where('statut', 'brouillon');
    }

    // Optional: helper methods / accessors
    public function getIsFutureAttribute(): bool
    {
        return $this->date_debut->isFuture();
    }

    public function getDurationInMinutesAttribute(): ?int
    {
        if (!$this->date_fin) {
            return null;
        }

        return $this->date_debut->diffInMinutes($this->date_fin);
    }
}