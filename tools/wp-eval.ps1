[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [string]$Code
)

$ErrorActionPreference = 'Stop'

if ([string]::IsNullOrWhiteSpace($Code)) {
    throw 'PHP code cannot be empty.'
}

$encoded = [Convert]::ToBase64String(
    [System.Text.Encoding]::UTF8.GetBytes($Code)
)

$wrapped = "try { eval(base64_decode('$encoded')); } catch (\ParseError `$e) { fwrite(STDERR, 'PHP syntax error: ' . `$e->getMessage() . PHP_EOL); exit(2); } catch (\Throwable `$e) { fwrite(STDERR, 'PHP runtime error: ' . `$e->getMessage() . PHP_EOL); exit(3); }"

& npm.cmd run wp -- eval $wrapped

if ($LASTEXITCODE -ne 0) {
    throw "WP-CLI eval failed with exit code $LASTEXITCODE."
}
