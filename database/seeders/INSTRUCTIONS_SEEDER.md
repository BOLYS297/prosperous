# Instructions pour les Seeders de Produits et Stocks

## 🎯 Objectif
Insérer les produits et les stocks dans la Boutique 1 de manière simple et structurée.

## 📁 Fichiers créés

### 1. **ProduitSeeder.php**
- Crée tous les produits du système
- Structure : `nom`, `reference`, `prix_achat`, `prix_vente`, `image`
- La référence est unique et permet d'éviter les doublons

### 2. **StockBoutique1Seeder.php**
- Crée les stocks pour la Boutique 1
- Associe les produits aux quantités disponibles
- Dépend de ProduitSeeder (les produits doivent exister d'abord)

---

## 📝 Étapes pour utiliser les seeders

### Étape 1 : Modifier le fichier ProduitSeeder.php
Remplacez les exemples par vos vrais produits dans la section "AJOUTEZ VOS PRODUITS CI-DESSOUS" :

```php
[
    'nom' => 'Nom du produit',
    'reference' => 'REF-XXX',      // Unique et identifiant (ex: PNEU-001, BATT-001, etc)
    'prix_achat' => 0,              // En FCFA (ex: 25000)
    'prix_vente' => 0,              // En FCFA (ex: 35000)
    'image' => null,                // Laisser null pour l'instant
],
```

**Exemple complet** :
```php
[
    'nom' => 'Ampoule H4 12V 60/55W',
    'reference' => 'AMPOULE-001',
    'prix_achat' => 2500,
    'prix_vente' => 4500,
    'image' => null,
],
```

### Étape 2 : Modifier le fichier StockBoutique1Seeder.php
Complétez la section "AJOUTEZ VOS QUANTITÉS CI-DESSOUS" :

```php
'REF-XXX' => 0,  // Nom du produit
```

Les références doivent correspondre à celles du ProduitSeeder.

**Exemple** :
```php
'AMPOULE-001' => 20,  // Ampoule H4 12V 60/55W : 20 ampoules
```

### Étape 3 : Mettre à jour DatabaseSeeder.php
Ajoutez les nouveaux seeders dans le DatabaseSeeder.php :

```php
$this->call([
    AdminSeeder::class,
    ProduitSeeder::class,
    StockBoutique1Seeder::class,
]);
```

### Étape 4 : Exécuter les seeders
```bash
php artisan db:seed
```

Ou pour exécuter un seeder spécifique :
```bash
php artisan db:seed --class=ProduitSeeder
php artisan db:seed --class=StockBoutique1Seeder
```

---

## 📊 Structure des données

### Table `produits`
| Colonne | Type | Remarques |
|---------|------|-----------|
| id | INTEGER | Clé primaire auto-incrémentée |
| nom | STRING | Nom du produit |
| reference | STRING | Identifiant unique (ex: PNEU-001) |
| prix_achat | DECIMAL(15,2) | Prix d'achat en FCFA |
| prix_vente | DECIMAL(15,2) | Prix de vente en FCFA |
| image | STRING | URL/chemin de l'image (nullable) |
| created_at | TIMESTAMP | Créé automatiquement |
| updated_at | TIMESTAMP | Mis à jour automatiquement |

### Table `stocks`
| Colonne | Remarques |
|---------|-----------|
| id | Clé primaire |
| boutique_id | Référence à la boutique (Boutique 1 = id 1) |
| produit_id | Référence au produit |
| quantite | Quantité disponible |
| created_at | Créé automatiquement |
| updated_at | Mis à jour automatiquement |

**Contrainte** : Un seul stock par (boutique_id, produit_id)

---

## ⚠️ Points importants

1. **La référence doit être unique** : Utilisez des codes cohérents (ex: PNEU-001, BATT-001)
2. **Les prix en FCFA** : Assurez-vous d'utiliser les bons formats (entiers ou décimaux)
3. **Ordre d'exécution** : ProduitSeeder AVANT StockBoutique1Seeder
4. **Utiliser firstOrCreate()** : Évite les doublons si vous réexécutez les seeders
5. **Les images** : Laisser null pour maintenant, seront ajoutées ultérieurement via l'interface

---

## 🔄 Workflow complet

```
1. Remplir ProduitSeeder avec les produits
   ↓
2. Remplir StockBoutique1Seeder avec les quantités
   ↓
3. Mettre à jour DatabaseSeeder
   ↓
4. Exécuter : php artisan db:seed
   ↓
5. ✅ Données insérées dans la base de données
```

---

## 🆘 Dépannage

### "Boutique 1 non trouvée"
→ Exécutez d'abord `php artisan db:seed --class=DatabaseSeeder`

### "Aucun produit trouvé"
→ Exécutez `php artisan db:seed --class=ProduitSeeder` avant StockBoutique1Seeder

### "Référence en double"
→ Changez la référence du produit (elle doit être unique)

### Reset complet de la base
```bash
php artisan migrate:fresh --seed
```

---

## ✅ Validation après l'exécution

Vous pouvez vérifier l'insertion via :

```bash
# Voir tous les produits
php artisan tinker
>>> App\Models\Produit::all();

# Voir les stocks de la Boutique 1
>>> App\Models\Boutique::find(1)->stocks()->with('produit')->get();

# Voir le nombre total de produits
>>> App\Models\Produit::count();
```

---

## 📝 Notes

- Les seeders utilisent `firstOrCreate()` pour éviter les doublons
- Vous pouvez réexécuter les seeders sans crainte
- Les quantités peuvent être modifiées directement dans le StockBoutique1Seeder
- Une fois les produits en place, vous pourrez les gérer via l'interface web

Bonne chance avec l'insertion des données ! 🚀
