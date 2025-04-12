#!/bin/bash

# Create directories
mkdir -p database includes assets/css assets/js api pages admin logs

# Array of files in the correct order
files=(
  "database/schema.sql"
  "includes/config.php"
  "includes/db.php"
  "includes/functions.php"
  "assets/js/analytics.js"
  "api/sync.php"
  "assets/js/navigation.js"
  "assets/js/examples.js"
  "assets/css/style.css"
  "assets/js/app.js"
  "index.php"
  "pages/introduction.php"
  "pages/push-pull.php"
  "admin/index.php"
  "admin/admin-functions.php"
  "admin/login.php"
  "admin/logout.php"
  "assets/css/admin.css"
  "admin/playback.php"
  "admin/sessions.php"
  "admin/export.php"
  "pages/what-is.php"
  "pages/lies-deflection.php"
  "pages/sabotage.php"
  "pages/toxic-preference.php"
  "pages/trauma-responses.php"
  "pages/vicious-cycle.php"
  "pages/secure-vs.php" 
  "pages/healing.php"
  "pages/resources.php"
)

# Process each file
for file in "${files[@]}"; do
  # Create the file if it doesn't exist
  if [ ! -f "$file" ]; then
    touch "$file"
    echo "Created file: $file"
  else
    echo "File already exists: $file"
  fi
  
  # Open the file in code-server
  code-server "$file"
  
  # Wait for user to press Enter before continuing
  read -p "Press Enter to continue to the next file..." </dev/tty
done

echo "All files have been created and opened."