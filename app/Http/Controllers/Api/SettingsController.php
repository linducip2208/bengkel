<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(protected SettingsService $service) {}

    public function index() { return $this->service->index(); }
    public function update(Request $request) { return $this->service->update($request); }
}
