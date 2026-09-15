<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SsoProvider extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'bool',
        'auto_provision' => 'bool',
        'active_from' => 'datetime',
        'active_until' => 'datetime',
    ];

    public function scopeActive($query)
    {
        $now = now();
        return $query
            ->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('active_from')->orWhere('active_from', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('active_until')->orWhere('active_until', '>=', $now);
            });
    }

    public function samlSettings(): array
    {
        $config = $this->config ?? [];
        $sp = $config['sp'] ?? [];
        $idp = $config['idp'] ?? [];

        return [
            'strict' => true,
            'debug' => false,
            'sp' => [
                'entityId' => $sp['entity_id'] ?? url('/saml/' . $this->slug . '/metadata'),
                'assertionConsumerService' => [
                    'url' => $sp['acs_url'] ?? url('/saml/' . $this->slug . '/acs'),
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                ],
                'singleLogoutService' => [
                    'url' => $sp['slo_url'] ?? url('/saml/' . $this->slug . '/slo'),
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                ],
                'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
                'x509cert' => $sp['x509cert'] ?? '',
                'privateKey' => $sp['private_key'] ?? '',
            ],
            'idp' => [
                'entityId' => $idp['entity_id'] ?? $this->entity_id,
                'singleSignOnService' => [
                    'url' => $idp['sso_url'] ?? '',
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                ],
                'singleLogoutService' => [
                    'url' => $idp['slo_url'] ?? '',
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                ],
                'x509cert' => $idp['x509cert'] ?? '',
            ],
        ];
    }
}
