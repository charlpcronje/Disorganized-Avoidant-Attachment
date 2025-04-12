# .htaccess Configuration for CORS

This is an example .htaccess file that you can place in your web root directory to handle CORS issues. This can be used as an alternative to the vhost configuration if you don't have access to the server configuration.

```apache
# Enable CORS
<IfModule mod_headers.c>
    # Allow requests from any origin
    Header set Access-Control-Allow-Origin "*"
    
    # Or to restrict to specific domains:
    # Header set Access-Control-Allow-Origin "https://info.nade.webally.co.za"
    
    Header set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
    Header set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With, X-API-Key"
    Header set Access-Control-Allow-Credentials "true"
    
    # Handle preflight OPTIONS requests
    RewriteEngine On
    RewriteCond %{REQUEST_METHOD} OPTIONS
    RewriteRule ^(.*)$ $1 [R=200,L]
</IfModule>

# Redirect analytics API requests
RewriteEngine On
RewriteRule ^attachment-site/api/sync\.php$ /api/sync.php [L,R=301]

# Fix for missing CSS file
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_URI} ^/assets/css/normalize\.css$
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^(.*)$ /path/to/your/normalize.css [L]
</IfModule>
```

## How to Use

1. Save this file as `.htaccess` in your web root directory
2. Replace `/path/to/your/normalize.css` with the actual path to your normalize.css file
3. If you want to restrict CORS to specific domains, uncomment the appropriate line and set your domain

## Notes

- Make sure mod_headers and mod_rewrite are enabled on your server
- You may need to adjust the paths based on your specific directory structure
- This configuration assumes that your API endpoint should be at `/api/sync.php`
