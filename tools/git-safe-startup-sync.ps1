[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'

function Stop-Safely {
    param([string]$Message)
    Write-Host "SAFE SYNC BLOCKED: $Message" -ForegroundColor Red
    exit 1
}

$repoRoot = (& git rev-parse --show-toplevel 2>$null)

if ($LASTEXITCODE -ne 0 -or [string]::IsNullOrWhiteSpace($repoRoot)) {
    Stop-Safely "Not inside a Git repository."
}

$repoRoot = $repoRoot.Trim()
Set-Location $repoRoot

$origin = (& git remote get-url origin 2>$null)

if ($LASTEXITCODE -ne 0 -or [string]::IsNullOrWhiteSpace($origin)) {
    Stop-Safely "origin is missing."
}

$origin = $origin.Trim()

if ($origin -notmatch 'github\.com[:/]Admin-dev32/EmpireKing(\.git)?$') {
    Stop-Safely "Unexpected origin: $origin"
}

$branch = (& git branch --show-current).Trim()

if ([string]::IsNullOrWhiteSpace($branch) -or $branch -notlike 'dev/*') {
    Stop-Safely "Automatic sync is allowed only from a dev/* branch. Current: $branch"
}

$dirty = @(git status --porcelain=v1)

if ($dirty.Count -gt 0) {
    Stop-Safely "Working tree is dirty. Commit or resolve local work before inbound sync."
}

Write-Host "Safe Sync In: fetching origin/$branch..." -ForegroundColor Cyan

& git fetch origin "refs/heads/${branch}:refs/remotes/origin/${branch}" --prune

if ($LASTEXITCODE -ne 0) {
    Stop-Safely "Fetch failed."
}

& git rev-parse --verify "origin/$branch" *> $null

if ($LASTEXITCODE -ne 0) {
    Stop-Safely "Remote branch origin/$branch does not exist."
}

$countText = (& git rev-list --left-right --count "HEAD...origin/$branch").Trim()

if ($countText -notmatch '^\s*(\d+)\s+(\d+)\s*$') {
    Stop-Safely "Could not determine ahead/behind state."
}

$ahead = [int]$Matches[1]
$behind = [int]$Matches[2]

if ($ahead -gt 0 -and $behind -gt 0) {
    Stop-Safely "Local and remote branches diverged."
}

if ($behind -gt 0) {
    Write-Host "Remote is ahead by $behind commit(s). Fast-forwarding..." -ForegroundColor Yellow

    & git merge --ff-only "origin/$branch"

    if ($LASTEXITCODE -ne 0) {
        Stop-Safely "Fast-forward failed."
    }

    Write-Host "Safe Sync In complete: fast-forwarded." -ForegroundColor Green
    exit 0
}

if ($ahead -gt 0) {
    Write-Host "Safe Sync In complete: local is ahead by $ahead commit(s); preserving local work for watcher push." -ForegroundColor Green
    exit 0
}

Write-Host "Safe Sync In complete: local and remote are equal." -ForegroundColor Green
