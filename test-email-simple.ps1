# Script simple pour tester avec votre email Gmail
# Remplacez VOTRE_EMAIL et MOT_DE_PASSE_APP par vos valeurs

$env:MAILER_DSN = "smtp://smtp.gmail.com:587?encryption=tls&auth_mode=login&username=VOTRE_EMAIL@gmail.com&password=MOT_DE_PASSE_APP"

# Remplacez cette adresse par votre email de test
$recipientEmail = "VOTRE_EMAIL_DE_TEST@example.com"

Write-Host "Envoi de l'email de test..." -ForegroundColor Yellow
php bin/console app:notify-expiring-contracts --email=$recipientEmail --days=30

Write-Host ""
Write-Host "Vérifiez votre boîte de réception!" -ForegroundColor Green

