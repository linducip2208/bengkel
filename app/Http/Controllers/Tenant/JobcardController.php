<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\JobcardService;
use Illuminate\Http\Request;

class JobcardController extends Controller
{
    public function __construct(protected JobcardService $service) {}

    public function index(Request $request) { return $this->service->index($request); }
    public function store(Request $request) { return $this->service->store($request); }
    public function show($id) { return $this->service->show($id); }
    public function update(Request $request, $id) { return $this->service->update($request, $id); }
    public function print($id) { return $this->service->print($id); }
    public function gatePass($id) { return $this->service->gatePass($id); }
}
