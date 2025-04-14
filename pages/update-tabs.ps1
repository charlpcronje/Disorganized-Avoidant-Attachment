$pages = @(
    "bpd-attachment.php",
    "healing.php",
    "introduction.php",
    "lies-deflection.php",
    "push-pull.php",
    "real-way-forward.php",
    "resources.php",
    "sabotage.php",
    "secure-vs.php",
    "toxic-preference.php",
    "trauma-responses.php",
    "vicious-cycle.php",
    "what-is.php"
)

foreach ($page in $pages) {
    $content = Get-Content -Path $page -Raw
    
    # Replace tab content with style="display: block;" with active class
    $pattern1 = '<div class="tab-content" data-tab="research" style="display: block;">'
    $replacement1 = '<div class="tab-content active" data-tab="research">'
    
    # Replace tab content with style="display: none;" with no active class
    $pattern2 = '<div class="tab-content" data-tab="personal" style="display: none;">'
    $replacement2 = '<div class="tab-content" data-tab="personal">'
    
    # Replace tab content with active class and style
    $pattern3 = '<div class="tab-content active" data-tab="research" style="display: block;">'
    $replacement3 = '<div class="tab-content active" data-tab="research">'
    
    # Replace tab content with no active class and style="display: none;"
    $pattern4 = '<div class="tab-content" data-tab="personal" style="display: none;">'
    $replacement4 = '<div class="tab-content" data-tab="personal">'
    
    # Apply replacements
    $newContent = $content -replace $pattern1, $replacement1 -replace $pattern2, $replacement2 -replace $pattern3, $replacement3 -replace $pattern4, $replacement4
    
    # Save the file
    Set-Content -Path $page -Value $newContent
    
    Write-Host "Processed $page"
}

Write-Host "All done!"
