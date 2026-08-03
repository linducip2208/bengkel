<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;

class ReportController extends Controller
{
    public function __construct(protected ReportService $service) {}

    public function index() { return $this->service->index(); }
    public function serviceReport() { return $this->service->serviceReport(); }
    public function salesReport() { return $this->service->salesReport(); }
    public function stockReport() { return $this->service->stockReport(); }
    public function financialReport() { return $this->service->financialReport(); }
}
