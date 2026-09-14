<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DnsScannerController extends Controller
{
    /**
     * Show DNS scanner tool
     */
    public function index()
    {
        return view('client.dns-scanner.index');
    }
    
    /**
     * Scan DNS records from a domain
     */
    public function scan(Request $request)
    {
        $validated = $request->validate([
            'domain' => 'required|domain',
            'record_types' => 'nullable|array',
            'record_types.*' => 'in:A,AAAA,CNAME,MX,TXT,NS,SOA,SRV,CAA',
        ]);
        
        $domain = $validated['domain'];
        $recordTypes = $validated['record_types'] ?? ['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'NS'];
        
        try {
            $records = $this->fetchDnsRecords($domain, $recordTypes);
            
            Log::info('DNS scan completed', [
                'domain' => $domain,
                'records_found' => count($records),
            ]);
            
            return response()->json([
                'success' => true,
                'domain' => $domain,
                'records' => $records,
                'summary' => $this->summarizeRecords($records),
            ]);
            
        } catch (\Exception $e) {
            Log::error('DNS scan failed', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Scan failed: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Auto-migrate DNS records to BelieVoo nameservers
     */
    public function migrate(Request $request)
    {
        $validated = $request->validate([
            'domain' => 'required|domain',
            'selected_records' => 'required|array',
            'target_ip' => 'required|ip',
        ]);
        
        $domain = $validated['domain'];
        $selectedRecords = $validated['selected_records'];
        $targetIp = $validated['target_ip'];
        
        try {
            // Generate new records with BelieVoo infrastructure
            $migratedRecords = [];
            
            foreach ($selectedRecords as $record) {
                if ($record['type'] === 'A') {
                    $migratedRecords[] = [
                        'type' => 'A',
                        'name' => $record['name'],
                        'value' => $targetIp,
                        'ttl' => 3600,
                        'old_value' => $record['value'],
                    ];
                } elseif ($record['type'] === 'MX') {
                    // Keep MX records but update if pointing to old server
                    $migratedRecords[] = [
                        'type' => 'MX',
                        'name' => $record['name'],
                        'value' => $record['value'],
                        'priority' => $record['priority'] ?? 10,
                        'ttl' => 3600,
                    ];
                } elseif ($record['type'] === 'TXT') {
                    // Preserve TXT records (SPF, DKIM, etc)
                    $migratedRecords[] = [
                        'type' => 'TXT',
                        'name' => $record['name'],
                        'value' => $record['value'],
                        'ttl' => 3600,
                    ];
                }
            }
            
            // Add BelieVoo default NS records
            $migratedRecords[] = [
                'type' => 'NS',
                'name' => '@',
                'value' => 'ns1.believoo.com',
                'ttl' => 86400,
            ];
            $migratedRecords[] = [
                'type' => 'NS',
                'name' => '@',
                'value' => 'ns2.believoo.com',
                'ttl' => 86400,
            ];
            
            return response()->json([
                'success' => true,
                'message' => 'DNS migration plan generated!',
                'domain' => $domain,
                'migrated_records' => $migratedRecords,
                'nameservers' => ['ns1.believoo.com', 'ns2.believoo.com'],
                'next_steps' => [
                    '1. Update nameservers at your registrar',
                    '2. Wait for DNS propagation (24-48 hours)',
                    '3. Verify records with our DNS checker',
                ],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Migration failed: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Export DNS records as zone file
     */
    public function export(Request $request)
    {
        $validated = $request->validate([
            'records' => 'required|array',
            'domain' => 'required',
        ]);
        
        $zoneContent = $this->generateZoneFile($validated['domain'], $validated['records']);
        
        return response($zoneContent)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', 'attachment; filename="' . $validated['domain'] . '.zone"');
    }
    
    /**
     * Fetch DNS records using PHP's dns_get_record
     */
    private function fetchDnsRecords(string $domain, array $types): array
    {
        $records = [];
        
        foreach ($types as $type) {
            $results = @dns_get_record($domain, constant('DNS_' . $type));
            
            if ($results) {
                foreach ($results as $result) {
                    $records[] = [
                        'type' => $result['type'],
                        'name' => $result['host'],
                        'value' => $this->getRecordValue($result),
                        'ttl' => $result['ttl'] ?? 3600,
                        'priority' => $result['pri'] ?? null,
                        'class' => $result['class'] ?? 'IN',
                    ];
                }
            }
        }
        
        // Remove duplicates
        $records = array_unique($records, SORT_REGULAR);
        
        return $records;
    }
    
    /**
     * Get record value based on type
     */
    private function getRecordValue(array $record): string
    {
        switch ($record['type']) {
            case 'A':
                return $record['ip'] ?? '';
            case 'AAAA':
                return $record['ipv6'] ?? '';
            case 'CNAME':
                return $record['target'] ?? '';
            case 'MX':
                return ($record['pri'] ?? 0) . ' ' . ($record['target'] ?? '');
            case 'TXT':
                return $record['txt'] ?? '';
            case 'NS':
                return $record['target'] ?? '';
            case 'SOA':
                return implode(' ', [
                    $record['mname'] ?? '',
                    $record['rname'] ?? '',
                    $record['serial'] ?? '',
                    $record['refresh'] ?? '',
                    $record['retry'] ?? '',
                    $record['expire'] ?? '',
                    $record['minimum-ttl'] ?? '',
                ]);
            default:
                return json_encode($record);
        }
    }
    
    /**
     * Summarize records by type
     */
    private function summarizeRecords(array $records): array
    {
        $summary = [];
        
        foreach ($records as $record) {
            $type = $record['type'];
            if (!isset($summary[$type])) {
                $summary[$type] = 0;
            }
            $summary[$type]++;
        }
        
        return $summary;
    }
    
    /**
     * Generate BIND zone file format
     */
    private function generateZoneFile(string $domain, array $records): string
    {
        $zone = "; Zone file for {$domain}\n";
        $zone .= "; Generated by BelieVoo DNS Scanner\n";
        $zone .= "; Date: " . now()->toDateTimeString() . "\n\n";
        
        $zone .= "\$TTL 3600\n";
        $zone .= "@ IN SOA ns1.believoo.com. admin.believoo.com. (\n";
        $zone .= "    " . date('Ymd') . "01 ; Serial\n";
        $zone .= "    3600       ; Refresh\n";
        $zone .= "    1800       ; Retry\n";
        $zone .= "    604800     ; Expire\n";
        $zone .= "    86400 )    ; Minimum TTL\n\n";
        
        $zone .= "; Name Servers\n";
        $zone .= "@    IN NS    ns1.believoo.com.\n";
        $zone .= "@    IN NS    ns2.believoo.com.\n\n";
        
        $zone .= "; Records\n";
        
        foreach ($records as $record) {
            $name = $record['name'] === $domain ? '@' : str_replace('.' . $domain, '', $record['name']);
            $ttl = $record['ttl'] ?? 3600;
            $class = $record['class'] ?? 'IN';
            $type = $record['type'];
            $value = $record['value'];
            
            if ($type === 'MX' && isset($record['priority'])) {
                $zone .= sprintf("%-4s %5d %s %-5s %3d %s\n", $name, $ttl, $class, $type, $record['priority'], $value);
            } else {
                $zone .= sprintf("%-4s %5d %s %-5s %s\n", $name, $ttl, $class, $type, $value);
            }
        }
        
        return $zone;
    }
}
