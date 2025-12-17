# Script pour corriger automatiquement le MAILER_DSN dans le fichier .env

Write-Host "=== Correction du fichier .env ===" -ForegroundColor Cyan
Write-Host ""

$envFile = ".env"
if (-not (Test-Path $envFile)) {
    Write-Host "ERREUR: Le fichier .env n'existe pas!" -ForegroundColor Red
    exit 1
}

# Lire le contenu du fichier
$content = Get-Content $envFile -Raw

# Vérifier si MAILER_DSN existe
if ($content -match 'MAILER_DSN\s*=') {
    Write-Host "Format actuel trouvé dans .env:" -ForegroundColor Yellow
    $content -match 'MAILER_DSN\s*="[^"]*"' | Out-Null
    if ($matches) {
        Write-Host $matches[0] -ForegroundColor Gray
    }
    Write-Host ""
    
    # Demander le nouveau mot de passe d'application
    Write-Host "Pour corriger, vous devez créer un NOUVEAU mot de passe d'application Gmail:" -ForegroundColor Yellow
    Write-Host "1. Allez sur: https://myaccount.google.com/apppasswords" -ForegroundColor Cyan
    Write-Host "2. Créez un nouveau mot de passe d'application" -ForegroundColor Cyan
    Write-Host "3. Copiez-le (16 caractères, sans espaces)" -ForegroundColor Cyan
    Write-Host ""
    
    $newPassword = Read-Host "Collez votre NOUVEAU mot de passe d'application Gmail (sans espaces)"
    
    # Supprimer les espaces si présents
    $newPassword = $newPassword -replace '\s', ''
    
    if ([string]::IsNullOrWhiteSpace($newPassword)) {
        Write-Host "ERREUR: Le mot de passe ne peut pas être vide!" -ForegroundColor Red
        exit 1
    }
    
    # Format correct
    $correctDsn = "MAILER_DSN=`"smtp://smtp.gmail.com:587?encryption=tls&auth_mode=login&username=mohamedzayen053@gmail.com&password=$newPassword`""
    
    # Remplacer la ligne MAILER_DSN
    $newContent = $content -replace 'MAILER_DSN\s*="[^"]*"', $correctDsn
    
    # Sauvegarder le fichier
    Set-Content -Path $envFile -Value $newContent -NoNewline
    
    Write-Host ""
    Write-Host "✓ Fichier .env corrigé avec succès!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Nouvelle ligne MAILER_DSN:" -ForegroundColor Cyan
    Write-Host $correctDsn -ForegroundColor White
    Write-Host ""
    Write-Host "Testez maintenant avec:" -ForegroundColor Yellow
    Write-Host "php bin/console app:notify-expiring-contracts --email=mohamedzayen053@gmail.com --days=30" -ForegroundColor Green
    
} else {
    Write-Host "MAILER_DSN non trouvé dans .env. Ajout de la ligne..." -ForegroundColor Yellow
    
    $newPassword = Read-Host "Collez votre mot de passe d'application Gmail (sans espaces)"
    $newPassword = $newPassword -replace '\s', ''
    
    $correctDsn = "MAILER_DSN=`"smtp://smtp.gmail.com:587?encryption=tls&auth_mode=login&username=mohamedzayen053@gmail.com&password=$newPassword`""
    
    # Ajouter à la fin du fichier
    Add-Content -Path $envFile -Value ""
    Add-Content -Path $envFile -Value $correctDsn
    
    Write-Host "✓ MAILER_DSN ajouté au fichier .env!" -ForegroundColor Green
}

