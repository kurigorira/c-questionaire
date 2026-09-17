param(
    [string]$InstallDir = "C:\Apache24\htdocs\c-questionaire",
    [string]$Php = "php"
)

$ErrorActionPreference = "Stop"
Set-Location $InstallDir

Write-Host "[1/4] PHP and required extensions"
& $Php -v
$modules = (& $Php -m) -join "`n"
foreach ($required in @("PDO", "pdo_sqlite", "sqlite3", "zip")) {
    if ($modules -notmatch "(?im)^$([regex]::Escape($required))$") {
        throw "PHP extension '$required' is not enabled. Enable it in php.ini and restart Apache."
    }
}

Write-Host "[2/4] Environment file"
if (-not (Test-Path ".env")) {
    Copy-Item ".env.example" ".env"
    Write-Warning "Edit .env and replace ADMIN_PASSWORD and APP_KEY before use."
}

Write-Host "[3/4] Data directory and database"
New-Item -ItemType Directory -Force "storage" | Out-Null
& $Php "scripts\init-db.php"

Write-Host "[4/4] Write access check"
$probe = "storage\.write-test"
Set-Content -Path $probe -Value "ok" -Encoding ascii
Remove-Item $probe

Write-Host "Setup completed. Restart Apache, then open http://<server-name>/c-questionaire/"
Write-Host "If another PC cannot connect, run deploy\open-firewall.ps1 as Administrator."
