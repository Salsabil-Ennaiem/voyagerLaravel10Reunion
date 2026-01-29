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
        'active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'active' => 'boolean',
    ];

  public function chef()
{
    return $this->belongsTo(User::class, 'chef_organisation_id');
}

public function members()
{
    return $this->belongsToMany(User::class, 'membres', 'organisation_id', 'compte_id')
                ->withPivot('fonction', 'description');
}

// Get members by role
public function getMembersByRole(string $role)
{
    return $this->members()->wherePivot('fonction', $role)->get();
}

// Get all members with their roles
public function getMembersWithRoles()
{
    return $this->members()->get()->map(function($member) {
        return [
            'user' => $member,
            'fonction' => $member->pivot->fonction,
            'description' => $member->pivot->description,
        ];
    });
}


    public function reunions(): HasMany
    {
        return $this->hasMany(Reunion::class);
    }
/*
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

    */
}