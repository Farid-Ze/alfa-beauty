[CmdletBinding()]
param(
  [string]$ProjectRef = "",
  [switch]$Link,
  [switch]$NonInteractive,
  [switch]$UseEnvPassword
)

$ErrorActionPreference = 'Stop'

function Import-DotEnvFile {
  param([Parameter(Mandatory = $true)][string]$Path)

  $values = @{}

  if (-not (Test-Path -LiteralPath $Path)) {
    throw "Missing env file: $Path"
  }

  Get-Content -LiteralPath $Path | ForEach-Object {
    $line = $_.Trim()
    if ($line.Length -eq 0) { return }
    if ($line.StartsWith('#')) { return }

    $eq = $line.IndexOf('=')
    if ($eq -lt 1) { return }

    $key = $line.Substring(0, $eq).Trim()
    # Handle UTF-8 BOM that can appear at start of file
    $key = $key.TrimStart([char]0xFEFF)
    $val = $line.Substring($eq + 1).Trim()

    # Strip optional surrounding quotes
    if ($val.Length -ge 2 -and (($val.StartsWith('"') -and $val.EndsWith('"')) -or ($val.StartsWith("'") -and $val.EndsWith("'")))) {
      $val = $val.Substring(1, $val.Length - 2)
    }

    $values[$key] = $val
    [System.Environment]::SetEnvironmentVariable($key, $val, 'Process')
  }

  return $values
}

function Resolve-ProjectRefFromUrl {
  param([string]$Url)

  if ([string]::IsNullOrWhiteSpace($Url)) { return "" }

  $candidate = $Url.Trim()
  # Strip optional surrounding quotes
  if ($candidate.Length -ge 2 -and (($candidate.StartsWith('"') -and $candidate.EndsWith('"')) -or ($candidate.StartsWith("'") -and $candidate.EndsWith("'")))) {
    $candidate = $candidate.Substring(1, $candidate.Length - 2)
  }

  try {
    $uri = [Uri]$candidate
    # https://<ref>.supabase.co
    $host = $uri.Host
    if ($host.EndsWith('.supabase.co')) {
      return $host.Substring(0, $host.Length - '.supabase.co'.Length)
    }
  } catch {
    return ""
  }

  return ""
}

$repoRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path
Set-Location -LiteralPath $repoRoot

$npxCmd = (Get-Command npx.cmd -ErrorAction SilentlyContinue)
$npx = if ($npxCmd) { $npxCmd.Source } else { 'npx' }

$envMap = @{}
$envPath = Join-Path $repoRoot '.env.local'
if (Test-Path -LiteralPath $envPath) {
  # Only import if explicitly requested. Avoid pulling secrets into process env by default.
  if ($UseEnvPassword -or [string]::IsNullOrWhiteSpace($ProjectRef)) {
    $envMap = Import-DotEnvFile -Path $envPath
  }
}

if ([string]::IsNullOrWhiteSpace($ProjectRef)) {
  if ($envMap.ContainsKey('NEXT_PUBLIC_SUPABASE_URL')) {
    $ProjectRef = Resolve-ProjectRefFromUrl -Url $envMap['NEXT_PUBLIC_SUPABASE_URL']
  }
}
if ([string]::IsNullOrWhiteSpace($ProjectRef)) {
  if ($envMap.ContainsKey('SUPABASE_URL')) {
    $ProjectRef = Resolve-ProjectRefFromUrl -Url $envMap['SUPABASE_URL']
  }
}
if ([string]::IsNullOrWhiteSpace($ProjectRef)) {
  throw "Could not infer ProjectRef. Pass -ProjectRef <your_ref> (e.g. pukkmsnoforphqzglunq)."
}

# Decide whether to link:
# - If -Link is passed, always link.
# - Otherwise, skip linking (assumes already linked) to avoid needing DB password.
$shouldLink = $Link.IsPresent

if ($shouldLink) {
  $dbPassword = $null

  if ($UseEnvPassword) {
    $dbPassword = $env:POSTGRES_PASSWORD
  }

  if ([string]::IsNullOrWhiteSpace($dbPassword)) {
    $secure = Read-Host -Prompt "Enter Supabase DB password (POSTGRES_PASSWORD)" -AsSecureString
    $bstr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure)
    try {
      $dbPassword = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($bstr)
    } finally {
      [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($bstr)
    }
  }

  if ([string]::IsNullOrWhiteSpace($dbPassword)) {
    throw "Database password is required to link. Provide -UseEnvPassword or enter it when prompted."
  }

  Write-Host "Linking Supabase project '$ProjectRef'..." -ForegroundColor Cyan
  # Note: password passed as an argument to the CLI; avoid printing it.
  & $npx supabase link --project-ref $ProjectRef --password $dbPassword
  if ($LASTEXITCODE -ne 0) {
    throw "supabase link failed with exit code $LASTEXITCODE"
  }
}

Write-Host "Pushing Supabase migrations..." -ForegroundColor Cyan
if ($NonInteractive) {
  # Some Supabase CLI versions still prompt even when flags are provided.
  # Force a 'yes' confirmation via stdin.
  $psi = New-Object System.Diagnostics.ProcessStartInfo
  $psi.FileName = $npx
  $psi.Arguments = "supabase db push"
  $psi.RedirectStandardInput = $true
  $psi.RedirectStandardOutput = $true
  $psi.RedirectStandardError = $true
  $psi.UseShellExecute = $false

  $p = New-Object System.Diagnostics.Process
  $p.StartInfo = $psi
  [void]$p.Start()
  $p.StandardInput.WriteLine('y')
  $p.StandardInput.Close()
  $stdout = $p.StandardOutput.ReadToEnd()
  $stderr = $p.StandardError.ReadToEnd()
  $p.WaitForExit()

  if ($stdout) { Write-Host $stdout }
  if ($stderr) { Write-Host $stderr }
  if ($p.ExitCode -ne 0) {
    throw "supabase db push failed with exit code $($p.ExitCode)"
  }
} else {
  & $npx supabase db push
  if ($LASTEXITCODE -ne 0) {
    throw "supabase db push failed with exit code $LASTEXITCODE"
  }
}

Write-Host "Done." -ForegroundColor Green
