<?php

namespace App\Support;

/**
 * Resolves a clickable destination URL for database notifications.
 * Notification payloads use inconsistent keys (action_url, url, Filament
 * actions, ticket_id, agreement_id) — this normalizes them.
 */
class NotificationLink
{
    public static function url($notification, ?string $fallback = null): string
    {
        $d = is_array($notification->data) ? $notification->data : [];

        if (!empty($d['action_url'])) {
            return $d['action_url'];
        }
        if (!empty($d['url'])) {
            return $d['url'];
        }
        if (isset($d['actions']) && is_array($d['actions'])) {
            foreach ($d['actions'] as $action) {
                if (is_array($action) && !empty($action['url'])) {
                    return $action['url'];
                }
            }
        }
        if (!empty($d['ticket_id'])) {
            return route('client.dashboard', ['ticket' => $d['ticket_id']]);
        }
        if (!empty($d['agreement_id'])) {
            return route('client.agreement.view', $d['agreement_id']);
        }

        return $fallback ?? route('client.dashboard');
    }
}
