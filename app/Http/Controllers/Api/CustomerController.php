<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CustomerService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(protected CustomerService $service) {}

    public function index(Request $request) { return $this->service->index($request); }
    public function store(Request $request) { return $this->service->store($request); }
    public function show($id) { return $this->service->show($id); }
    public function update(Request $request, $id) { return $this->service->update($request, $id); }
    public function destroy($id) { return $this->service->destroy($id); }
    public function import(Request $request) { return $this->service->import($request); }
}
