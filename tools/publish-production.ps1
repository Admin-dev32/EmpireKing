[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

function Stop-Promotion {
    param([Parameter(Mandatory = $true)][string]$Message)

    Write-Host "PRODUCTION PROMOTION BLOCKED: $Message" -ForegroundColor Red
    exit 1
}

function Get-GitSha {
    param([Parameter(Mandatory = $true)][string]$Ref)

    $sha = (& git rev-parse --verify "${Ref}^{commit}" 2>$null)

    if ($LASTEXITCODE -ne 0 -or [string]::IsNullOrWhiteSpace($sha)) {
        Stop-Promotion "Could not resolve $Ref."
    }

    return $sha.Trim()
}

function Test-GitAncestor {
    param(
        [Parameter(Mandatory = $true)][string]$Ancestor,
        [Parameter(Mandatory = $true)][string]$Descendant,
        [Parameter(Mandatory = $true)][string]$Destination
    )

    & git merge-base --is-ancestor $Ancestor $Descendant

    if ($LASTEXITCODE -eq 0) {
        return
    }

    if ($LASTEXITCODE -eq 1) {
        Stop-Promotion "The approved checkpoint cannot fast-forward $Destination. $Destination contains commits that are not ancestors of HEAD."
    }

    Stop-Promotion "Could not verify ancestry for $Destination."
}

function Get-RemoteBranchSha {
    param([Parameter(Mandatory = $true)][string]$Branch)

    $remoteLine = @(& git ls-remote --heads origin "refs/heads/$Branch" 2>$null)

    if ($LASTEXITCODE -ne 0 -or $remoteLine.Count -ne 1) {
        Stop-Promotion "Could not verify origin/$Branch after push."
    }

    $parts = $remoteLine[0] -split '\s+'

    if ($parts.Count -lt 2 -or $parts[1] -ne "refs/heads/$Branch" -or $parts[0] -notmatch '^[0-9a-f]{40}$') {
        Stop-Promotion "origin/$Branch returned an unexpected ref value."
    }

    return $parts[0]
}

$repoRoot = (& git rev-parse --show-toplevel 2>$null)

if ($LASTEXITCODE -ne 0 -or [string]::IsNullOrWhiteSpace($repoRoot)) {
    Stop-Promotion 'Not inside a Git repository.'
}

$repoRoot = $repoRoot.Trim()
Set-Location $repoRoot

$branch = (& git branch --show-current 2>$null).Trim()

if ($branch -ne 'dev/jorge') {
    Stop-Promotion "Run this task only from dev/jorge. Current branch: $branch"
}

$dirty = @(git status --porcelain=v1 --untracked-files=all)

if ($LASTEXITCODE -ne 0) {
    Stop-Promotion 'Could not inspect the working tree.'
}

if ($dirty.Count -gt 0) {
    Stop-Promotion 'Working tree is not clean. Wait for the Git Live Sync Watcher to create and push its checkpoint before publishing.'
}

$origin = (& git remote get-url origin 2>$null)

if ($LASTEXITCODE -ne 0 -or [string]::IsNullOrWhiteSpace($origin)) {
    Stop-Promotion 'origin is missing.'
}

if ($origin.Trim() -notmatch 'github\.com[:/]Admin-dev32/EmpireKing(\.git)?$') {
    Stop-Promotion "Unexpected origin: $($origin.Trim())"
}

Write-Host 'Fetching origin refs...' -ForegroundColor Cyan
& git fetch origin --prune

if ($LASTEXITCODE -ne 0) {
    Stop-Promotion 'Fetch failed. No branches were changed.'
}

$headSha    = Get-GitSha 'HEAD'
$devSha     = Get-GitSha 'origin/dev/jorge'
$liveDevSha = Get-GitSha 'origin/live-dev'
$mainSha    = Get-GitSha 'origin/main'

if ($headSha -ne $devSha) {
    Stop-Promotion 'The local checkpoint has not finished syncing to GitHub yet. Wait for the Git Live Sync Watcher, then run this task again.'
}

Test-GitAncestor -Ancestor $liveDevSha -Descendant $headSha -Destination 'live-dev'
Test-GitAncestor -Ancestor $mainSha -Descendant $headSha -Destination 'main'

$shortSha = $headSha.Substring(0, 12)
$subject  = (& git log -1 --format=%s $headSha).Trim()

if ($LASTEXITCODE -ne 0) {
    Stop-Promotion 'Could not read the approved checkpoint subject.'
}

Write-Host ''
Write-Host 'Empire King Production Promotion' -ForegroundColor Cyan
Write-Host ''
Write-Host "Checkpoint: $shortSha $subject"
Write-Host "dev/jorge: $devSha"
Write-Host "live-dev:  $liveDevSha"
Write-Host "main:      $mainSha"
Write-Host 'Destination: Avenue H + Avenue I production deployment'
Write-Host ''

$confirmation = Read-Host 'Type LIVE to publish this exact checkpoint'

if ($confirmation -cne 'LIVE') {
    Write-Host 'Production promotion cancelled. No branches were changed.' -ForegroundColor Yellow
    exit 0
}

Write-Host "Promoting $headSha to live-dev..." -ForegroundColor Cyan
& git push origin "${headSha}:refs/heads/live-dev"

if ($LASTEXITCODE -ne 0) {
    Stop-Promotion 'Push to live-dev failed. main was not touched.'
}

$verifiedLiveDevSha = Get-RemoteBranchSha 'live-dev'

if ($verifiedLiveDevSha -ne $headSha) {
    Write-Host 'PARTIAL PROMOTION: live-dev push completed but verification did not match the approved checkpoint. main was not touched. No rollback was attempted.' -ForegroundColor Red
    Write-Host "live-dev expected: $headSha"
    Write-Host "live-dev actual:   $verifiedLiveDevSha"
    exit 1
}

Write-Host "Promoting $headSha to main..." -ForegroundColor Cyan
& git push origin "${headSha}:refs/heads/main"

if ($LASTEXITCODE -ne 0) {
    Write-Host 'PARTIAL PROMOTION: live-dev was promoted successfully, but main push failed. No rollback was attempted.' -ForegroundColor Red
    Write-Host "live-dev: $verifiedLiveDevSha"
    Write-Host "main:     $mainSha"
    exit 1
}

$verifiedMainSha = Get-RemoteBranchSha 'main'

if ($verifiedMainSha -ne $headSha) {
    Write-Host 'PARTIAL PROMOTION: live-dev was promoted successfully, but main verification did not match the approved checkpoint. No rollback was attempted.' -ForegroundColor Red
    Write-Host "live-dev:      $verifiedLiveDevSha"
    Write-Host "main expected: $headSha"
    Write-Host "main actual:   $verifiedMainSha"
    exit 1
}

Write-Host ''
Write-Host 'PRODUCTION PROMOTION COMPLETE' -ForegroundColor Green
Write-Host "live-dev: $verifiedLiveDevSha"
Write-Host "main: $verifiedMainSha"
Write-Host 'GitHub Actions production deployment has been triggered.' -ForegroundColor Green

Start-Process 'https://github.com/Admin-dev32/EmpireKing/actions/workflows/deploy-production.yml'
