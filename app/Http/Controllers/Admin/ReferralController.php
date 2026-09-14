<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\ReferralCode;
use App\Models\LoyaltyPoint;
use Illuminate\Http\Request;
class ReferralController extends Controller {
    public function index() {
        return view('admin.referrals.index', [
            'referrals' => ReferralCode::with('user')->latest()->paginate(25),
            'total_points' => LoyaltyPoint::where('type', 'earned')->sum('points') - LoyaltyPoint::where('type', 'redeemed')->sum('points')
        ]);
    }
    public function updatePoints(Request $r, $userId) {
        LoyaltyPoint::create(['user_id' => $userId, 'points' => $r->points, 'type' => $r->type, 'reference' => $r->reference]);
        return back()->with('success', 'Points updated');
    }
}
