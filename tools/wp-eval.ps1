[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [string]$Code
)

$ErrorActionPreference = 'Stop'

if ([string]::IsNullOrWhiteSpace($Code)) {
    throw 'PHP code cannot be empty.'
}

# Multiline PHP does not survive PowerShell -> npm -> wp-env reliably.
# Encode it into one shell-safe argument, then decode inside PHP.
$encoded = [Convert]::ToBase64String(
    [System.Text.Encoding]::UTF8.GetBytes($Code)
)

$wrapped = "eval(base64_decode('$encoded'));"

& npm.cmd run wp -- eval $wrapped

if ($LASTEXITCODE -ne 0) {
    throw "WP-CLI eval failed with exit code $LASTEXITCODE."
}
