[CmdletBinding()]
param(
  [string]$ProjectRef = ""
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

  try {
    $uri = [Uri]$Url
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

$envPath = Join-Path $repoRoot '.env.local'
$envMap = Import-DotEnvFile -Path $envPath

if ([string]::IsNullOrWhiteSpace($ProjectRef)) {
  $ProjectRef = Resolve-ProjectRefFromUrl -Url $envMap['NEXT_PUBLIC_SUPABASE_URL']
}
if ([string]::IsNullOrWhiteSpace($ProjectRef)) {
  $ProjectRef = Resolve-ProjectRefFromUrl -Url $envMap['SUPABASE_URL']
}
if ([string]::IsNullOrWhiteSpace($ProjectRef)) {
  throw "Could not infer ProjectRef. Pass -ProjectRef <your_ref> (e.g. pukkmsnoforphqzglunq)."
}

if ([string]::IsNullOrWhiteSpace($env:POSTGRES_PASSWORD)) {
  throw "POSTGRES_PASSWORD is missing in .env.local"
}

Write-Host "Linking Supabase project '$ProjectRef'..." -ForegroundColor Cyan
# Use --password to avoid interactive prompt.
# Note: This does not print the password, but it does pass it as an argument to the CLI.
& $npx supabase link --project-ref $ProjectRef --password $env:POSTGRES_PASSWORD --yes
if ($LASTEXITCODE -ne 0) {
  throw "supabase link failed with exit code $LASTEXITCODE"
}

Write-Host "Pushing Supabase migrations..." -ForegroundColor Cyan
& $npx supabase db push --yes
if ($LASTEXITCODE -ne 0) {
  throw "supabase db push failed with exit code $LASTEXITCODE"
}

Write-Host "Done." -ForegroundColor Green
