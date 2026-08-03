<?php

namespace App\Services;

class DashboardService extends BaseService
{
    public function index()
    {
        return view('tenant.dashboard');
    }
}
