# Configuration de l'envoi d'emails

## Test de la commande avec envoi d'email

### Option 1 : Utiliser Mailpit (Recommandé pour le développement)

Si vous utilisez Docker avec Mailpit (déjà configuré dans `compose.override.yaml`) :

```powershell
# Définir le DSN pour Mailpit
$env:MAILER_DSN="smtp://localhost:1025"

# Exécuter la commande avec votre email
php bin/console app:notify-expiring-contracts --email=votre@email.com
```

Ensuite, ouvrez votre navigateur sur `http://localhost:8025` pour voir les emails reçus dans Mailpit.

### Option 2 : Utiliser Gmail (Pour les tests)

```powershell
# Configurer avec Gmail
$env:MAILER_DSN="smtp://smtp.gmail.com:587?encryption=tls&auth_mode=login&username=VOTRE_EMAIL@gmail.com&password=VOTRE_MOT_DE_PASSE_APP"

# Exécuter la commande
php bin/console app:notify-expiring-contracts --email=votre@email.com
```

**Note :** Pour Gmail, vous devez utiliser un "Mot de passe d'application" et non votre mot de passe habituel.

### Option 3 : Configuration permanente dans .env

Créez ou modifiez le fichier `.env.local` :

```env
# Pour Mailpit (développement)
MAILER_DSN=smtp://localhost:1025

# Pour Gmail (production/test)
# MAILER_DSN=smtp://smtp.gmail.com:587?encryption=tls&auth_mode=login&username=EMAIL&password=PASSWORD

# Pour null (pas d'envoi réel)
# MAILER_DSN=null://null
```

## Utilisation de la commande

```bash
# Afficher l'aide
php bin/console app:notify-expiring-contracts --help

# Par défaut (30 jours, pas d'email)
php bin/console app:notify-expiring-contracts

# Avec envoi d'email à une adresse spécifique
php bin/console app:notify-expiring-contracts --email=votre@email.com

# Personnaliser le nombre de jours
php bin/console app:notify-expiring-contracts --days=7 --email=votre@email.com

# Changer l'expéditeur
php bin/console app:notify-expiring-contracts --email=votre@email.com --from=noreply@monsite.com
```

## Exemples de DSN pour différents services

- **Mailpit** : `smtp://localhost:1025`
- **Gmail** : `smtp://smtp.gmail.com:587?encryption=tls&auth_mode=login&username=EMAIL&password=PASSWORD`
- **Outlook** : `smtp://smtp-mail.outlook.com:587?encryption=tls&auth_mode=login&username=EMAIL&password=PASSWORD`
- **SendGrid** : `smtp://smtp.sendgrid.net:587?encryption=tls&auth_mode=login&username=apikey&password=API_KEY`
- **Mailtrap** : `smtp://smtp.mailtrap.io:2525?encryption=tls&auth_mode=login&username=USERNAME&password=PASSWORD`

