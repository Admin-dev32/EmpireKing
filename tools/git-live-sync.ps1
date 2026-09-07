[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'

$repoRoot = (& git rev-parse --show-toplevel 2>$null)

if ($LASTEXITCODE -ne 0 -or [string]::IsNullOrWhiteSpace($repoRoot)) {
    throw "Not inside a Git repository."
}

$repoRoot = $repoRoot.Trim()
Set-Location $repoRoot

$branch = (& git branch --show-current).Trim()

if ($branch -notlike 'dev/*') {
    throw "Watcher only operates on dev/* branches."
}

# Single-instance mutex using repository + branch hash.
$trimCharacters = [char[]]@(
    [System.IO.Path]::DirectorySeparatorChar,
    [System.IO.Path]::AltDirectorySeparatorChar
)

$normalizedRoot = $repoRoot.TrimEnd($trimCharacters).ToLowerInvariant()
$identity = "$normalizedRoot|$($branch.ToLowerInvariant())"

$sha256 = [System.Security.Cryptography.SHA256]::Create()

try {
    $identityBytes = [System.Text.Encoding]::UTF8.GetBytes($identity)
    $hashBytes = $sha256.ComputeHash($identityBytes)
}
finally {
    $sha256.Dispose()
}

$hash = [System.BitConverter]::ToString($hashBytes).Replace('-', '')
$mutexName = "Local\GitLiveSync_$hash"

$createdNew = $false
$mutex = New-Object System.Threading.Mutex($true, $mutexName, [ref]$createdNew)

if (-not $createdNew) {
    Write-Host "Another Git Live Sync Watcher is already running for this repository/branch." -ForegroundColor Yellow
    $mutex.Dispose()
    exit 0
}

function Test-GitOperationActive {
    $gitDir = (& git rev-parse --git-dir).Trim()

    if (-not [System.IO.Path]::IsPathRooted($gitDir)) {
        $gitDir = Join-Path $repoRoot $gitDir
    }

    $markers = @(
        'MERGE_HEAD',
        'CHERRY_PICK_HEAD',
        'REVERT_HEAD',
        'rebase-merge',
        'rebase-apply'
    )

    foreach ($marker in $markers) {
        if (Test-Path (Join-Path $gitDir $marker)) {
            return $true
        }
    }

    return $false
}

function Get-WorkingSignature {
    $entries = @(git status --porcelain=v1 --untracked-files=all)
    if ($LASTEXITCODE -ne 0) {
        throw "Could not read working tree status."
    }
    return ($entries -join "`n")
}

function Find-BlockedSecretPath {
    $entries = @(git status --porcelain=v1 --untracked-files=all)
    if ($LASTEXITCODE -ne 0) {
        throw "Could not inspect paths for secret protection."
    }

    foreach ($entry in $entries) {
        if ($entry.Length -lt 4) {
            continue
        }

        $path = $entry.Substring(3).Trim('"')

        if ($path -like '* -> *') {
            $path = (($path -split ' -> ')[-1]).Trim('"')
        }

        $leaf = [System.IO.Path]::GetFileName($path)

        if ($leaf -ieq '.env') {
            return $path
        }

        if ($leaf -like '.env.*' -and $leaf -ine '.env.example') {
            return $path
        }

        if ($leaf -match '(?i)\.(pem|key|p12|pfx)$') {
            return $path
        }

        if ($path -match '(?i)(credentials?|secrets?|private[-_]?key)') {
            return $path
        }
    }

    return $null
}

function Assert-OutboundWorkSafe {
    if (Test-GitOperationActive) {
        throw "Active merge, rebase, cherry-pick, or revert detected. Watcher stopped."
    }

    & git diff --cached --quiet

    if ($LASTEXITCODE -ne 0) {
        throw "Pre-existing staged changes detected or index unreadable. Watcher stopped instead of modifying the index."
    }

    $blockedPath = Find-BlockedSecretPath
    if ($blockedPath) {
        throw "Secret-like file blocked from autosync: $blockedPath"
    }

    & git diff --check

    if ($LASTEXITCODE -ne 0) {
        throw "git diff --check failed. Watcher stopped."
    }
}

function Get-AheadBehind {
    & git fetch origin "refs/heads/${branch}:refs/remotes/origin/${branch}" --quiet

    if ($LASTEXITCODE -ne 0) {
        throw "Could not fetch origin/$branch."
    }

    $countOutput = @(& git rev-list --left-right --count "HEAD...origin/$branch")
    if ($LASTEXITCODE -ne 0) {
        throw "Could not compare HEAD with origin/$branch."
    }
    $counts = $countOutput -join ' '

    if ($counts -notmatch '^\s*(\d+)\s+(\d+)\s*$') {
        throw "Could not determine ahead/behind state."
    }

    return [PSCustomObject]@{
        Ahead  = [int]$Matches[1]
        Behind = [int]$Matches[2]
    }
}

function Assert-OutboundRemoteSafe {
    $state = Get-AheadBehind
    if ($state.Behind -gt 0) {
        throw "origin/$branch advanced (behind: $($state.Behind), ahead: $($state.Ahead)). Dirty work must be reconciled manually. Watcher stopped before staging or checkpointing."
    }
}

function Push-LocalAhead {
    $state = Get-AheadBehind

    if ($state.Behind -gt 0) {
        throw "origin/$branch advanced while this machine was active. Watcher stopped to prevent divergence."
    }

    if ($state.Ahead -eq 0) {
        return
    }

    Write-Host "Pushing $($state.Ahead) safe checkpoint commit(s) to origin/$branch..." -ForegroundColor Cyan

    & git push origin "HEAD:refs/heads/$branch"

    if ($LASTEXITCODE -ne 0) {
        Write-Host "Push failed. Local commit(s) were preserved and will not be reset." -ForegroundColor Yellow
        return
    }

    Write-Host "Checkpoint pushed successfully." -ForegroundColor Green
}

try {
    $origin = (& git remote get-url origin 2>$null)
    if ($LASTEXITCODE -ne 0 -or [string]::IsNullOrWhiteSpace($origin)) {
        throw "origin is missing. Watcher was not started."
    }
    if ($origin.Trim() -cnotmatch '\A(?:https://github\.com/|git@github\.com:|ssh://git@github\.com/)Admin-dev32/EmpireKing(?:\.git)?\z') {
        throw "Unexpected origin. Expected the Admin-dev32/EmpireKing GitHub repository."
    }

    Assert-OutboundWorkSafe
    $startupSignature = Get-WorkingSignature
    if ([string]::IsNullOrWhiteSpace($startupSignature)) {
        $safeSync = Join-Path $PSScriptRoot 'git-safe-startup-sync.ps1'
        & powershell.exe -NoProfile -ExecutionPolicy Bypass -File $safeSync
        if ($LASTEXITCODE -ne 0) {
            throw "Safe Sync In failed. Watcher was not started."
        }
    }
    else {
        # Fetching the exact branch also verifies that it exists on origin.
        Assert-OutboundRemoteSafe
        Assert-OutboundWorkSafe
        Write-Host "Dirty outbound startup approved; existing work will enter the 15-second debounce." -ForegroundColor Cyan
    }

    Write-Host ""
    Write-Host "=== EMPIRE KING GIT LIVE SYNC ===" -ForegroundColor Cyan
    Write-Host "Repository: $repoRoot"
    Write-Host "Branch:     $branch"
    Write-Host "Poll:       2 seconds"
    Write-Host "Debounce:   15 seconds"
    Write-Host ""

    # Push any safe local ahead-only commits left from setup/work.
    Push-LocalAhead

    $lastSignature = Get-WorkingSignature
    $lastChange = $null

    if (-not [string]::IsNullOrWhiteSpace($lastSignature)) {
        $lastChange = Get-Date
    }

    $nextPushRetry = Get-Date

    while ($true) {
        Start-Sleep -Seconds 2

        $currentBranch = (& git branch --show-current).Trim()

        if ($currentBranch -ne $branch) {
            throw "Branch changed from $branch to $currentBranch. Watcher stopped."
        }

        if (Test-GitOperationActive) {
            continue
        }

        $signature = Get-WorkingSignature

        if ($signature -ne $lastSignature) {
            $lastSignature = $signature

            if ([string]::IsNullOrWhiteSpace($signature)) {
                $lastChange = $null
            }
            else {
                $lastChange = Get-Date
            }

            continue
        }

        if ([string]::IsNullOrWhiteSpace($signature)) {
            if ((Get-Date) -ge $nextPushRetry) {
                Push-LocalAhead
                $nextPushRetry = (Get-Date).AddSeconds(30)
            }

            continue
        }

        if ($null -eq $lastChange) {
            $lastChange = Get-Date
            continue
        }

        $stableSeconds = ((Get-Date) - $lastChange).TotalSeconds

        if ($stableSeconds -lt 15) {
            continue
        }

        # Never checkpoint on a knowingly stale base. Recheck local guards
        # after fetching, before taking ownership of any index changes.
        Assert-OutboundRemoteSafe
        Assert-OutboundWorkSafe

        & git add -A

        if ($LASTEXITCODE -ne 0) {
            throw "git add failed."
        }

        & git diff --cached --check

        if ($LASTEXITCODE -ne 0) {
            throw "Staged diff validation failed. Changes remain staged for manual review."
        }

        & git diff --cached --quiet

        if ($LASTEXITCODE -eq 0) {
            $lastSignature = Get-WorkingSignature
            $lastChange = $null
            continue
        }

        $timestamp = Get-Date -Format 'yyyy-MM-dd HH:mm:ss'

        & git commit -m "autosync checkpoint $timestamp"

        if ($LASTEXITCODE -ne 0) {
            throw "Automatic checkpoint commit failed."
        }

        Push-LocalAhead

        $lastSignature = Get-WorkingSignature
        $lastChange = $null
        $nextPushRetry = (Get-Date).AddSeconds(30)
    }
}
finally {
    if ($mutex) {
        try {
            $mutex.ReleaseMutex()
        }
        catch {
        }

        $mutex.Dispose()
    }
}
