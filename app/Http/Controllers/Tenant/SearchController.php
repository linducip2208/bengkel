<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Service;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $limit = 5;

        $customers = $vehicles = $services = $invoices = $products = [];

        if ($q !== '') {
            $like = '%' . $q . '%';

            $customers = Customer::query()
                ->where(fn ($query) => $query->where('name', 'like', $like)
                    ->orWhere('phone', 'like', $like))
                ->limit($limit)
                ->get()
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'title' => $c->name,
                    'subtitle' => $c->phone ?: '—',
                    'url' => route('customers.show', $c),
                ])
                ->values();

            $vehicles = Vehicle::query()
                ->with('vehicleBrand:id,name')
                ->where(fn ($query) => $query->where('number_plate', 'like', $like)
                    ->orWhere('model_name', 'like', $like))
                ->limit($limit)
                ->get()
                ->map(fn ($v) => [
                    'id' => $v->id,
                    'title' => $v->number_plate,
                    'subtitle' => trim(($v->vehicleBrand?->name ? $v->vehicleBrand->name . ' ' : '') . ($v->model_name ?: '')),
                    'url' => route('vehicles.show', $v),
                ])
                ->values();

            $services = Service::query()
                ->with('customer:id,name')
                ->where(fn ($query) => $query->where('job_no', 'like', $like)
                    ->orWhere('title', 'like', $like))
                ->limit($limit)
                ->get()
                ->map(fn ($s) => [
                    'id' => $s->id,
                    'title' => trim(($s->job_no ? $s->job_no . ' — ' : '') . ($s->title ?: '')),
                    'subtitle' => $s->customer?->name ?: '—',
                    'url' => route('services.show', $s),
                ])
                ->values();

            $invoices = Invoice::query()
                ->with('customer:id,name')
                ->where('invoice_number', 'like', $like)
                ->limit($limit)
                ->get()
                ->map(fn ($i) => [
                    'id' => $i->id,
                    'title' => $i->invoice_number,
                    'subtitle' => $i->customer?->name ?: '—',
                    'url' => route('invoices.show', $i),
                ])
                ->values();

            $products = Product::query()
                ->where(fn ($query) => $query->where('name', 'like', $like)
                    ->orWhere('code', 'like', $like))
                ->limit($limit)
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'title' => $p->name,
                    'subtitle' => $p->code ?: '—',
                    'url' => route('products.show', $p),
                ])
                ->values();
        }

        return response()->json([
            'customers' => $customers,
            'vehicles' => $vehicles,
            'services' => $services,
            'invoices' => $invoices,
            'products' => $products,
        ]);
    }
}
