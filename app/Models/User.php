<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin; // Only admins can access the panel
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'is_admin',
        'phone',
        'password',
        'profile_photo_path',
        'google_id',
        'facebook_id',
        'twitter_id',
        'social_type',
        'avatar',
        'whmcs_client_id',
        'vps_ids',
        'next_due_date',
        'plan_price',
        'billing_status',
        'last_payment_date',
        'current_plan_name',
        'wallet_balance',
        'referral_code',
        'referral_discount_balance',
        'total_referrals',
        'successful_referrals',
        'referred_by',
        'locale',
    ];

    public function getAvatarUrlAttribute()
    {
        return $this->profile_photo_path 
            ? asset('storage/' . $this->profile_photo_path) 
            : 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7F9CF5&background=EBF4FF';
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'vps_ids' => 'array',
            'next_due_date' => 'date',
            'last_payment_date' => 'date',
            'plan_price' => 'decimal:2',
            'wallet_balance' => 'decimal:2',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function twoFactorEnabled(): bool
    {
        return !is_null($this->two_factor_secret) && !is_null($this->two_factor_confirmed_at);
    }

    public function twoFactorRecoveryCodes(): array
    {
        return json_decode($this->two_factor_recovery_codes ?? '[]', true) ?: [];
    }

    public function twoFactorQrCodeUrl(): string
    {
        return \PragmaRX\Google2FALaravel\Facade::getQRCodeUrl(
            config('app.name', 'Believoo'),
            $this->email,
            $this->two_factor_secret
        );
    }

    public function twoFactorQrCodeSvg(int $size = 200): string
    {
        return \PragmaRX\Google2FALaravel\Facade::getQRCodeInline(
            config('app.name', 'Believoo'),
            $this->email,
            $this->two_factor_secret,
            $size
        );
    }

    public function generateTwoFactorSecret(): void
    {
        $this->two_factor_secret = \PragmaRX\Google2FALaravel\Facade::generateSecretKey();
    }

    public function verifyTwoFactorCode(string $code): bool
    {
        if (!$this->two_factor_secret) {
            return false;
        }

        return \PragmaRX\Google2FALaravel\Facade::verifyKey(
            $this->two_factor_secret,
            $code,
            config('google2fa.window', 1)
        );
    }

    public function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = bin2hex(random_bytes(4));
        }

        $this->two_factor_recovery_codes = json_encode($codes);
        return $codes;
    }

    public function verifyTwoFactorRecoveryCode(string $code): bool
    {
        $codes = $this->twoFactorRecoveryCodes();
        if (in_array($code, $codes, true)) {
            $this->two_factor_recovery_codes = json_encode(array_values(array_diff($codes, [$code])));
            return true;
        }

        return false;
    }

    public function disableTwoFactor(): void
    {
        $this->two_factor_secret = null;
        $this->two_factor_recovery_codes = null;
        $this->two_factor_confirmed_at = null;
    }

    /**
     * Get VPS IDs as array (handles both JSON string and array)
     */
    public function getVpsIdsArray(): array
    {
        if (is_array($this->vps_ids)) {
            return $this->vps_ids;
        }
        if (is_string($this->vps_ids)) {
            return json_decode($this->vps_ids, true) ?? [];
        }
        return [];
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function agreements(): HasMany
    {
        return $this->hasMany(Agreement::class, 'client_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'email', 'email');
    }

    public function hostings(): HasMany
    {
        return $this->hasMany(UserHosting::class);
    }

    public function ownedTeams(): HasMany
    {
        return $this->hasMany(UserTeam::class, 'owner_id');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(UserTeam::class, 'team_users')
            ->withPivot('role', 'permissions')
            ->withTimestamps();
    }

    public function referralsMade(): HasMany
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    public function referralReceived(): HasOne
    {
        return $this->hasOne(Referral::class, 'referred_id');
    }

    public function getReferralLinkAttribute(): string
    {
        return route('register') . '?ref=' . ($this->referral_code ?? $this->generateReferralCode());
    }

    public function generateReferralCode(): string
    {
        if (!$this->referral_code) {
            $this->referral_code = 'BEL' . strtoupper(substr(md5($this->email . time()), 0, 6));
            $this->save();
        }
        return $this->referral_code;
    }

    public function teamMemberships(): HasMany
    {
        return $this->hasMany(TeamUser::class);
    }

    public function hasTeamRole(UserTeam $team, string $role): bool
    {
        $teamUser = $this->teamMemberships()
            ->where('user_team_id', $team->id)
            ->first();
            
        return $teamUser?->role === $role;
    }

    public function hasTeamPermission(UserTeam $team, string $permission): bool
    {
        $teamUser = $this->teamMemberships()
            ->where('user_team_id', $team->id)
            ->first();
            
        return $teamUser?->hasPermission($permission) ?? false;
    }

    public function isTeamOwner(UserTeam $team): bool
    {
        return $this->id === $team->owner_id;
    }

    public function isAdmin(): bool
    {
        return $this->is_admin;
    }
}
