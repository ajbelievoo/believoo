<?php

namespace App\Services;

use TaylanUnutmaz\AgoraTokenBuilder\RtcTokenBuilder;
use TaylanUnutmaz\AgoraTokenBuilder\RtmTokenBuilder;

class AgoraService
{
    public static function settings()
    {
        return \App\Models\Setting::pluck('value', 'key')->toArray();
    }

    public static function getAppId()
    {
        return self::settings()['agora_app_id'] ?? '';
    }

    public static function getAppCertificate()
    {
        return self::settings()['agora_app_certificate'] ?? '';
    }

    public static function generateRtcToken($channelName, $uid = 0, $role = RtcTokenBuilder::RolePublisher, $expireSeconds = 3600)
    {
        $appId = self::getAppId();
        $appCertificate = self::getAppCertificate();
        if (!$appId || !$appCertificate) return null;

        $expireTime = time() + $expireSeconds;
        return RtcTokenBuilder::buildTokenWithUid($appId, $appCertificate, $channelName, $uid, $role, $expireTime);
    }

    public static function generateRtmToken($userId, $expireSeconds = 3600)
    {
        $appId = self::getAppId();
        $appCertificate = self::getAppCertificate();
        if (!$appId || !$appCertificate) return null;

        $expireTime = time() + $expireSeconds;
        return RtmTokenBuilder::buildToken($appId, $appCertificate, $userId, $expireTime);
    }
}
