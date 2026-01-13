<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use TCG\Voyager\Traits\Translatable; // If you plan to use translations in Voyager
use Carbon\Carbon;

class Reunion extends Model
{
    use HasFactory;
    // use Translatable; // Uncomment if you enable translations for fields like objet, description, etc.

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'reunion';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'objet',
        'Description',
        'ordre_jour',
        'date_debut',
        'date_fin',
        'lieu',
        'type',
        'statut',
        'Presendent_id', // Foreign key to president (usually a User or custom model)
        'user_id',       // Foreign key to the creator/user
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'date_debut' => 'datetime',
        'date_fin'   => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Optional: If you want translated attributes with Voyager
     *
     * @var array
     */
    // protected $translatable = ['objet', 'Description', 'ordre_jour', 'lieu'];

    /**
     * Relationship: Réunion belongs to a User (creator)
     */
    public function user()
    {
        return $this->belongsTo(utilisateur::class, 'cin_ou_passeport');
    }

    /**
     * Relationship: Réunion belongs to a President (usually a User)
     */
    public function president()
    {
        return $this->belongsTo(utilisateur::class, 'Presendent_id');
    }

    /**
     * Accessor: Get formatted start date
     */
    public function getDateDebutFormattedAttribute()
    {
        return $this->date_debut ? Carbon::parse($this->date_debut)->format('d/m/Y H:i') : null;
    }

    /**
     * Accessor: Get formatted end date
     */
    public function getDateFinFormattedAttribute()
    {
        return $this->date_fin ? Carbon::parse($this->date_fin)->format('d/m/Y H:i') : null;
    }

    /**
     * Scope: Get reunions that overlap with a given date range
     */
    public function scopeOverlapping($query, Carbon $start, Carbon $end)
    {
        return $query->where(function ($q) use ($start, $end) {
            $q->where('date_debut', '<=', $end)
              ->where('date_fin', '>=', $start);
        });
    }
}