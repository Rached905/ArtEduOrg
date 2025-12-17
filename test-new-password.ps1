# Script pour tester avec le nouveau mot de passe d'application Gmail

Write-Host "=== Test avec votre nouveau mot de passe Gmail ===" -ForegroundColor Cyan
Write-Host ""

$email = Read-Host "Entrez votre email Gmail (défaut: mohamedzayen053@gmail.com)"
if ([string]::IsNullOrWhiteSpace($email)) {
    $email = "mohamedzayen053@gmail.com"
}

Write-Host ""
Write-Host "IMPORTANT: Collez votre NOUVEAU mot de passe d'application Gmail" -ForegroundColor Yellow
Write-Host "(16 caractères, sans espaces)" -ForegroundColor Gray
Write-Host ""
$password = Read-Host "Collez votre nouveau mot de passe d'application" -AsSecureString
$passwordPlain = [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($password))

# Supprimer les espaces si présents
$passwordPlain = $passwordPlain -replace '\s', ''

if ([string]::IsNullOrWhiteSpace($passwordPlain)) {
    Write-Host "ERREUR: Le mot de passe ne peut pas être vide!" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "Configuration du DSN avec le nouveau mot de passe..." -ForegroundColor Yellow

# Format correct
$dsn = "smtp://smtp.gmail.com:587?encryption=tls&auth_mode=login&username=$email&password=$passwordPlain"

# Mettre à jour le .env
$lines = Get-Content .env
$newLines = @()
$mailerDsnAdded = $false

foreach ($line in $lines) {
    if ($line -match '^\s*MAILER_DSN\s*=') {
        if (-not $mailerDsnAdded) {
            $newLines += "MAILER_DSN=`"$dsn`""
            $mailerDsnAdded = $true
            Write-Host "✓ Ligne MAILER_DSN mise à jour dans .env" -ForegroundColor Green
        }
    } else {
        $newLines += $line
    }
}

if (-not $mailerDsnAdded) {
    $newLines += ""
    $newLines += "MAILER_DSN=`"$dsn`""
    Write-Host "✓ Ligne MAILER_DSN ajoutée dans .env" -ForegroundColor Green
}

$newLines | Set-Content .env

Write-Host ""
Write-Host "Test de l'envoi d'email..." -ForegroundColor Yellow
Write-Host ""

$recipientEmail = Read-Host "Entrez l'adresse email de destination (défaut: $email)"
if ([string]::IsNullOrWhiteSpace($recipientEmail)) {
    $recipientEmail = $email
}

Write-Host ""
php bin/console app:notify-expiring-contracts --email=$recipientEmail --days=30

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "✓✓✓ SUCCÈS! Email envoyé avec succès! ✓✓✓" -ForegroundColor Green
    Write-Host ""
    Write-Host "Vérifiez votre boîte de réception: $recipientEmail" -ForegroundColor Cyan
    Write-Host "(Vérifiez aussi les spams si vous ne le voyez pas)" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Le mot de passe a été sauvegardé dans votre fichier .env" -ForegroundColor Green
} else {
    Write-Host ""
    Write-Host "✗ Échec de l'envoi" -ForegroundColor Red
    Write-Host ""
    Write-Host "Vérifiez:" -ForegroundColor Yellow
    Write-Host "1. Que vous avez bien copié le mot de passe d'application (16 caractères)" -ForegroundColor White
    Write-Host "2. Que la validation en 2 étapes est activée sur votre compte Gmail" -ForegroundColor White
    Write-Host "3. Que vous avez créé le mot de passe d'application depuis: https://myaccount.google.com/apppasswords" -ForegroundColor White
    Write-Host "4. Que le mot de passe n'a pas d'espaces" -ForegroundColor White
    Write-Host ""
    Write-Host "Si le problème persiste, essayez de créer un autre mot de passe d'application." -ForegroundColor Yellow
}

