@echo off
REM HostCheckr Release Builder for Windows
REM Creates a clean zip file for distribution

setlocal enabledelayedexpansion

set PLUGIN_SLUG=hostcheckr
set VERSION=1.0.0
set OUTPUT_FILE=%PLUGIN_SLUG%-%VERSION%.zip

echo.
echo ========================================
echo   HostCheckr Release Builder v%VERSION%
echo ========================================
echo.

REM Check if PowerShell is available
where powershell >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: PowerShell is required but not found.
    echo Please install PowerShell or use create-release.sh with Git Bash.
    pause
    exit /b 1
)

echo Creating release package...
echo.

REM Use PowerShell to create zip
powershell -Command "& { ^
    $source = Get-Location; ^
    $tempDir = Join-Path $env:TEMP 'hostcheckr-build'; ^
    $buildDir = Join-Path $tempDir '%PLUGIN_SLUG%'; ^
    if (Test-Path $tempDir) { Remove-Item $tempDir -Recurse -Force }; ^
    New-Item -ItemType Directory -Path $buildDir -Force | Out-Null; ^
    Write-Host 'Copying plugin files...'; ^
    $exclude = @('.git', '.gitignore', '.wordpress-playground', 'node_modules', '.DS_Store', '*.log', 'create-release.sh', 'create-release.bat', 'gitpush.sh', '%PLUGIN_SLUG%-*.zip'); ^
    Get-ChildItem -Path $source -Exclude $exclude | Copy-Item -Destination $buildDir -Recurse -Force; ^
    Write-Host 'Creating zip archive...'; ^
    $zipPath = Join-Path $source '%OUTPUT_FILE%'; ^
    if (Test-Path $zipPath) { Remove-Item $zipPath -Force }; ^
    Compress-Archive -Path (Join-Path $tempDir '*') -DestinationPath $zipPath -Force; ^
    Write-Host 'Cleaning up...'; ^
    Remove-Item $tempDir -Recurse -Force; ^
    Write-Host ''; ^
    Write-Host 'Release created successfully: %OUTPUT_FILE%' -ForegroundColor Green; ^
}"

echo.
echo ========================================
echo   Release Complete!
echo ========================================
echo.
echo File created: %OUTPUT_FILE%
echo.
echo Next steps:
echo   1. Test the zip file locally
echo   2. Upload to WordPress.org or GitHub releases
echo   3. Test in WordPress Playground
echo.
echo Test in Playground:
echo   https://playground.wordpress.net/
echo.
pause
