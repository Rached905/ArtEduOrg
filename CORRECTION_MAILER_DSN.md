# Correction du MAILER_DSN pour Gmail

## ❌ Format INCORRECT (actuel dans votre .env)
```
MAILER_DSN="smtp://mohamedzayen053@gmail.com:rtqj kvly vsbz krcd@smtp.gmail.com:587"
```

## ✅ Format CORRECT pour Gmail
```
MAILER_DSN="smtp://smtp.gmail.com:587?encryption=tls&auth_mode=login&username=mohamedzayen053@gmail.com&password=rtqjkvlyvsbzkrcd"
```

## Points importants :

1. **Le mot de passe d'application Gmail ne doit PAS avoir d'espaces**
   - ❌ `rtqj kvly vsbz krcd` (avec espaces)
   - ✅ `rtqjkvlyvsbzkrcd` (sans espaces)

2. **Structure du DSN** :
   - `smtp://` : protocole
   - `smtp.gmail.com:587` : serveur et port
   - `?encryption=tls` : chiffrement TLS
   - `&auth_mode=login` : mode d'authentification
   - `&username=...` : votre email Gmail
   - `&password=...` : votre mot de passe d'application (SANS espaces)

3. **Comment obtenir le bon mot de passe d'application** :
   - Allez sur https://myaccount.google.com/apppasswords
   - Créez un nouveau mot de passe d'application
   - Copiez-le tel quel (sans espaces)
   - Si vous voyez "rtqj kvly vsbz krcd", supprimez les espaces manuellement

## Commande pour tester :

```powershell
# Configurez le DSN correctement
$env:MAILER_DSN="smtp://smtp.gmail.com:587?encryption=tls&auth_mode=login&username=mohamedzayen053@gmail.com&password=VOTRE_MOT_DE_PASSE_SANS_ESPACES"

# Testez l'envoi
php bin/console app:notify-expiring-contracts --email=mohamedzayen053@gmail.com --days=30
```

## Si ça ne fonctionne toujours pas :

1. Vérifiez que vous utilisez bien un **mot de passe d'application** et non votre mot de passe Gmail normal
2. Vérifiez que la validation en 2 étapes est activée sur votre compte Gmail
3. Essayez de créer un nouveau mot de passe d'application

