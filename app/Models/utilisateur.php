<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use TCG\Voyager\Models\Role; // Si tu utilises les rôles Voyager
// use TCG\Voyager\Traits\VoyagerUser; // Optionnel si tu veux intégration complète Voyager

class Utilisateur extends Authenticatable
{
    use Notifiable;

    protected $table = 'utilisateur';

    protected $primaryKey = 'cin_ou_passeport';

    public $incrementing = false; // Parce que la clé primaire est une string

    protected $keyType = 'string';

    protected $fillable = [
        'cin_ou_passeport',
        'nom',
        'prenom',
        'email',
        'telephone',
        'role',
        'mdp',
        'actif',
        'id_image',
    ];

    protected $hidden = [
        'mdp',
        'remember_token',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'email_verified_at' => 'datetime',
    ];

    // Laravel attend "password" comme champ pour l'auth
    public function getAuthPassword()
    {
        return $this->mdp;
    }

    // Optionnel : Relation avec une image (si tu gères via Voyager media)
    public function image()
    {
        return $this->belongsTo('TCG\Voyager\Models\Image', 'id_image');
    }

    // Si tu veux utiliser les rôles Voyager
    public function role()
    {
        return $this->belongsTo(Role::class, 'role', 'name'); // ou 'id' selon ta config
    }
}