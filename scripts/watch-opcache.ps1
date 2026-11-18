param(
    [string]$BaseUrl = 'http://localhost/poolstream',
    [string]$Token = '',
    [int]$IntervalSeconds = 1,
    [string]$Root = ''
)

# Pantau file PHP (backend) lalu minta container menyegarkan OPcache,
# sehingga perubahan kode langsung terpakai tanpa restart container.

if (-not $Root) {
    $Root = Split-Path -Parent $PSScriptRoot
}

$Root = (Resolve-Path $Root).Path

if (-not $Token) {
    $envFile = Join-Path $Root '.env'
    if (Test-Path $envFile) {
        $match = Select-String -Path $envFile -Pattern '^DEV_OPCACHE_TOKEN=(.+)$' | Select-Object -First 1
        if ($match) {
            $Token = $match.Matches[0].Groups[1].Value.Trim()
        }
    }
}

if (-not $Token) {
    Write-Error 'DEV_OPCACHE_TOKEN tidak ditemukan di .env. Jalankan dengan -Token <token>.'
    exit 1
}

$endpoint = $BaseUrl.TrimEnd('/') + '/__dev/opcache?token=' + [uri]::EscapeDataString($Token)
$watchDirs = @('app', 'routes', 'config', 'database', 'bootstrap')

function Get-PhpSnapshot {
    $snapshot = @{}

    foreach ($dir in $watchDirs) {
        $full = Join-Path $Root $dir
        if (-not (Test-Path $full)) { continue }

        Get-ChildItem -Path $full -Recurse -Filter *.php -File -ErrorAction SilentlyContinue | ForEach-Object {
            $snapshot[$_.FullName] = $_.LastWriteTimeUtc.Ticks
        }
    }

    return $snapshot
}

function Send-Invalidate {
    param([string[]]$Files)

    if (-not $Files -or $Files.Count -eq 0) { return }

    $parts = foreach ($file in $Files) {
        $relative = $file.Substring($Root.Length).TrimStart('\', '/').Replace('\', '/')
        'path%5B%5D=' + [uri]::EscapeDataString($relative)
    }

    $url = $endpoint + '&' + ($parts -join '&')
    $stamp = Get-Date -Format 'HH:mm:ss'

    try {
        $response = Invoke-RestMethod -Uri $url -TimeoutSec 10
        $ok = @($response.files | Where-Object { $_.ok }).Count

        Write-Host "[$stamp] OPcache disegarkan: $ok file" -ForegroundColor DarkGray

        foreach ($file in @($response.files | Where-Object { -not $_.ok })) {
            Write-Host "          tidak ada di cache: $($file.path)" -ForegroundColor DarkYellow
        }
    } catch {
        Write-Host "[$stamp] gagal menghubungi endpoint opcache: $($_.Exception.Message)" -ForegroundColor Yellow
        Write-Host "          pastikan container php8.5-apache berjalan dan URL benar ($BaseUrl)." -ForegroundColor DarkGray
    }
}

Write-Host "Memantau perubahan PHP di: $Root" -ForegroundColor Cyan
Write-Host "Endpoint                 : $($endpoint.Split('?')[0])" -ForegroundColor Cyan
Write-Host "Tekan Ctrl+C untuk berhenti." -ForegroundColor DarkGray

$previous = Get-PhpSnapshot

while ($true) {
    Start-Sleep -Seconds $IntervalSeconds
    $current = Get-PhpSnapshot
    $changed = New-Object System.Collections.Generic.List[string]

    foreach ($path in $current.Keys) {
        if (-not $previous.ContainsKey($path) -or $previous[$path] -ne $current[$path]) {
            $changed.Add($path)
        }
    }

    foreach ($path in $previous.Keys) {
        if (-not $current.ContainsKey($path)) {
            $changed.Add($path)
        }
    }

    if ($changed.Count -gt 0) {
        Send-Invalidate -Files $changed.ToArray()
    }

    $previous = $current
}
