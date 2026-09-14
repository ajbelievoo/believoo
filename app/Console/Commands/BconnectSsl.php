<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BconnectSsl extends Command
{
    protected $signature = 'bconnect:ssl {domain}';
    protected $description = 'Create SSL certificate for a B-CONNECT custom domain';

    public function handle()
    {
        $domain = $this->argument('domain');
        $certDir = "/www/server/panel/vhost/cert/{$domain}";
        File::makeDirectory($certDir, 0755, true, true);

        $key = "{$certDir}/privkey.pem";
        $cert = "{$certDir}/fullchain.pem";

        // Try acme.sh if available
        $acme = shell_exec('which acme.sh 2>/dev/null') ?: (file_exists('/root/.acme.sh/acme.sh') ? '/root/.acme.sh/acme.sh' : '');
        if ($acme) {
            exec("{$acme} --issue -d {$domain} --nginx --force 2>&1", $out, $rc);
            if ($rc === 0) {
                exec("{$acme} --install-cert -d {$domain} --key-file {$key} --fullchain-file {$cert} --reloadcmd 'nginx -s reload' 2>&1", $out2, $rc2);
                if ($rc2 === 0) {
                    $this->info("SSL issued via acme.sh for {$domain}");
                    return 0;
                }
            }
            $this->warn('acme.sh attempt failed: ' . implode("\n", $out));
        }

        // Fallback to self-signed for local/testing
        $conf = <<<CNF
[req]
distinguished_name = req_distinguished_name
x509_extensions = v3_req
prompt = no
[req_distinguished_name]
CN = {$domain}
[v3_req]
keyUsage = keyEncipherment, dataEncipherment
extendedKeyUsage = serverAuth
subjectAltName = @alt_names
[alt_names]
DNS.1 = {$domain}
DNS.2 = www.{$domain}
CNF;
        $cnfPath = "{$certDir}/req.cnf";
        File::put($cnfPath, $conf);
        exec("openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout {$key} -out {$cert} -config {$cnfPath} 2>&1", $out, $rc);
        if ($rc === 0) {
            $this->info("Self-signed SSL created for {$domain}. Replace with real cert from acme.sh/Let's Encrypt for production.");
            $this->warn("Files: key={$key} cert={$cert}");
            exec('nginx -s reload 2>&1');
            return 0;
        }
        $this->error('SSL creation failed: ' . implode("\n", $out));
        return 1;
    }
}
