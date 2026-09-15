<?php

namespace App\Guards;

use App\Models\ApiKey;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;

class ApiKeyGuard implements Guard
{
    protected ?Authenticatable $user = null;
    protected ?ApiKey $apiKey = null;
    protected bool $retrieved = false;

    public function __construct(
        protected Request $request,
        protected ?UserProvider $provider = null
    ) {
    }

    public function user(): ?Authenticatable
    {
        if ($this->retrieved) {
            return $this->user;
        }

        $this->retrieved = true;

        $token = $this->getTokenFromRequest();

        if (!$token) {
            return null;
        }

        $key = ApiKey::findByToken($token);

        if (!$key) {
            return null;
        }

        if (!$key->isValid()) {
            return null;
        }

        if (!$key->isIpAllowed($this->request->ip())) {
            return null;
        }

        $user = $this->provider?->retrieveById($key->user_id) ?? $key->user;

        if ($user) {
            $this->user = $user;
            $this->apiKey = $key;
            $this->request->attributes->set('api_key', $key);
            $key->recordUsage();
        }

        return $this->user;
    }

    public function id(): ?int
    {
        return $this->user()?->getAuthIdentifier();
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function guest(): bool
    {
        return !$this->check();
    }

    public function validate(array $credentials = []): bool
    {
        $token = $credentials['api_token'] ?? null;

        if (!$token) {
            return false;
        }

        $key = ApiKey::findByToken($token);

        return $key && $key->isValid() && $key->isIpAllowed($this->request->ip());
    }

    public function hasUser(): bool
    {
        return $this->user !== null;
    }

    public function setUser(Authenticatable $user): static
    {
        $this->user = $user;
        $this->retrieved = true;

        return $this;
    }

    public function currentApiKey(): ?ApiKey
    {
        $this->user();

        return $this->apiKey;
    }

    protected function getTokenFromRequest(): ?string
    {
        if ($this->request->hasHeader('Authorization')) {
            $header = $this->request->header('Authorization');
            if (str_starts_with($header, 'Bearer ')) {
                return substr($header, 7);
            }
        }

        if ($this->request->hasHeader('X-API-Key')) {
            return $this->request->header('X-API-Key');
        }

        if ($this->request->has('api_key')) {
            return $this->request->input('api_key');
        }

        return null;
    }
}
