<?php
namespace App\Services;
use App\Models\Bconnect\Company;
use App\Models\Bconnect\Member;
use App\Models\Bconnect\Meeting;
use App\Models\Bconnect\Ticket;
class BconnectPlanService {
    public static $limits = [
        'free' => ['members' => 2, 'meetings' => 100, 'tickets' => 50, 'remote' => true, 'remote_control' => false, 'ai' => false, 'recording' => false, 'branding' => false],
        'pro' => ['members' => 10, 'meetings' => null, 'tickets' => null, 'remote' => true, 'remote_control' => true, 'ai' => false, 'recording' => false, 'branding' => true],
        'enterprise' => ['members' => null, 'meetings' => null, 'tickets' => null, 'remote' => true, 'remote_control' => true, 'ai' => true, 'recording' => true, 'branding' => true],
    ];

    public static function check($companyId, $feature) {
        $company = Company::find($companyId);
        if (!$company) return false;

        // Treat paid plans as free once they expire (until renewal).
        $planName = $company->plan;
        if ($planName !== 'free' && $company->plan_expires_at && $company->plan_expires_at->isPast()) {
            $planName = 'free';
        }

        $plan = self::$limits[$planName] ?? self::$limits['free'];
        if (is_bool($plan[$feature] ?? false)) return $plan[$feature];
        return $plan[$feature] ?? 0;
    }

    public static function canAddMember($companyId) {
        $limit = self::check($companyId, 'members');
        if ($limit === false || $limit === null) return true;
        return Member::where('company_id', $companyId)->where('is_active', true)->count() < $limit;
    }

    public static function canCreateMeeting($companyId) {
        $limit = self::check($companyId, 'meetings');
        if ($limit === null) return true;
        return Meeting::where('company_id', $companyId)->count() < $limit;
    }

    public static function canCreateTicket($companyId) {
        $limit = self::check($companyId, 'tickets');
        if ($limit === null) return true;
        return Ticket::where('company_id', $companyId)->count() < $limit;
    }

    public static function canUseRemote($companyId) { return self::check($companyId, 'remote'); }
    public static function canUseRemoteControl($companyId) { return self::check($companyId, 'remote_control'); }
    public static function canUseAi($companyId) { return self::check($companyId, 'ai'); }
    public static function canUseRecording($companyId) { return self::check($companyId, 'recording'); }
    public static function canUseBranding($companyId) { return self::check($companyId, 'branding'); }
}
