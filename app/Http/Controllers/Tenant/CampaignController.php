<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\NotificationQueue;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index()
    {
        $groups = Customer::whereNotNull('phone')->get()->groupBy('customer_group_id');
        return view('marketing.campaign', compact('groups'));
    }

    public function send(Request $request)
    {
        $v = $request->validate([
            'message' => 'required|string|max:1600',
            'customer_ids' => 'required|array|min:1',
            'channel' => 'required|in:whatsapp,sms',
        ]);

        $customers = Customer::whereIn('id', $v['customer_ids'])->get();
        $count = 0;
        foreach ($customers as $c) {
            if (!$c->phone) continue;
            NotificationQueue::create([
                'channel' => $v['channel'],
                'recipient' => $c->phone,
                'message' => $v['message'],
                'status' => 'pending',
            ]);
            $count++;
        }
        return back()->with('success', "{$count} notifikasi diantrikan untuk dikirim.");
    }

    /** AJAX: search customers for campaign */ 
    public function searchCustomers(Request $request)
    {
        $customers = Customer::where('name', 'like', "%{$request->q}%")->orWhere('phone', 'like', "%{$request->q}%")->limit(20)->get(['id','name','phone']);
        return response()->json($customers);
    }
}
