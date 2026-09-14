# Server Management Service Provider

This Laravel Service Provider integrates with **WHMCS** for billing and **Virtualizor** for VPS management to provide a unified server dashboard for your client portal.

## Features

- **Real-time Server Status**: CPU, RAM, Disk, and Bandwidth usage
- **Server Controls**: Start, Stop, Restart, and Power Off VPS instances
- **Bandwidth Monitoring**: Historical bandwidth usage statistics
- **WHMCS Integration**: Billing info, invoices, and service details
- **Auto-refresh Dashboard**: Live updates every 30 seconds
- **Caching**: Optimized API calls with configurable cache TTL
- **Error Handling**: Comprehensive logging and graceful error recovery

## Installation

### 1. Configure Environment Variables

Add these to your `.env` file:

```env
# WHMCS API Configuration
WHMCS_BASE_URL=https://billing.yourdomain.com
WHMCS_API_IDENTIFIER=your_api_identifier_here
WHMCS_API_SECRET=your_api_secret_here
WHMCS_CACHE_TTL=300

# Virtualizor API Configuration
VIRTUALIZOR_BASE_URL=https://virtualizor.yourdomain.com
VIRTUALIZOR_API_KEY=your_api_key_here
VIRTUALIZOR_API_PASS=your_api_pass_here
VIRTUALIZOR_PORT=4085
VIRTUALIZOR_CACHE_TTL=60

# Dashboard Settings
SERVER_DASHBOARD_AUTO_REFRESH=true
SERVER_DASHBOARD_REFRESH_INTERVAL=30
```

### 2. Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=server-management-config
```

### 3. Link WHMCS Client ID to Users

Add a `whmcs_client_id` field to your users table or use a separate mapping table:

```php
// In your User model or migration
Schema::table('users', function (Blueprint $table) {
    $table->integer('whmcs_client_id')->nullable()->after('id');
});
```

## Usage

### Via Facade

```php
use App\Facades\ServerManagement;

// Get full dashboard data
$data = ServerManagement::getDashboardData($whmcsClientId, $vpsIds);

// Get server details
$details = ServerManagement::getServerDetails($serviceId, $vpsId);

// Execute server action
$result = ServerManagement::executeServerAction($vpsId, 'restart');

// Get bandwidth history
$bandwidth = ServerManagement::getBandwidthHistory($vpsId, 30);

// Clear cache
ServerManagement::refreshData($clientId, $vpsIds);

// Health check
$status = ServerManagement::healthCheck();
```

### Via Dependency Injection

```php
use App\Services\ServerManagementService;

class MyController extends Controller
{
    public function index(ServerManagementService $serverManager)
    {
        $data = $serverManager->getDashboardData($clientId, $vpsIds);
        return view('dashboard', compact('data'));
    }
}
```

### Livewire Component

The `ServerDashboard` Livewire component is included and can be used in any Blade view:

```blade
<livewire:server-dashboard />
```

### Individual Services

```php
use App\Services\WhmcsApiService;
use App\Services\VirtualizorApiService;

// WHMCS Only
$whmcs = app(WhmcsApiService::class);
$products = $whmcs->getClientProducts($clientId);
$invoices = $whmcs->getClientInvoices($clientId, 'Unpaid');

// Virtualizor Only
$virtualizor = app(VirtualizorApiService::class);
$status = $virtualizor->getVpsStatus($vpsId);
$virtualizor->restartVps($vpsId);
$bandwidth = $virtualizor->getBandwidthStats($vpsId, 30);
```

## API Endpoints

The following routes are available (all require authentication):

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/client/servers` | Server Dashboard Page |
| GET | `/api/servers/data` | Get dashboard data |
| GET | `/api/servers/{serviceId}/{vpsId}/details` | Get server details |
| POST | `/api/servers/{vpsId}/action` | Execute action (start/stop/restart/poweroff) |
| GET | `/api/servers/{vpsId}/bandwidth` | Get bandwidth stats |
| POST | `/api/servers/refresh` | Clear cache |
| GET | `/api/servers/health` | API health check |

## Dashboard View

The default dashboard view is located at `resources/views/livewire/server-dashboard.blade.php`. It features:

- Summary cards for total services, running/stopped VPS, and unpaid invoices
- Individual server cards with resource usage bars
- Action buttons (Start, Stop, Restart, Power Off)
- Detailed server modal with full metrics
- Bandwidth history display
- Auto-refresh every 30 seconds

## API Credentials Setup

### WHMCS API Credentials

1. Login to WHMCS Admin Panel
2. Navigate to **Setup > Staff Management > Manage API Credentials**
3. Create a new API Role with permissions for:
   - `GetClientsProducts`
   - `GetClientsDetails`
   - `GetInvoices`
4. Create API Credentials and copy the Identifier and Secret

### Virtualizor API Credentials

1. Login to Virtualizor Admin Panel
2. Navigate to **Configuration > API Credentials**
3. Generate or copy the API Key and API Pass
4. Ensure the API port (default: 4085) is accessible from your Laravel server

## Customization

### Cache Configuration

Adjust cache TTL in `config/server-management.php`:

```php
'whmcs' => [
    'cache_ttl' => 300, // 5 minutes
],
'virtualizor' => [
    'cache_ttl' => 60, // 1 minute for real-time stats
],
```

### Custom Dashboard View

Publish and modify the views:

```bash
php artisan vendor:publish --tag=server-management-views
```

## Troubleshooting

### API Connection Issues

Check the health endpoint:
```bash
curl https://yourdomain.com/api/servers/health
```

### Check Logs

API errors are logged to your Laravel log:
```bash
tail -f storage/logs/laravel.log | grep -E "(WHMCS|Virtualizor)"
```

### Clear Cache

If data appears stale:
```bash
php artisan cache:clear
```

Or use the refresh endpoint:
```bash
curl -X POST https://yourdomain.com/api/servers/refresh \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Security Considerations

1. **API Credentials**: Store in `.env`, never commit to version control
2. **HTTPS Only**: Ensure both WHMCS and Virtualizor use HTTPS
3. **Firewall**: Restrict Virtualizor API port to your Laravel server IP
4. **Rate Limiting**: Consider adding rate limiting to API endpoints
5. **User Authorization**: Ensure users can only access their own servers

## Files Created

- `app/Services/WhmcsApiService.php` - WHMCS API client
- `app/Services/VirtualizorApiService.php` - Virtualizor API client
- `app/Services/ServerManagementService.php` - Combined service
- `app/Providers/ServerManagementServiceProvider.php` - Service provider
- `app/Facades/ServerManagement.php` - Facade
- `app/Livewire/ServerDashboard.php` - Livewire component
- `app/Http/Controllers/ServerDashboardController.php` - API controller
- `config/server-management.php` - Configuration
- `resources/views/livewire/server-dashboard.blade.php` - Dashboard view

## License

This is custom code for your Laravel application. Modify as needed for your use case.
