# Shared helpers for the Windows scripts (setup / start / update / create-admin). Dot-source it.
# Everything the app needs (PHP, Composer, Node.js) is downloaded into <app>\.runtime when a
# suitable version is not already installed - nothing is installed system-wide and PATH is only
# changed inside the current window. Ollama is the only real install (it has its own installer).
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
        if ((Test-Path $dir) -and -not (($env:PATH -split ';') -contains $dir)) { $env:PATH = "$dir;$env:PATH" }
    }
    if ($env:LOCALAPPDATA) {
        $ollamaDir = Join-Path $env:LOCALAPPDATA 'Programs\Ollama'
        if ((Test-Path (Join-Path $ollamaDir 'ollama.exe')) -and -not (Get-Command 'ollama' -ErrorAction SilentlyContinue)) {
            $env:PATH = "$env:PATH;$ollamaDir"
        }
    }
}

# Shows a message box on Windows (the app window is minimized, so console text alone is missed).
function Show-Error([string]$Message) {
    Write-Host "`nERROR: $Message" -ForegroundColor Red
    if ($script:OnWindows) {
        try { $null = (New-Object -ComObject WScript.Shell).Popup($Message, 0, 'Error', 16) } catch { }
    }
}

# A click inside a classic console window starts "Select" mode, which freezes every program
# writing to it - here the single-process PHP server, i.e. the whole app. Turn QuickEdit off.
function Disable-QuickEdit {
    if (-not $script:OnWindows) { return }
    try {
        if (-not ('ConsoleQuickEdit' -as [type])) {
            Add-Type -Name ConsoleQuickEdit -Namespace '' -MemberDefinition @'
[DllImport("kernel32.dll")] public static extern IntPtr GetStdHandle(int handle);
[DllImport("kernel32.dll")] public static extern bool GetConsoleMode(IntPtr handle, out uint mode);
[DllImport("kernel32.dll")] public static extern bool SetConsoleMode(IntPtr handle, uint mode);
'@
        }
        $handle = [ConsoleQuickEdit]::GetStdHandle(-10)
        $mode = [uint32]0
        if ([ConsoleQuickEdit]::GetConsoleMode($handle, [ref]$mode)) {
            $null = [ConsoleQuickEdit]::SetConsoleMode($handle, (($mode -band (-bnot [uint32]0x40)) -bor [uint32]0x80))
        }
    } catch { }
}

# curl.exe is the fast path; if it fails (system proxy it ignores, antivirus HTTPS scanning that
# breaks its certificate-revocation check, ...) Invoke-WebRequest gets a try as well.
function Get-Download([string]$Url, [string]$OutFile, [switch]$ShowProgress) {
    if (Test-Path $OutFile) { Remove-Item $OutFile -Force }
    $curl = Get-Command 'curl.exe' -ErrorAction SilentlyContinue
    if ($curl) {
        $quiet = if ($ShowProgress) { '-fSL' } else { '-fsSL' }
        & $curl.Source $quiet --ssl-revoke-best-effort --retry 3 -o $OutFile $Url
        if (($LASTEXITCODE -eq 0) -and (Test-Path $OutFile)) { return $true }
        if (Test-Path $OutFile) { Remove-Item $OutFile -Force -ErrorAction SilentlyContinue }
    }
    try {
        Invoke-WebRequest -Uri $Url -OutFile $OutFile -UseBasicParsing
        return (Test-Path $OutFile)
    } catch {
        return $false
    }
}

function Test-Sha256([string]$File, [string]$Expected) {
    if (-not $Expected -or -not $Expected.Trim()) { return $false }
    return ((Get-FileHash -Path $File -Algorithm SHA256).Hash -eq $Expected.Trim())
}

# Installers that run with elevation (VC++) or silently (Ollama) must carry a valid signature
# from their real publisher.
function Test-Signed([string]$File, [string]$Publisher) {
    if (-not $script:OnWindows) { return $true }
    try {
        $signature = Get-AuthenticodeSignature -FilePath $File
        return (($signature.Status -eq 'Valid') -and ($signature.SignerCertificate.Subject -match $Publisher))
    } catch {
        return $false
    }
}

