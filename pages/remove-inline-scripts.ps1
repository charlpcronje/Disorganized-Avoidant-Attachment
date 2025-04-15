$pages = @(
    "healing.php",
    "sabotage.php",
    "secure-vs.php",
    "toxic-preference.php",
    "trauma-responses.php",
    "vicious-cycle.php"
)

foreach ($page in $pages) {
    $content = Get-Content -Path $page -Raw
    
    # Replace the script with a comment
    $pattern = '(?s)<script>\s*document\.addEventListener\(''DOMContentLoaded'', function \(\) \{\s*const exampleContainers = document\.querySelectorAll\(''.example-container''\);.*?</script>'
    $replacement = ""
    
    $newContent = $content -replace $pattern, $replacement
    
    # Save the file
    Set-Content -Path $page -Value $newContent
    
    Write-Host "Processed $page"
}

Write-Host "All done!"
