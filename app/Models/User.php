<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Boutique;
use App\Models\HoraireConnexion;
use App\Models\SalaryPeriod;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nom_utilisateur',
        'email',
        'password',
        'role',
        'shift',
        'device_token',
        'boutique_id',
        'monthly_salary',
        'commission_percent',
        'solde_personnel',
    ];

    /** Mouvements du solde personnel (recettes, retraits, achats, dépenses). */
    public function soldeMouvements()
    {
        return $this->hasMany(AdminSoldeMouvement::class, 'admin_id');
    }

    /** Le mécanicien n'a pas de salaire de base : il est payé à la commission. */
    public function isMecanicien(): bool
    {
        return $this->role === 'mecanicien';
    }

    public function boutique()
    {
        return $this->belongsTo(Boutique::class);
    }

    public function horaires()
    {
        return $this->belongsToMany(HoraireConnexion::class, 'horaire_connexion_user');
    }

    public function salaryPeriods()
    {
        return $this->hasMany(SalaryPeriod::class);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
