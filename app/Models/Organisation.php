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

//scope

    public function scopeWithChef($query)
    {
        return $query->whereNotNull('chef_organisation_id');
    }

    public function scopeWithoutChef($query)
    {
        return $query->whereNull('chef_organisation_id');
    }

//Accessors / Helpers

    public function getHasChefAttribute(): bool
    {
        return $this->chef_organisation_id !== null;
    }

    /**
     * Get the full path to the organisation image (if stored in storage)
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }

        // Adjust according to your storage setup
        // Examples:

        // If using public disk
        // return asset('storage/organisations/' . $this->image);

        // If using custom path or S3 / Cloudinary / etc.
        return $this->image; // ← most common when storing full URL
    }

    /**
     * Short version of the name (useful for badges, avatars, etc.)
     */
    public function getShortNameAttribute(): string
    {
        $words = explode(' ', trim($this->nom));
        return count($words) >= 2
            ? strtoupper(substr($words[0], 0, 1) . substr(end($words), 0, 1))
            : strtoupper(substr($this->nom, 0, 2));
    }
}