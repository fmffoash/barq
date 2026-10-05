# Shared helpers for the Windows scripts (setup / start / update). Dot-source it, don't run it.
# Everything the app needs (PHP, Composer, Node.js) is downloaded into <app>\.runtime when it
# is not already installed - nothing is installed system-wide and the PATH is not touched
# outside the current window. Ollama is the only real install (it has its own installer).
# Kept ASCII-only on purpose: Windows PowerShell 5.1 misreads UTF-8 files without a BOM.

$ProgressPreference = 'SilentlyContinue'   # the progress bar makes Invoke-WebRequest ~10x slower in 5.1
try {
    [Net.ServicePointManager]::SecurityProtocol = [Net.ServicePointManager]::SecurityProtocol -bor [Net.SecurityProtocolType]::Tls12
} catch { }

$script:OnWindows = ($env:OS -eq 'Windows_NT')

function Say([string]$Message) { Write-Host "`n==> $Message" -ForegroundColor Cyan }
function Warn([string]$Message) { Write-Host $Message -ForegroundColor Yellow }
function Fail([string]$Message) { Write-Host "`nERROR: $Message" -ForegroundColor Red; exit 1 }

# Native commands don't throw in PowerShell - check the exit code of every step explicitly.
function Run([string]$Exe, [string[]]$Arguments) {
    & $Exe @Arguments
    if ($LASTEXITCODE -ne 0) { Fail "'$Exe $($Arguments -join ' ')' failed (exit code $LASTEXITCODE)." }
}

# Downloads go to <app>\.runtime\downloads, not %TEMP%: %TEMP% is under the user profile, and
# an Arabic (non-ASCII) Windows username breaks the paths handed to curl.exe / tar.exe.
$script:DownloadDir = $null

function Get-TempPath([string]$Name) {
    $base = $script:DownloadDir
    if (-not $base) { $base = if ($env:TEMP) { $env:TEMP } else { [IO.Path]::GetTempPath() } }
    New-Item -ItemType Directory -Force -Path $base | Out-Null
    return (Join-Path $base $Name)
}

# Puts <app>\.runtime\{php,node,composer} (when present) and Ollama's default install folder
# in front of PATH for this window only.
function Use-LocalRuntime([string]$Root) {
    $runtime = Join-Path $Root '.runtime'
    $script:DownloadDir = Join-Path $runtime 'downloads'
    foreach ($name in @('composer', 'node', 'php')) {
        $dir = Join-Path $runtime $name
        if (Test-Path $dir) { $env:PATH = "$dir;$env:PATH" }
    }
    if ($env:LOCALAPPDATA) {
        $ollamaDir = Join-Path $env:LOCALAPPDATA 'Programs\Ollama'
        if ((Test-Path (Join-Path $ollamaDir 'ollama.exe')) -and -not (Get-Command 'ollama' -ErrorAction SilentlyContinue)) {
            $env:PATH = "$env:PATH;$ollamaDir"
        }
    }
}

function Get-Download([string]$Url, [string]$OutFile) {
    if (Test-Path $OutFile) { Remove-Item $OutFile -Force }
    $curl = Get-Command 'curl.exe' -ErrorAction SilentlyContinue
    if ($curl) {
        & $curl.Source -fsSL --retry 3 -o $OutFile $Url
        return (($LASTEXITCODE -eq 0) -and (Test-Path $OutFile))
    }
    try {
        Invoke-WebRequest -Uri $Url -OutFile $OutFile -UseBasicParsing
        return (Test-Path $OutFile)
    } catch {
        return $false
    }
}

function Test-Sha256([string]$File, [string]$Expected) {
    if (-not $Expected) { return $true }
    return ((Get-FileHash -Path $File -Algorithm SHA256).Hash -eq $Expected.Trim())
}

function Expand-Zip([string]$Zip, [string]$Destination) {
    New-Item -ItemType Directory -Force -Path $Destination | Out-Null
    # tar.exe (built into Windows 10+) unpacks zips much faster than Expand-Archive.
    $tar = Get-Command 'tar.exe' -ErrorAction SilentlyContinue
    if ($tar) {
        & $tar.Source -xf $Zip -C $Destination
        if ($LASTEXITCODE -eq 0) { return }
    }
    Expand-Archive -Path $Zip -DestinationPath $Destination -Force
}

# PHP 8.3+ with every extension the app uses.
function Test-Php {
    if (-not (Get-Command 'php' -ErrorAction SilentlyContinue)) { return $false }
    $check = 'exit(PHP_VERSION_ID >= 80300 && extension_loaded(''pdo_sqlite'') && extension_loaded(''zip'') && extension_loaded(''mbstring'') && extension_loaded(''fileinfo'') && extension_loaded(''openssl'') ? 0 : 1);'
    & php -r $check 2>$null | Out-Null
    return ($LASTEXITCODE -eq 0)
}

