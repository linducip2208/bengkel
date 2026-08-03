<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\JobcardService;
use Illuminate\Http\Request;

class JobcardController extends Controller
{
    public function __construct(protected JobcardService $service) {}

    public function index(Request $request) { return $this->service->index($request); }
    public function store(Request $request) { return $this->service->store($request); }
}
