<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request as RequestFacade;

class AuditService
{
    /**
     * Keys whose values should be redacted before storing.
     */
    protected static array $sensitiveKeys = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'token',
        'access_token',
        'refresh_token',
        'secret',
        'api_key',
        'api_secret',
        'client_secret',
        'credit_card',
        'cvv',
        'otp',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'recovery_codes',
    ];

    /**
     * Redact sensitive values from an array.
     */
    protected static function redact(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        $redacted = [];
        foreach ($values as $key => $value) {
            $lowerKey = strtolower((string) $key);
            if (in_array($lowerKey, self::$sensitiveKeys, true)) {
                $redacted[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $redacted[$key] = self::redact($value);
            } else {
                $redacted[$key] = $value;
            }
        }

        return $redacted;
    }

    /**
     * Log an action.
     *
     * @param Request|null $request The request to extract route/URL/IP/UA from. Defaults to the current request facade.
     */
    public static function log(
        string $action,
        ?Model $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null,
        ?int $userId = null,
        ?Request $request = null
    ): AuditLog {
        $user = Auth::user();
        $request ??= RequestFacade::instance();

        return AuditLog::create([
            'user_id' => $userId ?? ($user ? $user->id : null),
            'action' => $action,
            'auditable_type' => $auditable ? get_class($auditable) : null,
            'auditable_id' => $auditable?->getKey(),
            'route' => $request->route()?->getName(),
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'old_values' => self::redact($oldValues),
            'new_values' => self::redact($newValues),
            'description' => $description,
        ]);
    }

    /**
     * Log a login or logout event.
     */
    public static function auth(string $action, ?int $userId = null, ?string $description = null): AuditLog
    {
        return static::log($action, null, null, null, $description, $userId);
    }

    /**
     * Log a model creation.
     */
    public static function created(Model $model, ?string $description = null): AuditLog
    {
        return static::log('created', $model, null, $model->toArray(), $description);
    }

    /**
     * Log a model update.
     */
    public static function updated(Model $model, array $oldValues, ?string $description = null): AuditLog
    {
        return static::log('updated', $model, $oldValues, $model->toArray(), $description);
    }

    /**
     * Log a model deletion.
     */
    public static function deleted(Model $model, ?string $description = null): AuditLog
    {
        return static::log('deleted', $model, $model->toArray(), null, $description);
    }
}
