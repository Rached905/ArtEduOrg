# Script pour corriger automatiquement TOUTES les lignes MAILER_DSN incorrectes dans .env

Write-Host "=== Correction automatique du fichier .env ===" -ForegroundColor Cyan
Write-Host ""

$envFile = ".env"
if (-not (Test-Path $envFile)) {
    Write-Host "ERREUR: Le fichier .env n'existe pas!" -ForegroundColor Red
    exit 1
}

# Lire toutes les lignes
$lines = Get-Content $envFile

# Trouver toutes les lignes MAILER_DSN
$mailerLines = $lines | Select-String "MAILER_DSN"

if ($mailerLines) {
    Write-Host "Lignes MAILER_DSN trouvées:" -ForegroundColor Yellow
    $mailerLines | ForEach-Object { Write-Host $_.Line -ForegroundColor Gray }
    Write-Host ""
}

# Demander le mot de passe d'application
Write-Host "Pour corriger, vous devez créer un NOUVEAU mot de passe d'application Gmail:" -ForegroundColor Yellow
Write-Host "1. Allez sur: https://myaccount.google.com/apppasswords" -ForegroundColor Cyan
Write-Host "2. Créez un nouveau mot de passe d'application" -ForegroundColor Cyan
Write-Host "3. Copiez-le (16 caractères, sans espaces)" -ForegroundColor Cyan
Write-Host ""

$email = Read-Host "Entrez votre email Gmail (défaut: mohamedzayen053@gmail.com)"
if ([string]::IsNullOrWhiteSpace($email)) {
    $email = "mohamedzayen053@gmail.com"
}

$newPassword = Read-Host "Collez votre NOUVEAU mot de passe d'application Gmail (sans espaces)"

# Supprimer les espaces si présents
$newPassword = $newPassword -replace '\s', ''

if ([string]::IsNullOrWhiteSpace($newPassword)) {
    Write-Host "ERREUR: Le mot de passe ne peut pas être vide!" -ForegroundColor Red
    exit 1
}

# Format correct
$correctDsn = "MAILER_DSN=`"smtp://smtp.gmail.com:587?encryption=tls&auth_mode=login&username=$email&password=$newPassword`""

# Supprimer toutes les anciennes lignes MAILER_DSN et ajouter la nouvelle
$newLines = @()
$mailerDsnAdded = $false

foreach ($line in $lines) {
    if ($line -match '^\s*MAILER_DSN\s*=') {
        # Ignorer les anciennes lignes MAILER_DSN
        if (-not $mailerDsnAdded) {
            $newLines += $correctDsn
            $mailerDsnAdded = $true
        }
    } else {
        $newLines += $line
    }
}

# Si aucune ligne MAILER_DSN n'existait, l'ajouter à la fin
if (-not $mailerDsnAdded) {
    $newLines += ""
    $newLines += $correctDsn
}

# Sauvegarder le fichier
$newLines | Set-Content -Path $envFile

Write-Host ""
Write-Host "✓ Fichier .env corrigé avec succès!" -ForegroundColor Green
Write-Host ""
Write-Host "Nouvelle ligne MAILER_DSN:" -ForegroundColor Cyan
Write-Host $correctDsn -ForegroundColor White
Write-Host ""
Write-Host "Testez maintenant avec:" -ForegroundColor Yellow
Write-Host "php bin/console app:notify-expiring-contracts --email=$email --days=30" -ForegroundColor Green

