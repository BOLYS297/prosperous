# Hébergement de Prosperous sur un VPS Hostinger (Ubuntu + Docker + GitHub Actions)

Guide complet pour déployer l'application **Prosperous** (Laravel 12 / PHP 8.2 / MySQL /
Vite-Tailwind) sur un VPS Hostinger, avec mises à jour automatiques via GitHub Actions.

---

## 1. Architecture mise en place

Tout tourne dans Docker, orchestré par `docker-compose.yml` :

```
                Internet (HTTPS 443)
                        │
                 ┌──────▼───────┐
                 │    caddy     │  reverse-proxy + TLS Let's Encrypt auto
                 └──────┬───────┘
                        │ (réseau interne docker)
                 ┌──────▼───────┐
                 │     app      │  Nginx + PHP-FPM (le site)
                 └──────┬───────┘
        ┌───────────────┼────────────────┐
   ┌────▼────┐    ┌──────▼──────┐    ┌────▼─────┐
   │  queue  │    │  scheduler  │    │    db    │  MySQL 8
   │ worker  │    │ (cron L12)  │    │ (volume) │
   └─────────┘    └─────────────┘    └──────────┘
```

| Service     | Rôle                                                        |
|-------------|-------------------------------------------------------------|
| `app`       | Sert le site (Nginx + PHP-FPM, image construite via Dockerfile) |
| `queue`     | Traite les jobs (validation dépenses/pertes, rapports…)     |
| `scheduler` | Exécute les tâches planifiées (`shift:reminders`, etc.)     |
| `db`        | MySQL 8 (données persistées dans un volume Docker)          |
| `caddy`     | HTTPS automatique + reverse-proxy                           |

