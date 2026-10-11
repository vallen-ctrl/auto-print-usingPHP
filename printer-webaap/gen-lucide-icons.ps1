# Run from the Laravel project root.
# Usage: .\gen-lucide-icons.ps1 [-OutFile references\lucide-icons.txt]
param(
    [string]$OutFile = "references/lucide-icons.txt"
)

$pkg = "vendor/mallardduck/blade-lucide-icons/resources/svg"
if (-not (Test-Path "$pkg/icons")) {
    Write-Error "Package not found: $pkg"
    exit 1
}

function Get-Names($dir) {
    if (-not (Test-Path $dir)) { return @() }
    $names = [string[]](Get-ChildItem $dir -Filter *.svg | ForEach-Object { $_.BaseName })
    [Array]::Sort($names, [StringComparer]::Ordinal)
    return $names
}

$mainIcons = Get-Names "$pkg/icons"
$labIcons  = Get-Names "$pkg/lab"

# One component name per line, e.g. "lucide-printer" -> <x-lucide-printer />
$lines = @()
$lines += @($mainIcons | ForEach-Object { "lucide-$_" })
$lines += @($labIcons  | ForEach-Object { "lucide-lab-$_" })

$fullPath = $ExecutionContext.SessionState.Path.GetUnresolvedProviderPathFromPSPath($OutFile)
$dir = Split-Path $fullPath -Parent
if ($dir) { New-Item -ItemType Directory -Force -Path $dir | Out-Null }

# UTF-8 without BOM
[System.IO.File]::WriteAllText($fullPath, (($lines -join "`n") + "`n"), (New-Object System.Text.UTF8Encoding $false))

Write-Host "Generated $OutFile ($($mainIcons.Count) main, $($labIcons.Count) lab)"