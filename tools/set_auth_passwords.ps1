$ErrorActionPreference = 'Stop'
$phpPath = $env:XAMPP_PHP_CLI
if (-not $phpPath) {
    $phpCommand = Get-Command php -ErrorAction SilentlyContinue
    if ($phpCommand) { $phpPath = $phpCommand.Source }
}
$writerPath = Join-Path $PSScriptRoot 'write_auth_hashes.php'
if (-not $phpPath -or -not (Test-Path -LiteralPath $phpPath) -or -not (Test-Path -LiteralPath $writerPath)) {
    throw 'PHP CLI was not found. Set XAMPP_PHP_CLI to its executable path or add php to PATH.'
}

function Read-PasswordText([string]$Prompt) {
    $secure = Read-Host -Prompt $Prompt -AsSecureString
    $pointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure)
    try { [Runtime.InteropServices.Marshal]::PtrToStringBSTR($pointer) }
    finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($pointer) }
}

$adminPassword = Read-PasswordText 'Administrator password (12+ characters)'
$staffPassword = Read-PasswordText 'Staff password (12+ characters)'
if ($adminPassword -ne $staffPassword) {
    $processInfo = [Diagnostics.ProcessStartInfo]::new($phpPath)
    $processInfo.Arguments = '"' + $writerPath + '"'
    $processInfo.UseShellExecute = $false
    $processInfo.CreateNoWindow = $true
    $processInfo.RedirectStandardInput = $true
    $processInfo.RedirectStandardOutput = $true
    $processInfo.RedirectStandardError = $true
    $process = [Diagnostics.Process]::Start($processInfo)
    $process.StandardInput.WriteLine($adminPassword)
    $process.StandardInput.WriteLine($staffPassword)
    $process.StandardInput.Close()
    $result = $process.StandardOutput.ReadToEnd()
    $errors = $process.StandardError.ReadToEnd()
    $process.WaitForExit()
    $adminPassword = $null
    $staffPassword = $null
    if ($process.ExitCode -ne 0) { throw $errors.Trim() }
    Write-Output 'Copy these generated hashes directly into the matching Render environment variables. Do not save this output in the project.'
    Write-Output $result.Trim()
} else {
    $adminPassword = $null
    $staffPassword = $null
    throw 'Administrator and Staff passwords must be different.'
}
