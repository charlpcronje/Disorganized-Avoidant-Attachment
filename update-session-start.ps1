$files = @(
    "..\admin\examples.php",
    "..\admin\export.php",
    "..\admin\index.php",
    "..\admin\logout.php",
    "..\admin\playback.php",
    "..\admin\sessions.php"
)

foreach ($file in $files) {
    $content = Get-Content -Path $file -Raw
    
    # Replace session_start() with a check
    $pattern = '(?m)^session_start\(\);'
    $replacement = 'if (session_status() == PHP_SESSION_NONE) {
    session_start();
}'
    
    $newContent = $content -replace $pattern, $replacement
    
    # Save the file
    Set-Content -Path $file -Value $newContent
    
    Write-Host "Updated $file"
}

Write-Host "All files updated!"
