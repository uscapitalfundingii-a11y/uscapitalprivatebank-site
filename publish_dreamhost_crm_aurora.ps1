[CmdletBinding()]
param(
    [string[]]$Files = @(
        'crm\application\controllers\admin\Ai.php',
        'crm\application\controllers\admin\Ai_tickets.php',
        'crm\application\services\ai\AiProviderRegistry.php',
        'crm\application\services\ai\AiTicket.php',
        'crm\application\services\ai\AudioTranscriptionService.php',
        'crm\application\services\ai\Contracts\AiTicketInterface.php',
        'crm\application\services\ai\Providers\Base44SuperagentProvider.php',
        'crm\application\views\admin\tickets\single.php',
        'crm\application\views\admin\tickets\partials\ticket-tabpanel-add-reply.php',
        'crm\assets\js\tickets.js',
        'crm\application\config\app-config-sample.php'
    ),

    [string]$KeyPath = 'C:\Users\uscap\.ssh\codex_deploy_ed25519',

    [switch]$SkipRemoteLint
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$repoRoot = $PSScriptRoot
$publishScript = 'G:\GithubRepos\GithubUtilities\publish_ssh_files.ps1'

if (-not (Test-Path -LiteralPath $publishScript -PathType Leaf)) {
    throw "Shared publish helper not found: $publishScript"
}

& $publishScript `
    -RepoPath $repoRoot `
    -RemoteUser 'dh_9a4ezr' `
    -RemoteHost 'iad1-shared-e1-24.dreamhost.com' `
    -RemoteRoot '/home/dh_9a4ezr/uscapitalprivatebank.com' `
    -RemotePhpBin '/usr/local/bin/php-8.3' `
    -KeyPath $KeyPath `
    -Files $Files `
    -SkipRemoteLint:$SkipRemoteLint

if ($LASTEXITCODE -ne 0) {
    throw 'DreamHost Aurora CRM publish failed.'
}