# PHP 8.4 (vs17) builds need the Visual C++ 2015-2022 runtime, version 14.30 or newer. Checked
# up front: running php.exe without it pops up a blocking "DLL not found" dialog.
function Test-VcRuntime {
    $system32 = Join-Path $env:SystemRoot 'System32'
    $dll = Join-Path $system32 'vcruntime140.dll'
    if (-not (Test-Path $dll) -or -not (Test-Path (Join-Path $system32 'vcruntime140_1.dll'))) { return $false }
    $version = (Get-Item $dll).VersionInfo
    return (($version.FileMajorPart -gt 14) -or (($version.FileMajorPart -eq 14) -and ($version.FileMinorPart -ge 30)))
}

function Install-VcRuntime {
    Write-Host 'Installing the Microsoft Visual C++ runtime that PHP needs (click Yes if Windows asks)...'
    $installer = Get-TempPath 'vc_redist.x64.exe'
    if (-not (Get-Download 'https://aka.ms/vs/17/release/vc_redist.x64.exe' $installer)) { return $false }
    Start-Process -FilePath $installer -ArgumentList '/install', '/quiet', '/norestart' -Wait
    return (Test-VcRuntime)
}

function Write-PhpIni([string]$PhpDir) {
    $lines = @(
        '; Written by local\setup.ps1 for running the app on this machine.',
        "extension_dir = `"$(Join-Path $PhpDir 'ext')`"",
        'extension=curl',
        'extension=fileinfo',
        'extension=mbstring',
        'extension=openssl',
        'extension=pdo_sqlite',
        'extension=sqlite3',
        'extension=zip',
        'memory_limit = 512M',
        'upload_max_filesize = 10M',
        'post_max_size = 64M',
        'max_execution_time = 300'
    )
    # UTF-8 without BOM - PHP's ini parser chokes on a BOM.
    [IO.File]::WriteAllLines((Join-Path $PhpDir 'php.ini'), $lines)
}

# Official Windows build from windows.php.net: newest 8.4 (else 8.3), non-thread-safe, x64.
function Install-PortablePhp([string]$Root) {
    $index = Get-TempPath 'php-releases.json'
    if (-not (Get-Download 'https://windows.php.net/downloads/releases/releases.json' $index)) { return $false }

    try { $releases = Get-Content $index -Raw | ConvertFrom-Json } catch { return $false }

    $build = $null
    foreach ($minor in @('8.4', '8.3')) {
        $release = $releases.$minor
        if (-not $release) { continue }
        foreach ($property in $release.PSObject.Properties) {
            if ($property.Name -match '^nts-vs\d+-x64$' -and $property.Value.zip.path) { $build = $property.Value.zip; break }
        }
        if ($build) { break }
    }
    if (-not $build) { return $false }

    if (-not (Test-VcRuntime) -and -not (Install-VcRuntime)) { return $false }

    Write-Host "Downloading $($build.path) ..."
    $zip = Get-TempPath $build.path
    if (-not (Get-Download "https://windows.php.net/downloads/releases/$($build.path)" $zip)) { return $false }
    if (-not (Test-Sha256 $zip $build.sha256)) { Warn 'PHP download is corrupted (checksum mismatch).'; return $false }

    $phpDir = Join-Path $Root '.runtime\php'
    if (Test-Path $phpDir) { Remove-Item $phpDir -Recurse -Force }
    Expand-Zip $zip $phpDir
    Write-PhpIni $phpDir
    Remove-Item $zip -Force -ErrorAction SilentlyContinue
    return (Test-Path (Join-Path $phpDir 'php.exe'))
}

# Composer as a single composer.phar + a composer.bat next to it, so `composer` works as usual.
function Install-PortableComposer([string]$Root) {
    $dir = Join-Path $Root '.runtime\composer'
    New-Item -ItemType Directory -Force -Path $dir | Out-Null
    $phar = Join-Path $dir 'composer.phar'

    $ok = $false
    $sumFile = Get-TempPath 'composer.phar.sha256sum'
    if ((Get-Download 'https://getcomposer.org/download/latest-stable/composer.phar' $phar) -and
        (Get-Download 'https://getcomposer.org/download/latest-stable/composer.phar.sha256sum' $sumFile)) {
        $ok = Test-Sha256 $phar ((Get-Content $sumFile -Raw).Trim() -split '\s+')[0]
    }
    if (-not $ok) {
        $ok = Get-Download 'https://github.com/composer/composer/releases/latest/download/composer.phar' $phar
    }
    if (-not $ok) { return $false }

    [IO.File]::WriteAllText((Join-Path $dir 'composer.bat'), "@php `"%~dp0composer.phar`" %*`r`n")
    return $true
}

# Latest Node.js LTS from nodejs.org (portable zip, checksum-verified).
function Install-PortableNode([string]$Root) {
    $index = Get-TempPath 'node-index.json'
    if (-not (Get-Download 'https://nodejs.org/dist/index.json' $index)) { return $false }

    try { $releases = @(Get-Content $index -Raw | ConvertFrom-Json) } catch { return $false }
    # 5.1 hands a JSON array over as one object - @() + foreach makes sure every release is seen.
    $lts = $null
    foreach ($release in ($releases | ForEach-Object { $_ })) {
        if ($release.lts -and ($release.files -contains 'win-x64-zip')) { $lts = $release; break }
    }
    if (-not $lts) { return $false }

    $name = "node-$($lts.version)-win-x64"
    Write-Host "Downloading $name.zip ..."
    $zip = Get-TempPath "$name.zip"
    $sums = Get-TempPath 'node-SHASUMS256.txt'
    if (-not (Get-Download "https://nodejs.org/dist/$($lts.version)/$name.zip" $zip)) { return $false }
    if (Get-Download "https://nodejs.org/dist/$($lts.version)/SHASUMS256.txt" $sums) {
        $line = Get-Content $sums | Where-Object { $_ -match "\s$([regex]::Escape("$name.zip"))$" } | Select-Object -First 1
        if ($line -and -not (Test-Sha256 $zip (($line.Trim() -split '\s+')[0]))) { Warn 'Node.js download is corrupted (checksum mismatch).'; return $false }
    }

    $runtime = Join-Path $Root '.runtime'
    $staging = Join-Path $runtime 'node-unpack'
    $nodeDir = Join-Path $runtime 'node'
    foreach ($dir in @($staging, $nodeDir)) { if (Test-Path $dir) { Remove-Item $dir -Recurse -Force } }
    Expand-Zip $zip $staging
    Move-Item (Join-Path $staging $name) $nodeDir
    Remove-Item $staging -Recurse -Force -ErrorAction SilentlyContinue
    Remove-Item $zip -Force -ErrorAction SilentlyContinue
    return (Test-Path (Join-Path $nodeDir 'node.exe'))
}

function Test-OllamaApi {
    try {
        Invoke-WebRequest -Uri 'http://127.0.0.1:11434/api/tags' -UseBasicParsing -TimeoutSec 2 | Out-Null
        return $true
    } catch {
        return $false
    }
}

# Starts `ollama serve` in the background when the Ollama app isn't already running.
function Start-OllamaIfNeeded {
    if (Test-OllamaApi) { return $true }
    if (-not (Get-Command 'ollama' -ErrorAction SilentlyContinue)) { return $false }
    Start-Process 'ollama' -ArgumentList 'serve' -WindowStyle Hidden
    for ($i = 0; $i -lt 20; $i++) {
        Start-Sleep -Seconds 1
        if (Test-OllamaApi) { return $true }
    }
    return $false
}

function Install-Ollama([string]$Root) {
    if (Get-Command 'winget' -ErrorAction SilentlyContinue) {
        & winget install --id Ollama.Ollama -e --source winget --accept-package-agreements --accept-source-agreements
    }
    Use-LocalRuntime $Root
    if (Get-Command 'ollama' -ErrorAction SilentlyContinue) { return $true }

    Write-Host 'Downloading the Ollama installer (large download, please wait)...'
    $installer = Get-TempPath 'OllamaSetup.exe'
    if (Get-Download 'https://ollama.com/download/OllamaSetup.exe' $installer) {
        Start-Process -FilePath $installer -ArgumentList '/SILENT' -Wait
    }
    Use-LocalRuntime $Root
    return [bool](Get-Command 'ollama' -ErrorAction SilentlyContinue)
}

# Desktop + Start-menu shortcut that starts the app (minimized window) and opens the browser.
function New-AppShortcuts([string]$Root) {
    if (-not $script:OnWindows) { return }
    # The shortcut's Arabic name, built from code points so this file stays ASCII.
    $name = -join ([char[]](0x0644, 0x0648, 0x062D, 0x0629, 0x0020, 0x0627, 0x0644, 0x0645, 0x0648, 0x0627, 0x0642, 0x0639))
    $shell = New-Object -ComObject WScript.Shell
    foreach ($folder in @([Environment]::GetFolderPath('Desktop'), [Environment]::GetFolderPath('Programs'))) {
        if (-not $folder) { continue }
        $shortcut = $shell.CreateShortcut((Join-Path $folder "$name.lnk"))
        $shortcut.TargetPath = Join-Path $Root 'local\start.bat'
        $shortcut.WorkingDirectory = $Root
        $shortcut.WindowStyle = 7
        $shortcut.IconLocation = (Join-Path $Root 'local\app.ico') + ',0'
        $shortcut.Save()
    }
}
