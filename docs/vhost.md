# Apache Virtual Host Configuration for info.nade.webally.co.za

This configuration includes CORS headers to resolve the cross-origin issues with the analytics API.

```apache
# info.nade.webally.co.za
<VirtualHost 212.227.241.186:82>
    ServerName info.nade.webally.co.za
    ServerAdmin charl@webally.co.za
    DocumentRoot /var/www/html/nade.webally.co.za/web
    
    <Directory /var/www/html/nade.webally.co.za/web>
        Require all granted
        Options -Indexes +FollowSymLinks
        AllowOverride All
        
        # CORS Headers
        <IfModule mod_headers.c>
            # Allow requests from any origin (you can restrict this to specific domains if needed)
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
    </Directory>
    
    # Fix for missing CSS file
    Alias /assets/css/normalize.css /var/www/html/nade.webally.co.za/web/assets/css/normalize.css
    
    # Fix for analytics API
    # Option 1: Redirect analytics requests to the correct server
    RewriteEngine On
    RewriteRule ^/attachment-site/api/sync\.php$ /api/sync.php [L,R=301]
    
    # Option 2: Create a proxy for the analytics API
    # ProxyPass /attachment-site/api/sync.php http://localhost/api/sync.php
    # ProxyPassReverse /attachment-site/api/sync.php http://localhost/api/sync.php
    
    CustomLog /var/www/html/nade.webally.co.za/logs/access.log combined
    ErrorLog /var/www/html/nade.webally.co.za/logs/error.log
</VirtualHost>
```

## Additional Configuration for analytics.js

You may also need to update the `analytics.js` file to point to the correct API endpoint. Look for references to `http://localhost/attachment-site/api/sync.php` and change them to `/api/sync.php` or the correct path.

```javascript
// Example fix for analytics.js
this.apiEndpoint = window.BASE_URL + 'api/sync.php';  // Instead of 'attachment-site/api/sync.php'
```

## Notes

1. The CORS headers allow requests from any origin (`*`). For better security, you can restrict this to specific domains.
2. The configuration includes two options for fixing the analytics API issue:
   - Option 1: Redirect requests to the correct path
   - Option 2: Use a proxy to forward requests to the correct server
3. The missing CSS file is addressed with an Alias directive.
4. You may need to enable the required Apache modules:
   ```
   sudo a2enmod headers rewrite proxy proxy_http
   sudo systemctl restart apache2
   ```
