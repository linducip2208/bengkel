<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\ServiceService;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function __construct(protected ServiceService $service) {}

    public function index(Request $request) { return $this->service->index($request); }
    public function create() { return $this->service->create(); }
    public function store(Request $request) { return $this->service->store($request); }
    public function show($id) { return $this->service->show($id); }
    public function edit($id) { return $this->service->edit($id); }
    public function update(Request $request, $id) { return $this->service->update($request, $id); }
    public function destroy($id) { return $this->service->destroy($id); }
    public function complete(Request $request, $id) { return $this->service->complete($request, $id); }
    public function start($id) { return $this->service->startService($id); }
    public function uploadImage(Request $request, $id) { return $this->service->uploadImage($request, $id); }
    public function searchCustomers(Request $request) { return $this->service->searchCustomers($request); }
    public function vehiclesByCustomer($customer) { return $this->service->vehiclesByCustomer($customer); }
}
