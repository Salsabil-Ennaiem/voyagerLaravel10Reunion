<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organisation extends Model
{
    use HasFactory;

    protected $table = 'organisations';

    protected $fillable = [
        'nom',
        'description',
        'email_contact',
        'adresse',
        'chef_organisation_id',
        'image',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function chef(): BelongsTo
    {
        return $this->belongsTo(User::class, 'chef_organisation_id');
    }

    public function reunions(): HasMany
    {
        return $this->hasMany(Reunion::class);
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'membres', 'organisation_id', 'compte_id')
                    ->withPivot('fonction', 'description');
    }


    public function activeOrganisation()
    {
        return $this->members()
            ->wherePivot('is_active', true)
            ->first();
    }

    public function getActiveOrganisationId()
    {
        return $this->activeOrganisation()?->id;
    }

    /**
     * Get the full path to the organisation image (if stored in storage)
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }
 return $this->image; // ← most common when storing full URL
        // Adjust according to your storage setup
        // return asset('storage/organisations/' . $this->image);
    }
}