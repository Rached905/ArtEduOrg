# Script pour corriger l'authentification Gmail
# Le problème "530 Authentication Required" signifie que le mot de passe d'application n'est pas correct

Write-Host "=== Correction de l'authentification Gmail ===" -ForegroundColor Cyan
Write-Host ""

Write-Host "L'erreur '530 Authentication Required' indique que:" -ForegroundColor Yellow
Write-Host "- Le format du DSN est correct ✓" -ForegroundColor Green
Write-Host "- Mais l'authentification échoue ✗" -ForegroundColor Red
Write-Host ""

Write-Host "ÉTAPES À SUIVRE:" -ForegroundColor Cyan
Write-Host ""
Write-Host "1. Allez sur: https://myaccount.google.com/apppasswords" -ForegroundColor White
Write-Host "2. Assurez-vous que la validation en 2 étapes est activée" -ForegroundColor White
Write-Host "3. Créez un NOUVEAU mot de passe d'application:" -ForegroundColor White
Write-Host "   - Sélectionnez 'Autre' comme application" -ForegroundColor Gray
Write-Host "   - Nommez-le 'Symfony Mailer'" -ForegroundColor Gray
Write-Host "   - Copiez le mot de passe généré (16 caractères)" -ForegroundColor Gray
Write-Host ""

$newPassword = Read-Host "Collez votre NOUVEAU mot de passe d'application Gmail (sans espaces)"

# Supprimer les espaces si présents
$newPassword = $newPassword -replace '\s', ''

# Encoder le mot de passe pour l'URL (au cas où)
$encodedPassword = [System.Web.HttpUtility]::UrlEncode($newPassword)

Write-Host ""
Write-Host "Configuration du DSN..." -ForegroundColor Yellow

# Essayer d'abord sans encodage
$dsn1 = "smtp://smtp.gmail.com:587?encryption=tls&auth_mode=login&username=mohamedzayen053@gmail.com&password=$newPassword"
$env:MAILER_DSN = $dsn1

Write-Host "DSN configuré (sans encodage)" -ForegroundColor Green
Write-Host ""

$recipientEmail = Read-Host "Entrez l'adresse email de test (ou appuyez sur Entrée pour utiliser mohamedzayen053@gmail.com)"
if ([string]::IsNullOrWhiteSpace($recipientEmail)) {
    $recipientEmail = "mohamedzayen053@gmail.com"
}

Write-Host ""
Write-Host "Test de l'envoi..." -ForegroundColor Yellow
php bin/console app:notify-expiring-contracts --email=$recipientEmail --days=30

$result = $LASTEXITCODE
if ($result -eq 0) {
    Write-Host ""
    Write-Host "✓ SUCCÈS! Email envoyé!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Pour rendre cette configuration permanente, ajoutez dans votre .env:" -ForegroundColor Cyan
    Write-Host "MAILER_DSN=`"$dsn1`"" -ForegroundColor White
} else {
    Write-Host ""
    Write-Host "✗ Échec. Essayons avec le mot de passe encodé..." -ForegroundColor Yellow
    $dsn2 = "smtp://smtp.gmail.com:587?encryption=tls&auth_mode=login&username=mohamedzayen053@gmail.com&password=$encodedPassword"
    $env:MAILER_DSN = $dsn2
    php bin/console app:notify-expiring-contracts --email=$recipientEmail --days=30
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host ""
        Write-Host "✓ SUCCÈS avec mot de passe encodé!" -ForegroundColor Green
        Write-Host ""
        Write-Host "Pour rendre cette configuration permanente, ajoutez dans votre .env:" -ForegroundColor Cyan
        Write-Host "MAILER_DSN=`"$dsn2`"" -ForegroundColor White
    } else {
        Write-Host ""
        Write-Host "✗ Échec. Vérifiez:" -ForegroundColor Red
        Write-Host "  1. Que vous utilisez bien un mot de passe d'application (pas votre mot de passe Gmail)" -ForegroundColor Yellow
        Write-Host "  2. Que la validation en 2 étapes est activée" -ForegroundColor Yellow
        Write-Host "  3. Que le mot de passe est correct (16 caractères, sans espaces)" -ForegroundColor Yellow
    }
}

