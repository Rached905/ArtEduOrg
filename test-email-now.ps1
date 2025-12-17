# Script pour tester l'envoi d'email avec un nouveau mot de passe

Write-Host "=== Test d'envoi d'email ===" -ForegroundColor Cyan
Write-Host ""

Write-Host "IMPORTANT: Vous devez avoir un mot de passe d'application Gmail valide!" -ForegroundColor Yellow
Write-Host "Si vous n'en avez pas, créez-en un sur: https://myaccount.google.com/apppasswords" -ForegroundColor Cyan
Write-Host ""

$email = Read-Host "Entrez votre email Gmail (défaut: mohamedzayen053@gmail.com)"
if ([string]::IsNullOrWhiteSpace($email)) {
    $email = "mohamedzayen053@gmail.com"
}

$password = Read-Host "Collez votre mot de passe d'application Gmail (16 caractères, sans espaces)" -AsSecureString
$passwordPlain = [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($password))

# Supprimer les espaces
$passwordPlain = $passwordPlain -replace '\s', ''

if ([string]::IsNullOrWhiteSpace($passwordPlain)) {
    Write-Host "ERREUR: Le mot de passe ne peut pas être vide!" -ForegroundColor Red
    exit 1
}

# Configurer le DSN
$dsn = "smtp://smtp.gmail.com:587?encryption=tls&auth_mode=login&username=$email&password=$passwordPlain"
$env:MAILER_DSN = $dsn

Write-Host ""
Write-Host "Configuration du DSN..." -ForegroundColor Yellow
Write-Host "DSN configuré!" -ForegroundColor Green
Write-Host ""

$recipientEmail = Read-Host "Entrez l'adresse email de destination (défaut: $email)"
if ([string]::IsNullOrWhiteSpace($recipientEmail)) {
    $recipientEmail = $email
}

Write-Host ""
Write-Host "Envoi de l'email de test..." -ForegroundColor Yellow
Write-Host ""

php bin/console app:notify-expiring-contracts --email=$recipientEmail --days=30

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "✓✓✓ SUCCÈS! Email envoyé avec succès! ✓✓✓" -ForegroundColor Green
    Write-Host ""
    Write-Host "Vérifiez votre boîte de réception: $recipientEmail" -ForegroundColor Cyan
    Write-Host "(Vérifiez aussi les spams si vous ne le voyez pas)" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Pour rendre cette configuration permanente, ajoutez dans votre .env:" -ForegroundColor Cyan
    Write-Host "MAILER_DSN=`"$dsn`"" -ForegroundColor White
} else {
    Write-Host ""
    Write-Host "✗ Échec de l'envoi" -ForegroundColor Red
    Write-Host ""
    Write-Host "Vérifiez:" -ForegroundColor Yellow
    Write-Host "1. Que vous utilisez bien un mot de passe d'application (pas votre mot de passe Gmail normal)" -ForegroundColor White
    Write-Host "2. Que la validation en 2 étapes est activée sur votre compte Gmail" -ForegroundColor White
    Write-Host "3. Que le mot de passe est correct (16 caractères, sans espaces)" -ForegroundColor White
    Write-Host "4. Créez un NOUVEAU mot de passe d'application si nécessaire" -ForegroundColor White
}