**Données persistantes** (jamais perdues lors d'une mise à jour) :
- `db_data` → base MySQL
- `storage_public` → images produits uploadées (`storage/app/public`)
- `storage_logs` → logs applicatifs
- `caddy_data` → certificats TLS

---

## 2. Prérequis

- Un **VPS Hostinger** sous **Ubuntu 22.04 ou 24.04** (plan **2 Go RAM minimum** recommandé :
  le build Vite/Composer consomme de la mémoire).
- Un **nom de domaine** pointant vers l'IP du VPS (enregistrement DNS **A**).
  Sans domaine, on peut tester en HTTP sur l'IP, mais le HTTPS auto nécessite un domaine.
- Le dépôt GitHub : `https://github.com/BOLYS297/prosperous.git`.

> 💡 À la commande du VPS sur Hostinger, vous pouvez choisir le template
> **« Ubuntu 24.04 with Docker »** : Docker est alors déjà installé (sautez l'étape 4.3).

---

## 3. Préparer le DNS

Dans la zone DNS de votre domaine (chez Hostinger ou votre registrar) :

| Type | Nom | Valeur            |
|------|-----|-------------------|
| A    | `@` | `IP_DU_VPS`       |
| A    | `www` (optionnel) | `IP_DU_VPS` |

Vérifiez la propagation : `ping votre-domaine.com` doit renvoyer l'IP du VPS.

---

## 4. Préparer le VPS

### 4.1 Connexion

```bash
ssh root@IP_DU_VPS
```

### 4.2 Mises à jour système + utilisateur dédié

```bash
apt update && apt upgrade -y

# Créer un utilisateur non-root pour déployer
adduser deployer
usermod -aG sudo deployer
```

### 4.3 Installer Docker (si non présent)

```bash
curl -fsSL https://get.docker.com | sh
usermod -aG docker deployer          # autoriser deployer à utiliser docker
```

Déconnectez-vous puis reconnectez-vous en tant que `deployer` :

```bash
exit
ssh deployer@IP_DU_VPS
docker --version && docker compose version   # vérification
```

### 4.4 Pare-feu

```bash
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw --force enable
```

### 4.5 (Plan 1–2 Go) Ajouter du swap pour sécuriser le build

```bash
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile && sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

---

## 5. Récupérer le code sur le VPS

```bash
sudo mkdir -p /opt/prosperous
sudo chown deployer:deployer /opt/prosperous
git clone https://github.com/BOLYS297/prosperous.git /opt/prosperous
cd /opt/prosperous
```

> Dépôt privé ? Utilisez un **deploy key** (clé SSH en lecture seule ajoutée dans
> *Settings → Deploy keys* du dépôt) et clonez via l'URL SSH `git@github.com:...`.

---

## 6. Configurer l'environnement (`.env`)

```bash
cp .env.production.example .env
nano .env
```

À renseigner impérativement :

```env
APP_URL=https://votre-domaine.com
APP_DOMAIN=votre-domaine.com          # utilisé par Caddy
ACME_EMAIL=vous@votre-domaine.com     # e-mail Let's Encrypt

DB_HOST=db                            # NE PAS changer (nom du service docker)
DB_DATABASE=prosperous
DB_USERNAME=prosperous                # surtout pas "root"
DB_PASSWORD=un_mot_de_passe_solide
DB_ROOT_PASSWORD=un_autre_mot_de_passe_solide

# SMTP réel pour les e-mails (rapports, alertes)
MAIL_HOST=...  MAIL_USERNAME=...  MAIL_PASSWORD=...
```

Laissez `APP_KEY=` vide pour l'instant (généré à l'étape suivante).

---

## 7. Premier déploiement (manuel)

```bash
cd /opt/prosperous

# 1. Construire l'image
docker compose build

# 2. Générer la clé d'application et la coller dans .env
docker compose run --rm app php artisan key:generate --show
#   -> copie la ligne "base64:..." et place-la dans APP_KEY= du .env
nano .env

# 3. Démarrer toute la stack
docker compose up -d

# 4. Initialiser la base + caches
docker compose exec -T app php artisan migrate --force
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:cache
docker compose exec -T app php artisan view:cache
```

Vérifiez :

```bash
docker compose ps           # tous les services "Up"
docker compose logs -f caddy   # doit montrer l'obtention du certificat TLS
```

Ouvrez `https://votre-domaine.com` 🎉

> Le `storage:link` (lien `public/storage`) est créé automatiquement au démarrage du
> conteneur par l'entrypoint — rien à faire.

---

## 8. Mises à jour automatiques avec GitHub Actions

Le workflow [`.github/workflows/deploy.yml`](.github/workflows/deploy.yml) se connecte
en SSH au VPS à chaque `push` sur `main` et exécute `deploy.sh`
(git pull → build → up → migrate → cache → restart workers).

### 8.1 Créer une clé SSH dédiée au déploiement

**Sur le VPS** (utilisateur `deployer`) :

```bash
ssh-keygen -t ed25519 -C "github-actions" -f ~/.ssh/gh_deploy -N ""
cat ~/.ssh/gh_deploy.pub >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
cat ~/.ssh/gh_deploy            # <-- clé PRIVÉE à copier dans GitHub (secret)
```

### 8.2 Déclarer les secrets dans GitHub

Dépôt → **Settings → Secrets and variables → Actions → New repository secret** :

| Secret        | Valeur                                            |
|---------------|---------------------------------------------------|
| `VPS_HOST`    | IP du VPS                                          |
| `VPS_USER`    | `deployer`                                         |
| `VPS_PORT`    | `22`                                               |
| `VPS_SSH_KEY` | contenu de `~/.ssh/gh_deploy` (la clé **privée**)  |

### 8.3 C'est prêt

À partir de maintenant, chaque `git push origin main` (depuis votre PC) déclenche
automatiquement le déploiement. Suivez-le dans l'onglet **Actions** du dépôt.

Vous pouvez aussi déployer **à la main** sur le VPS à tout moment :

```bash
cd /opt/prosperous && bash deploy.sh
```

---

## 9. Recommandé : confiance au reverse-proxy (vraie IP client)

L'app détecte déjà le HTTPS (via Caddy + Nginx). Pour que `request()->ip()` renvoie la
**vraie IP** du visiteur (utile pour les middlewares `CheckDevice` / `LogUserActivity`),
ajoutez dans `bootstrap/app.php`, dans le bloc `->withMiddleware(...)` :

```php
$middleware->trustProxies(at: '*');
```

Puis committez/poussez : le déploiement se fera tout seul.

---

## 10. Exploitation au quotidien

```bash
cd /opt/prosperous

# Voir l'état / les logs
docker compose ps
docker compose logs -f app
docker compose logs -f queue

# Console Laravel (tinker, artisan)
docker compose exec app php artisan tinker
docker compose exec app php artisan about

# Vider les caches après un changement de config manuel
docker compose exec app php artisan optimize:clear

# Redémarrer un service
docker compose restart queue
```

### Sauvegarde de la base (à automatiser via cron)

```bash
docker compose exec -T db \
  mysqldump -u root -p"$DB_ROOT_PASSWORD" prosperous \
  > ~/backup-prosperous-$(date +%F).sql
```

Exemple de cron quotidien (`crontab -e`) :

```cron
0 3 * * * cd /opt/prosperous && set -a && . ./.env && set +a && \
  docker compose exec -T db mysqldump -u root -p"$DB_ROOT_PASSWORD" prosperous \
  | gzip > /home/deployer/backups/prosperous-$(date +\%F).sql.gz
```

### Restaurer une sauvegarde

```bash
gunzip < backup.sql.gz | docker compose exec -T db mysql -u root -p"$DB_ROOT_PASSWORD" prosperous
```

---

## 11. Dépannage

| Symptôme | Piste |
|----------|-------|
| Certificat TLS non émis | Le domaine pointe-t-il bien sur l'IP ? Ports 80/443 ouverts ? `docker compose logs caddy` |
| Erreur 500 au premier accès | `APP_KEY` vide → regénérez (étape 7.2) ; vérifiez `docker compose logs app` |
| `SQLSTATE … Connection refused` | `DB_HOST` doit valoir `db` (pas `127.0.0.1`) |
| Images produits qui disparaissent | Ne pas supprimer le volume `storage_public` ; le `storage:link` est auto |
| Build qui échoue (mémoire) | Ajoutez du swap (étape 4.5) ou prenez un plan ≥ 2 Go |
| Modif de config non prise en compte | `docker compose exec app php artisan optimize:clear` puis re-cache |

---

## 12. Notes de sécurité

- `.env` n'est **jamais** dans l'image ni sur GitHub (il est monté en lecture seule).
- MySQL n'est **pas** exposé sur Internet (aucun port publié, réseau interne seulement).
- Pensez à désactiver la connexion SSH par mot de passe une fois la clé en place
  (`PasswordAuthentication no` dans `/etc/ssh/sshd_config`).
- Les scripts de debug à la racine (`debug_*.php`, `check_*.php`, `test_*.php`…) sont
  exclus de l'image via `.dockerignore` — ils ne seront pas servis en production.
```
