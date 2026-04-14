[CmdletBinding()]
param(
    [string]$KeyPath = 'C:\Users\uscap\.ssh\codex_deploy_ed25519',
    [string[]]$Paths = @(
        'crm\modules',
        'crm\application',
        'crm\system',
        'crm\resources',
        'crm\verify'
    )
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$syncScript = 'G:\GithubRepos\GithubUtilities\sync_ssh_files.ps1'
if (-not (Test-Path -LiteralPath $syncScript -PathType Leaf)) {
    throw "Shared sync script not found: $syncScript"
}

& powershell -ExecutionPolicy Bypass -File $syncScript `
    -RepoPath 'G:\GithubRepos\uscapitalprivatebank-site' `
    -RemoteUser 'dh_9a4ezr' `
    -RemoteHost 'iad1-shared-e1-24.dreamhost.com' `
    -RemoteRoot '/home/dh_9a4ezr/uscapitalprivatebank.com' `
    -KeyPath $KeyPath `
    -Paths $Paths

if ($LASTEXITCODE -ne 0) {
    throw 'DreamHost live-to-repo sync failed.'
}
