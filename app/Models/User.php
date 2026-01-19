<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends \TCG\Voyager\Models\User
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     * Align with users table and additional utilisateur fields.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'password',
        'cin_ou_passeport',
        'nom',
        'prenom',
        'telephone',
        // 'role',
        'actif',
        'image',
        'role_id',
        // 'role', // Conflict with Voyager relationship
    ];

    /**
     * Accessor to ensure we get the Role object, not the string from the DB column 'role'
     * This fixes the "Call to a member function relationLoaded() on string" error.
     */
    public function getRoleAttribute()
    {
        return $this->getRelationValue('role');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'actif' => 'boolean',
    ];
}
