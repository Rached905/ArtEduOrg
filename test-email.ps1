# Script PowerShell pour tester l'envoi d'email
# Utilisez ce script pour configurer et tester l'envoi d'emails

Write-Host "=== Configuration de l'envoi d'email ===" -ForegroundColor Cyan
Write-Host ""

# Option 1: Gmail
Write-Host "Option 1: Gmail" -ForegroundColor Yellow
Write-Host "Pour utiliser Gmail, vous devez créer un 'Mot de passe d'application':" -ForegroundColor White
Write-Host "1. Allez sur https://myaccount.google.com/apppasswords" -ForegroundColor Cyan
Write-Host "2. Créez un mot de passe d'application" -ForegroundColor Cyan
Write-Host "3. Utilisez ce mot de passe dans la commande ci-dessous" -ForegroundColor Cyan
Write-Host ""
$gmailEmail = Read-Host "Entrez votre email Gmail"
$gmailPassword = Read-Host "Entrez le mot de passe d'application Gmail" -AsSecureString
$gmailPasswordPlain = [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($gmailPassword))

$gmailDsn = "smtp://smtp.gmail.com:587?encryption=tls&auth_mode=login&username=$gmailEmail&password=$gmailPasswordPlain"
$env:MAILER_DSN = $gmailDsn

Write-Host ""
Write-Host "MAILER_DSN configuré pour Gmail" -ForegroundColor Green
Write-Host ""

# Demander l'email de destination
$recipientEmail = Read-Host "Entrez l'adresse email où vous voulez recevoir le test"
$days = Read-Host "Nombre de jours avant expiration (défaut: 30)" 
if ([string]::IsNullOrWhiteSpace($days)) { $days = 30 }

Write-Host ""
Write-Host "Envoi de l'email..." -ForegroundColor Yellow
php bin/console app:notify-expiring-contracts --email=$recipientEmail --days=$days

Write-Host ""
Write-Host "=== Test terminé ===" -ForegroundColor Green
Write-Host "Vérifiez votre boîte de réception (et les spams)!" -ForegroundColor Cyan

