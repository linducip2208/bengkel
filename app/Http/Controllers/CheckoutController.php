<?php

namespace App\Http\Controllers;

use App\Models\CheckoutCategory;
use App\Models\Service;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function index(Service $service)
    {
        $service->load(['checkoutResults.checkoutCategory', 'customer', 'vehicle']);
        $categories = CheckoutCategory::orderBy('category_name')->get();

        return view('checkouts.form', compact('service', 'categories'));
    }

    public function store(Request $request, Service $service)
    {
        $request->validate([
            'results' => 'required|array',
            'results.*.checkout_category_id' => 'required|exists:checkout_categories,id',
            'results.*.result' => 'required|string',
            'results.*.comment' => 'nullable|string',
        ]);

        $service->checkoutResults()->delete();

        foreach ($request->input('results') as $item) {
            if (! empty($item['checkout_category_id']) && ! empty($item['result'])) {
                $service->checkoutResults()->create([
                    'checkout_category_id' => $item['checkout_category_id'],
                    'result' => $item['result'],
                    'comment' => $item['comment'] ?? null,
                ]);
            }
        }

        return redirect()
            ->route('services.show', $service)
            ->with('success', 'Hasil checkout berhasil disimpan.');
    }
}
