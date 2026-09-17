param(
    [string]$InstallDir = "C:\Apache24\htdocs\c-questionaire",
    [string]$Php = "php"
)

$ErrorActionPreference = "Stop"
Set-Location $InstallDir

if (-not (Test-Path "public\index.php") -or -not (Test-Path "bootstrap.php")) {
    throw "Copy the complete project to $InstallDir before running this script."
}

Write-Host "[1/3] Copy public files to the application folder"
Copy-Item -Path "public\*" -Destination "." -Recurse -Force

Write-Host "[2/3] Initialize PHP and SQLite"
& ".\deploy\setup-windows.ps1" -InstallDir $InstallDir -Php $Php

Write-Host "[3/3] Verify installation"
foreach ($required in @('index.php', 'assets\app.css', 'bootstrap.php', 'config\app.php')) {
    if (-not (Test-Path $required)) { throw "Missing file: $required" }
}

Write-Host "Installation completed."
Write-Host "Restart Apache and open http://<server-ip>/c-questionaire/"
