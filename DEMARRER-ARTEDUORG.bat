@echo off
title ArtEduOrg - Demarrage
echo.
echo Demarrage d'ArtEduOrg (application + base de donnees)...
echo.

docker-compose up -d

if %ERRORLEVEL% NEQ 0 (
  echo.
  echo ERREUR: Docker n'a pas pu demarrer. Verifiez que Docker Desktop est installe et lance.
  pause
  exit /b 1
)

echo.
echo Demarrage en cours. Attendez 1 a 2 minutes puis ouvrez votre navigateur sur:
echo.
echo    http://localhost:8080
echo.
echo Pour arreter plus tard: docker-compose down
echo.
pause
