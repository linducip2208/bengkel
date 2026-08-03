<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\VehicleService;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function __construct(protected VehicleService $service) {}

    public function index(Request $request) { return $this->service->index($request); }
    public function store(Request $request) { return $this->service->store($request); }
    public function show($id) { return $this->service->show($id); }
    public function update(Request $request, $id) { return $this->service->update($request, $id); }
    public function destroy($id) { return $this->service->destroy($id); }
    public function uploadImage(Request $request, $id) { return $this->service->uploadImage($request, $id); }
}
