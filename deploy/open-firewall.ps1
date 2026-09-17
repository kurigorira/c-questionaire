# Run this script once in an elevated PowerShell window on the web server.
$ErrorActionPreference = "Stop"
$name = "Influenza questionnaire (hospital LAN HTTP)"
Get-NetFirewallRule -DisplayName $name -ErrorAction SilentlyContinue | Remove-NetFirewallRule
New-NetFirewallRule -DisplayName $name -Direction Inbound -Action Allow `
    -Protocol TCP -LocalPort 80 -Profile Domain,Private -RemoteAddress LocalSubnet | Out-Null
Write-Host "Windows Firewall now allows TCP/80 from the local subnet only."
