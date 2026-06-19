<?php
foreach(\App\Models\User::where('role','boutiquier')->get() as $u) { 
    echo $u->nom_utilisateur . ' - boutique ' . $u->boutique_id . "\n"; 
}
