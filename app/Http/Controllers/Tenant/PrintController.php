<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Service;
use App\Services\ThermalPrintService;
use Illuminate\Http\Request;

class PrintController extends Controller
{
    /**
     * Print invoice via thermal printer
     */
    public function invoice(Invoice $invoice, Request $request, ThermalPrintService $printService)
    {
        $printerIp = $request->get('printer_ip', config('services.printer.ip', '192.168.1.100'));
        $printerPort = $request->get('printer_port', config('services.printer.port', 9100));

        try {
            $items = $invoice->items->map(fn($i) => [
                'description' => $i->description,
                'quantity' => $i->quantity,
                'unit_price' => $i->unit_price,
            ])->toArray();

            $printService->printToNetwork($printerIp, (int) $printerPort)
                ->printReceipt([
                    'header' => config('app.name'),
                    'invoice_number' => $invoice->invoice_number,
                    'date' => $invoice->invoice_date->format('d/m/Y H:i'),
                    'cashier' => auth()->user()?->name,
                    'customer' => $invoice->customer?->name,
                    'vehicle' => $invoice->service?->vehicle?->number_plate,
                    'items' => $items,
                    'grand_total' => $invoice->grand_total,
                    'payment_method' => $invoice->paymentMethod?->payment,
                    'footer' => 'Layanan Bengkel Profesional',
                ]);

            return back()->with('success', 'Print berhasil dikirim ke printer.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Print gagal: ' . $e->getMessage());
        }
    }

    /**
     * Print jobcard via thermal printer
     */
    public function jobcard(Service $service, Request $request, ThermalPrintService $printService)
    {
        $printerIp = $request->get('printer_ip', config('services.printer.ip', '192.168.1.100'));
        $printerPort = $request->get('printer_port', config('services.printer.port', 9100));

        try {
            $printService->printToNetwork($printerIp, (int) $printerPort)
                ->printJobcard([
                    'job_no' => $service->job_no,
                    'date' => $service->service_date->format('d/m/Y H:i'),
                    'customer' => $service->customer?->name,
                    'vehicle' => $service->vehicle?->model_name,
                    'number_plate' => $service->vehicle?->number_plate,
                    'odometer' => $service->jobcardDetail?->odometer_in,
                    'complaint' => $service->description,
                    'category' => $service->repairCategory?->repair_category_name,
                    'technician' => $service->technicians->pluck('name')->implode(', '),
                ]);

            return back()->with('success', 'Jobcard berhasil di-print.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Print gagal: ' . $e->getMessage());
        }
    }

    /**
     * Open cash drawer
     */
    public function openDrawer(Request $request, ThermalPrintService $printService)
    {
        $printerIp = $request->get('printer_ip', config('services.printer.ip', '192.168.1.100'));
        $printerPort = $request->get('printer_port', config('services.printer.port', 9100));

        try {
            $printService->printToNetwork($printerIp, (int) $printerPort)->openCashDrawer();
            return response()->json(['ok' => true, 'message' => 'Laci kasir terbuka.']);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate ESC/POS raw data for browser-based printing (Web Bluetooth / Web USB)
     */
    public function rawData(Invoice $invoice, ThermalPrintService $printService)
    {
        $items = $invoice->items->map(fn($i) => [
            'description' => $i->description,
            'quantity' => $i->quantity,
            'unit_price' => $i->unit_price,
        ])->toArray();

        $data = [
            'header' => config('app.name'),
            'invoice_number' => $invoice->invoice_number,
            'date' => $invoice->invoice_date->format('d/m/Y H:i'),
            'cashier' => auth()->user()?->name,
            'customer' => $invoice->customer?->name,
            'items' => $items,
            'grand_total' => $invoice->grand_total,
            'payment_method' => $invoice->paymentMethod?->payment,
        ];

        // Generate ESC/POS binary as base64 for JavaScript to send via Web Bluetooth
        $raw = $printService->generateReceiptData($data);
        return response()->json([
            'ok' => true,
            'data' => base64_encode($raw),
        ]);
    }
}
