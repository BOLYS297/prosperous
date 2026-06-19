<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogActivite extends Model
{
    protected $table = 'log_activites'; // Optionnel si le nom est devinable, mais on s'assure
    protected $fillable = ['user_id', 'action', 'description'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getActionLabelAttribute()
    {
        $action = $this->action;
        if (!$action) {
            return 'Action inconnue';
        }

        return match (true) {
            str_contains($action, 'connexion') && $action === 'connexion' => 'Connexion',
            str_contains($action, 'deconnexion') && $action === 'deconnexion' => 'Déconnexion',
            str_contains($action, 'admin.rapports.depenses.approve') => 'Validation de dépense',
            str_contains($action, 'admin.rapports.depenses.reject') => 'Rejet de dépense',
            str_contains($action, 'admin.rapports.pertes.approve') => 'Validation de perte',
            str_contains($action, 'admin.rapports.pertes.reject') => 'Rejet de perte',
            str_contains($action, 'admin.users') => 'Gestion des utilisateurs',
            str_contains($action, 'admin.produits') => 'Gestion des produits',
            str_contains($action, 'admin.fournisseurs') => 'Gestion des fournisseurs',
            str_contains($action, 'admin.achats') => 'Gestion des achats',
            str_contains($action, 'magasinier.depenses') => 'Déclaration de dépense/perte',
            str_contains($action, 'boutiquier.ventes') => 'Enregistrement de vente',
            default => ucfirst(str_replace(['.', '_'], [' ', ' '], $action)),
        };
    }

    public function getDescriptionLabelAttribute()
    {
        $description = $this->description ?? '';
        if (str_contains($description, 'Requête')) {
            return ucfirst($description);
        }

        return $description;
    }
}
