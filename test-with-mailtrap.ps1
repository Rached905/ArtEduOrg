# Script pour tester avec Mailtrap (plus facile que Gmail)

Write-Host "=== Test avec Mailtrap (Alternative a Gmail) ===" -ForegroundColor Cyan
Write-Host ""
Write-Host "Mailtrap est un service gratuit pour tester les emails sans envoyer de vrais emails." -ForegroundColor Yellow
Write-Host "Les emails seront captures dans votre boite Mailtrap au lieu d'etre envoyes." -ForegroundColor Yellow
Write-Host ""
Write-Host "Etapes:" -ForegroundColor White
Write-Host "1. Creez un compte gratuit sur: https://mailtrap.io" -ForegroundColor Cyan
Write-Host "2. Allez dans Email Testing > Inboxes > SMTP Settings" -ForegroundColor Cyan
Write-Host "3. Copiez les identifiants SMTP fournis" -ForegroundColor Cyan
Write-Host ""

$useMailtrap = Read-Host "Voulez-vous utiliser Mailtrap maintenant? (O/N)"
if ($useMailtrap -ne "O" -and $useMailtrap -ne "o") {
    Write-Host "Test annule." -ForegroundColor Yellow
    exit 0
}

Write-Host ""
$mailtrapUser = Read-Host "Entrez votre username Mailtrap"
$mailtrapPass = Read-Host "Entrez votre password Mailtrap" -AsSecureString
$mailtrapPassPlain = [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($mailtrapPass))

# Format Mailtrap
$dsn = "smtp://smtp.mailtrap.io:2525?encryption=tls&auth_mode=login&username=$mailtrapUser&password=$mailtrapPassPlain"

# Mettre a jour le .env
$lines = Get-Content .env
$newLines = @()
$mailerDsnAdded = $false

foreach ($line in $lines) {
    if ($line -match '^\s*MAILER_DSN\s*=') {
        if (-not $mailerDsnAdded) {
            $newLines += "MAILER_DSN=`"$dsn`""
            $mailerDsnAdded = $true
        }
    } else {
        $newLines += $line
    }
}

if (-not $mailerDsnAdded) {
    $newLines += ""
    $newLines += "MAILER_DSN=`"$dsn`""
}

$newLines | Set-Content .env

Write-Host ""
Write-Host "Configuration Mailtrap ajoutee dans .env" -ForegroundColor Green
Write-Host ""

$recipientEmail = Read-Host "Entrez l'adresse email de test (sera capturee dans Mailtrap)"
if ([string]::IsNullOrWhiteSpace($recipientEmail)) {
    $recipientEmail = "test@example.com"
}

Write-Host ""
Write-Host "Envoi de l'email de test..." -ForegroundColor Yellow
php bin/console app:notify-expiring-contracts --email=$recipientEmail --days=30

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "SUCCES! Email envoye!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Verifiez votre boite Mailtrap:" -ForegroundColor Cyan
    Write-Host "https://mailtrap.io/inboxes" -ForegroundColor White
    Write-Host ""
    Write-Host "L'email devrait apparaitre dans votre inbox Mailtrap!" -ForegroundColor Green
} else {
    Write-Host ""
    Write-Host "Echec. Verifiez vos identifiants Mailtrap." -ForegroundColor Red
}
