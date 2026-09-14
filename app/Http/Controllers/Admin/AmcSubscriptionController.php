<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AmcSubscription;
use Illuminate\Http\Request;

class AmcSubscriptionController extends Controller
{
    public function index()
    {
        $subscriptions = AmcSubscription::with(['client', 'agreement'])->latest()->paginate(20);
        return view('admin.amc-subscriptions.index', compact('subscriptions'));
    }

    public function show(AmcSubscription $amcSubscription)
    {
        $amcSubscription->load(['client', 'agreement']);
        return view('admin.amc-subscriptions.show', compact('amcSubscription'));
    }
}