# Waits for the process itself only. Start-Process -Wait waits for every descendant too, and the
# Ollama installer leaves its tray app running - that would wait forever.
function Invoke-Installer([string]$File, [string[]]$Arguments) {
    $process = Start-Process -FilePath $File -ArgumentList $Arguments -PassThru
    $null = $process.Handle   # keeps ExitCode readable after exit in Windows PowerShell 5.1
    $process.WaitForExit()
    return $process.ExitCode
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

# ---------------------------------------------------------------------------------- PHP ----

# PHP 8.3+ with every extension the app uses.
function Test-Php {
    if (-not (Get-Command 'php' -ErrorAction SilentlyContinue)) { return $false }
    $check = 'exit(PHP_VERSION_ID >= 80300 && extension_loaded(''pdo_sqlite'') && extension_loaded(''zip'') && extension_loaded(''mbstring'') && extension_loaded(''fileinfo'') && extension_loaded(''openssl'') ? 0 : 1);'
    & php -r $check 2>$null | Out-Null
    return ($LASTEXITCODE -eq 0)
}

# Current PHP 8.4 Windows builds are linked with Visual C++ 14.4x and refuse to start on an older
# runtime. Checked before php.exe ever runs: without the DLL at all, Windows shows a blocking
# "DLL not found" dialog instead of an error message.
function Test-VcRuntime {
    $system32 = Join-Path $env:SystemRoot 'System32'
    $dll = Join-Path $system32 'vcruntime140.dll'
    if (-not (Test-Path $dll) -or -not (Test-Path (Join-Path $system32 'vcruntime140_1.dll'))) { return $false }
    $version = (Get-Item $dll).VersionInfo
    return (($version.FileMajorPart -gt 14) -or (($version.FileMajorPart -eq 14) -and ($version.FileMinorPart -ge 40)))
}

function Install-VcRuntime {
    Write-Host 'Installing the Microsoft Visual C++ runtime that PHP needs (click Yes if Windows asks)...'
    $installer = Get-TempPath 'vc_redist.x64.exe'
    if (-not (Get-Download 'https://aka.ms/vs/17/release/vc_redist.x64.exe' $installer)) {
        Fail 'Could not download the Visual C++ runtime. Check the internet connection and run the installer again.'
    }
    if (-not (Test-Signed $installer 'Microsoft')) { Fail 'The Visual C++ runtime download is not signed by Microsoft - not running it.' }

    $code = Invoke-Installer $installer @('/install', '/quiet', '/norestart')
    if ($code -eq 3010 -or $code -eq 1641) { Fail 'Windows needs a restart to finish installing the Visual C++ runtime. Restart the computer, then run the installer again.' }
    if ($code -eq 1602 -or $code -eq 1223) { Fail 'The Windows permission prompt was declined. Run the installer again and click Yes when Windows asks.' }
    if ($code -ne 0 -and $code -ne 1638) { Fail "Installing the Visual C++ runtime failed (code $code). Restart the computer and run the installer again." }
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

    if ($script:OnWindows -and -not (Test-VcRuntime)) { Install-VcRuntime }

    Write-Host "Downloading $($build.path) ..."
    $zip = Get-TempPath $build.path
    if (-not (Get-Download "https://windows.php.net/downloads/releases/$($build.path)" $zip -ShowProgress)) { return $false }
    if (-not (Test-Sha256 $zip $build.sha256)) { Warn 'The PHP download is corrupted or could not be verified (checksum).'; return $false }

    $phpDir = Join-Path $Root '.runtime\php'
    if (Test-Path $phpDir) { Remove-Item $phpDir -Recurse -Force }
    Expand-Zip $zip $phpDir
    Write-PhpIni $phpDir
    Remove-Item $zip -Force -ErrorAction SilentlyContinue

    $php = Join-Path $phpDir 'php.exe'
    if (-not (Test-Path $php)) { return $false }
    if ($script:OnWindows) {
        # PHP itself refuses a runtime older than the one it was linked with - ask PHP, not the DLL.
        $output = & $php -n -v 2>&1 | Out-String
        if (($LASTEXITCODE -ne 0) -or ($output -match 'not compatible')) {
            Install-VcRuntime
            $output = & $php -n -v 2>&1 | Out-String
            if ($LASTEXITCODE -ne 0) { Warn $output; return $false }
        }
    }
    return $true
}

# ----------------------------------------------------------------------------- Composer ----

function Test-Composer {
    if (-not (Get-Command 'composer' -ErrorAction SilentlyContinue)) { return $false }
    # --no-interaction: its output is captured here, so a question from Composer would hang unseen.
    $version = (& composer --version --no-ansi --no-interaction 2>$null | Out-String)
    return ($version -match 'Composer (version )?([2-9])\.')
}

# Composer as a single composer.phar + a composer.bat next to it, so `composer` works as usual.
function Install-PortableComposer([string]$Root) {
    $dir = Join-Path $Root '.runtime\composer'
    New-Item -ItemType Directory -Force -Path $dir | Out-Null
    $phar = Join-Path $dir 'composer.phar'
    $sumFile = Get-TempPath 'composer.phar.sha256sum'

    # $true = verified, $false = checksum mismatch, $null = getcomposer.org unreachable.
    $verified = $null
    for ($attempt = 1; ($attempt -le 2) -and ($verified -ne $true); $attempt++) {
        if (-not ((Get-Download 'https://getcomposer.org/download/latest-stable/composer.phar' $phar) -and
                  (Get-Download 'https://getcomposer.org/download/latest-stable/composer.phar.sha256sum' $sumFile))) { break }
        $verified = Test-Sha256 $phar (((Get-Content $sumFile -Raw).Trim() -split '\s+')[0])
    }

    if ($verified -eq $false) { Warn 'The Composer download is corrupted (checksum mismatch).'; return $false }
    if ($null -eq $verified) {
        # getcomposer.org unreachable: the same file from Composer's GitHub releases (HTTPS, but
        # GitHub publishes no checksum for it).
        if (-not (Get-Download 'https://github.com/composer/composer/releases/latest/download/composer.phar' $phar)) { return $false }
        Warn 'getcomposer.org was unreachable - using Composer from its GitHub releases page (checksum not available there).'
    }

    [IO.File]::WriteAllText((Join-Path $dir 'composer.bat'), "@php `"%~dp0composer.phar`" %*`r`n")
    return $true
}

# ------------------------------------------------------------------------------ Node.js ----

# The build tools (Vite 8) need Node.js 20.19+ or 22.12+.
function Test-Node {
    if (-not (Get-Command 'node' -ErrorAction SilentlyContinue) -or -not (Get-Command 'npm' -ErrorAction SilentlyContinue)) { return $false }
    try { $version = [version]((& node -v 2>$null | Out-String).Trim().TrimStart('v')) } catch { return $false }
    return ((($version.Major -eq 20) -and ($version.Minor -ge 19)) -or (($version.Major -eq 22) -and ($version.Minor -ge 12)) -or ($version.Major -gt 22))
}

# Latest Node.js LTS from nodejs.org (portable zip, checksum-verified).
function Install-PortableNode([string]$Root) {
    $index = Get-TempPath 'node-index.json'
    if (-not (Get-Download 'https://nodejs.org/dist/index.json' $index)) { return $false }

    try { $releases = @(Get-Content $index -Raw | ConvertFrom-Json) } catch { return $false }
    # 5.1 hands a JSON array over as one object - @() + ForEach-Object makes sure every release is seen.
    $lts = $null
    foreach ($release in ($releases | ForEach-Object { $_ })) {
        if ($release.lts -and ($release.files -contains 'win-x64-zip')) { $lts = $release; break }
    }
    if (-not $lts) { return $false }

    $name = "node-$($lts.version)-win-x64"
    Write-Host "Downloading $name.zip ..."
    $zip = Get-TempPath "$name.zip"
    $sums = Get-TempPath 'node-SHASUMS256.txt'
    if (-not (Get-Download "https://nodejs.org/dist/$($lts.version)/$name.zip" $zip -ShowProgress)) { return $false }
    if (-not (Get-Download "https://nodejs.org/dist/$($lts.version)/SHASUMS256.txt" $sums)) { Warn 'Could not download the Node.js checksums.'; return $false }
    $line = Get-Content $sums | Where-Object { $_ -match "\s$([regex]::Escape("$name.zip"))$" } | Select-Object -First 1
    if (-not $line -or -not (Test-Sha256 $zip (($line.Trim() -split '\s+')[0]))) { Warn 'The Node.js download is corrupted or could not be verified (checksum).'; return $false }

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

# PHP, Composer and Node.js for this app: the ones already installed when they are good enough,
# otherwise a private copy in .runtime. Stops with a clear message when something can't be set up.
function Initialize-Runtime([string]$Root) {
    $herdHint = 'Or install Laravel Herd (https://herd.laravel.com/windows) and run the installer again.'

    Say 'PHP'
    if (Test-Php) {
        Write-Host "Using $((Get-Command 'php').Source)"
    } else {
        Write-Host 'Not found (or too old) - downloading a private copy for this app...'
        if (-not (Install-PortablePhp $Root)) { Fail "Could not set up PHP automatically. Check the internet connection and run the installer again. $herdHint" }
        Use-LocalRuntime $Root
        if (-not (Test-Php)) { Fail "The downloaded PHP does not start correctly. $herdHint" }
    }

    Say 'Composer'
    if (Test-Composer) {
        Write-Host 'Already installed.'
    } else {
        if (-not (Install-PortableComposer $Root)) { Fail "Could not set up Composer. Check the internet connection and run the installer again. $herdHint" }
        Use-LocalRuntime $Root
    }

    Say 'Node.js'
    if (Test-Node) {
        Write-Host "Using $(& node -v)"
    } else {
        Write-Host 'Not found (or older than 20.19) - downloading a private copy for this app...'
        if (-not (Install-PortableNode $Root)) { Fail 'Could not set up Node.js. Check the internet connection and run the installer again.' }
        Use-LocalRuntime $Root
        if (-not (Test-Node)) { Fail 'The downloaded Node.js does not start correctly.' }
    }
}

# ------------------------------------------------------------------------------- Ollama ----

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
    $winget = Get-Command 'winget' -ErrorAction SilentlyContinue
    if ($winget) {
        # Straight to this console (its progress stays visible) and never into this function's
        # return value. Waits for winget only - see Invoke-Installer.
        $process = Start-Process -FilePath $winget.Source -NoNewWindow -PassThru -ArgumentList @(
            'install', '--id', 'Ollama.Ollama', '-e', '--source', 'winget', '--accept-package-agreements', '--accept-source-agreements')
        $null = $process.Handle
        $process.WaitForExit()
    }
    Use-LocalRuntime $Root
    if (Get-Command 'ollama' -ErrorAction SilentlyContinue) { return $true }

    Write-Host 'Downloading the Ollama installer (large download, please wait)...'
    $installer = Get-TempPath 'OllamaSetup.exe'
    if (-not (Get-Download 'https://ollama.com/download/OllamaSetup.exe' $installer -ShowProgress)) { return $false }
    if (-not (Test-Signed $installer 'Ollama')) { Warn 'The Ollama installer is not signed by Ollama - not running it.'; return $false }
    $null = Invoke-Installer $installer @('/VERYSILENT', '/SUPPRESSMSGBOXES', '/NORESTART')
    Remove-Item $installer -Force -ErrorAction SilentlyContinue
    Use-LocalRuntime $Root
    return [bool](Get-Command 'ollama' -ErrorAction SilentlyContinue)
}

# ----------------------------------------------------------------------- Admin account ----

function ConvertFrom-SecureText([Security.SecureString]$Secure) {
    $pointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($Secure)
    try { return [Runtime.InteropServices.Marshal]::PtrToStringBSTR($pointer) } finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($pointer) }
}

function ConvertTo-Base64Utf8([string]$Text) {
    return [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes([string]$Text))
}

# Asks for the login here (Read-Host reads any language correctly; PHP's hidden prompt on
# Windows needs the Visual C++ 2008 runtime and garbles non-English input) and hands it to
# `artisan barq:create-admin --stdin` through a pipe - never a file or an environment variable.
function Invoke-AdminAccountPrompt {
    for ($attempt = 1; $attempt -le 3; $attempt++) {
        Write-Host ''
        Write-Host 'Your login for the app (you will type it in the browser):' -ForegroundColor White
        $name = Read-Host 'Name (or just press Enter)'
        $email = Read-Host 'Email'
        $first = ConvertFrom-SecureText (Read-Host 'Password, 8 or more characters (hidden while you type)' -AsSecureString)
        $second = ConvertFrom-SecureText (Read-Host 'The same password again' -AsSecureString)

        if ($first -cne $second) { Warn 'The two passwords are not the same - try again.'; continue }
        if ($first.Length -lt 8) { Warn 'The password must be at least 8 characters - try again.'; continue }

        $payload = (@($name, $email, $first) | ForEach-Object { ConvertTo-Base64Utf8 $_ }) -join "`n"
        $first = $null; $second = $null
        # Out-Host: PHP's messages go to the screen, not into this function's return value.
        $payload | & php artisan barq:create-admin --stdin | Out-Host
        $succeeded = ($LASTEXITCODE -eq 0)
        $payload = $null
        if ($succeeded) { return $true }
    }
    return $false
}

# ---------------------------------------------------------------- Folder and shortcuts ----

# A folder created at C:\ inherits "Authenticated Users: Modify" - any other account on the PC
# could read the database and change the program files. Keep it to this user, SYSTEM and Admins.
function Protect-AppFolder([string]$Root) {
    if (-not $script:OnWindows) { return }
    try {
        if ((Get-Acl -LiteralPath $Root).AreAccessRulesProtected) { return }
        $acl = New-Object System.Security.AccessControl.DirectorySecurity
        $acl.SetAccessRuleProtection($true, $false)
        $inherit = [System.Security.AccessControl.InheritanceFlags]'ContainerInherit, ObjectInherit'
        $owners = @(
            [Security.Principal.WindowsIdentity]::GetCurrent().User,
            (New-Object Security.Principal.SecurityIdentifier 'S-1-5-18'),
            (New-Object Security.Principal.SecurityIdentifier 'S-1-5-32-544')
        )
        foreach ($sid in $owners) {
            $acl.AddAccessRule((New-Object System.Security.AccessControl.FileSystemAccessRule($sid, 'FullControl', $inherit, 'None', 'Allow')))
        }
        (Get-Item -LiteralPath $Root).SetAccessControl($acl)
    } catch {
        Warn "Could not restrict access to $Root to your account (not required): $($_.Exception.Message)"
    }
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

# Port: BARQ_PORT if set, else the one in APP_URL in .env (so changing it there is enough), else 8010.
function Get-AppPort([string]$Root) {
    if ($env:BARQ_PORT) { return $env:BARQ_PORT }
    $line = Get-Content (Join-Path $Root '.env') -Encoding UTF8 -ErrorAction SilentlyContinue |
        Where-Object { $_ -match '^APP_URL=' } | Select-Object -Last 1
    if ($line -match ':(\d{2,5})/?\s*["'']?\s*$') { return $Matches[1] }
    return '8010'
}
