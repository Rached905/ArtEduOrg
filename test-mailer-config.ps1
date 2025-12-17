# Script pour tester la configuration Gmail
# Le mot de passe d'application Gmail ne doit PAS avoir d'espaces
# Si vous voyez "rtqj kvly vsbz krcd", supprimez les espaces : "rtqj kvly vsbz krcd" devient "rtqjkvlyvsbzkrcd"

Write-Host "=== Configuration Gmail pour Symfony Mailer ===" -ForegroundColor Cyan
Write-Host ""

# Format correct pour Gmail avec Symfony Mailer
$email = "mohamedzayen053@gmail.com"
# IMPORTANT: Le mot de passe d'application Gmail ne doit PAS avoir d'espaces
# Si votre mot de passe est "rtqj kvly vsbz krcd", utilisez "rtqjkvlyvsbzkrcd" (sans espaces)
$password = "rtqjkvlyvsbzkrcd"  # Remplacez par votre mot de passe SANS espaces

$dsn = "smtp://smtp.gmail.com:587?encryption=tls&auth_mode=login&username=$email&password=$password"

Write-Host "Configuration du MAILER_DSN..." -ForegroundColor Yellow
$env:MAILER_DSN = $dsn

Write-Host "MAILER_DSN configuré!" -ForegroundColor Green
Write-Host ""

# Test de l'envoi
$recipientEmail = Read-Host "Entrez l'adresse email où vous voulez recevoir le test (ou appuyez sur Entrée pour utiliser $email)"
if ([string]::IsNullOrWhiteSpace($recipientEmail)) {
    $recipientEmail = $email
}

Write-Host ""
Write-Host "Envoi de l'email de test à $recipientEmail..." -ForegroundColor Yellow
php bin/console app:notify-expiring-contracts --email=$recipientEmail --days=30

Write-Host ""
Write-Host "=== Test terminé ===" -ForegroundColor Green
Write-Host "Vérifiez votre boîte de réception (et les spams)!" -ForegroundColor Cyan

