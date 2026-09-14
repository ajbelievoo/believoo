<?php
namespace App\Console\Commands;
use App\Models\Bconnect\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BconnectCustomDomain extends Command
{
    protected $signature = 'bconnect:domain {company_id} {domain}';
    protected $description = 'Create Nginx vhost for a B-CONNECT company custom domain';

    public function handle()
    {
        $company = Company::findOrFail($this->argument('company_id'));
        $domain = $this->argument('domain');
        if (!filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            $this->error('Invalid domain');
            return 1;
        }

        $conf = <<<NGX
server {
    listen 80;
    listen 443 ssl http2;
    server_name {$domain};
    root /www/wwwroot/believoo/public;
    index index.php index.html;

    ssl_certificate /www/server/panel/vhost/cert/{$domain}/fullchain.pem;
    ssl_certificate_key /www/server/panel/vhost/cert/{$domain}/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256;

    location ~ /\.ht { deny all; }

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/tmp/php-cgi-82.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ .*\.(gif|jpg|jpeg|png|bmp|swf)$ { expires 30d; }
    location ~ .*\.(js|css)?$ { expires 1h; }
}
NGX;

        $path = "/www/server/panel/vhost/nginx/{$domain}.conf";
        File::put($path, $conf);
        $company->update(['domain' => $domain]);

        // Generate SSL certificate
        exec("cd /www/wwwroot/believoo && /usr/bin/php82 artisan bconnect:ssl {$domain} 2>&1", $sslOut, $sslRc);
        $this->info('SSL: ' . implode("\n", $sslOut));

        // Attempt nginx reload if possible
        exec('nginx -t 2>&1', $out, $rc);
        if ($rc === 0) {
            exec('nginx -s reload 2>&1');
            $this->info("Vhost created and Nginx reloaded for {$domain}");
        } else {
            $this->warn('Nginx config file written but test failed: ' . implode("\n", $out));
            $this->warn('Please run: nginx -s reload');
        }
        $this->info("DNS required: point {$domain} A record to this server IP.");
        return 0;
    }
}
