<#
.SYNOPSIS
    Copies the shared backend from one template to the others, leaving each template's design untouched.

.DESCRIPTION
    All five templates run the same backend (models, services, actions, policies, Livewire component
    classes, migrations, seeders, routes, config and tests). Only the presentation layer differs:
    resources/ (views, CSS, JS), vite.config.js, DESIGN.md, README.md and a few template-only files.

    Fix or extend the backend in ONE template (usually template_1), run its tests, then run this
    script to copy the change to the others. Afterwards run the tests in every template.

    Copied:      app, bootstrap, config, database (no *.sqlite), routes, tests, lang/ar + lang/en (PHP group files)
    Merged:      lang/ar.json — keys from the source are added/updated, template-only keys are kept
    Never copied: resources/, public/, vite.config.js, package*.json, composer.json/lock, .env, DESIGN.md,
                 README.md and the template-only paths listed in $TemplateOnly below.

.PARAMETER From
    Source template number (default 1).

.PARAMETER To
    Target template numbers (default: every other template).

.PARAMETER DryRun
    Only list what would change.

.PARAMETER Mirror
    Also delete files in the target that no longer exist in the source (template-only paths are protected).

.EXAMPLE
    ./tools/sync-backend.ps1 -DryRun
    ./tools/sync-backend.ps1 -From 1 -To 2,3
#>
param(
    [int]$From = 1,
    [int[]]$To = @(),
    [switch]$DryRun,
    [switch]$Mirror
)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$source = Join-Path $root "template_$From"

if (-not (Test-Path $source)) { throw "Source template not found: $source" }
if ($To.Count -eq 0) { $To = 1..5 | Where-Object { $_ -ne $From } }

# Shared backend folders.
$folders = @('app', 'bootstrap', 'config', 'database', 'routes', 'tests', 'lang\ar', 'lang\en')

# Paths that belong to a single template and must never be copied or deleted (relative to the template root).
$TemplateOnly = @(
    'app\Livewire\Ui',                       # template 2 command palette
    'tests\Feature\CommandPaletteTest.php'   # template 2 command palette tests
)

# Files that never travel between templates.
$excludeFiles = @('*.sqlite', '*.sqlite-journal', 'CommandPaletteTest.php')
$excludeDirs = @('Ui', 'cache')

function Merge-Json([string]$sourceFile, [string]$targetFile) {
    # Done in PHP: PowerShell 5.1's JSON parser is case-insensitive ("Choose Image" vs "Choose image")
    # and writes a BOM, which breaks Laravel. Keys only the target has are kept.
    $phpArgs = @((Join-Path $PSScriptRoot 'merge-json.php'), $sourceFile, $targetFile)
    if ($DryRun) { $phpArgs += '--dry-run' }
    $out = & php @phpArgs
    if ($LASTEXITCODE -ne 0) { throw "Merging $targetFile failed" }
    return $out
}

foreach ($n in $To) {
    $target = Join-Path $root "template_$n"
    if (-not (Test-Path $target)) { Write-Warning "Skipping missing template_$n"; continue }

    Write-Host "`n=== template_$From -> template_$n ===" -ForegroundColor Cyan

    foreach ($folder in $folders) {
        $s = Join-Path $source $folder
        $d = Join-Path $target $folder
        if (-not (Test-Path $s)) { continue }

        $args = @($s, $d, '/E', '/NJH', '/NJS', '/NP', '/NDL', '/XF') + $excludeFiles + @('/XD') + $excludeDirs
        if ($Mirror) { $args += '/PURGE' }
        if ($DryRun) { $args += '/L' }

        & robocopy @args | Where-Object { $_.Trim() -ne '' } | ForEach-Object { Write-Host "  $folder $($_.Trim())" }
        if ($LASTEXITCODE -ge 8) { throw "robocopy failed for $folder (exit $LASTEXITCODE)" }
    }

    $count = Merge-Json (Join-Path $source 'lang\ar.json') (Join-Path $target 'lang\ar.json')
    Write-Host "  lang\ar.json: $count key(s) added or updated"
}

Write-Host "`nDone. Template-only paths were left alone: $($TemplateOnly -join ', ')" -ForegroundColor Green
Write-Host 'Next: composer dump-autoload, php artisan view:clear and ./vendor/bin/pest in every updated template.'
if ($DryRun) { Write-Host '(dry run - nothing was written)' -ForegroundColor Yellow }
