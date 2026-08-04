<?php

namespace App\Services;

use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\EscposImage;
use Illuminate\Support\Facades\Log;

class ThermalPrintService
{
    private ?Printer $printer = null;
    private string $connectorType = 'network';

    /**
     * Print to network thermal printer (LAN/WiFi)
     */
    public function printToNetwork(string $ip, int $port = 9100): self
    {
        try {
            $connector = new NetworkPrintConnector($ip, $port);
            $this->printer = new Printer($connector);
            $this->connectorType = 'network';
        } catch (\Throwable $e) {
            Log::error("Printer connection failed: {$e->getMessage()}");
            throw new \RuntimeException("Tidak bisa terhubung ke printer {$ip}:{$port}");
        }
        return $this;
    }

    /**
     * Print via USB (shared printer name on Windows)
     */
    public function printToUsb(string $shareName): self
    {
        try {
            $connector = new FilePrintConnector($shareName);
            $this->printer = new Printer($connector);
            $this->connectorType = 'usb';
        } catch (\Throwable $e) {
            Log::error("USB printer connection failed: {$e->getMessage()}");
            throw new \RuntimeException("Tidak bisa terhubung ke printer USB: {$shareName}");
        }
        return $this;
    }

    /**
     * Generate ESC/POS receipt as raw binary (for browser-based printing via Web Bluetooth/USB)
     */
    public function generateReceiptData(array $data): string
    {
        // Use output buffer capture
        ob_start();
        $this->printReceipt($data);
        $raw = ob_get_clean();
        return $raw;
    }

    /**
     * Print invoice-style receipt
     */
    public function printReceipt(array $data): self
    {
        if (!$this->printer) {
            throw new \RuntimeException('Printer not initialized. Call printToNetwork() or printToUsb() first.');
        }

        $p = $this->printer;

        // Header - centered, bold, double height
        $p->setJustification(Printer::JUSTIFY_CENTER);
        $p->setTextSize(2, 2);
        $p->text($data['header'] ?? config('app.name') . "\n");
        $p->setTextSize(1, 1);
        $p->text($data['address'] ?? '' . "\n");
        $p->text($data['phone'] ?? '' . "\n");
        $p->text(str_repeat('-', 32) . "\n");

        // Invoice info
        $p->setJustification(Printer::JUSTIFY_LEFT);
        $p->text("No    : " . ($data['invoice_number'] ?? '-') . "\n");
        $p->text("Tgl   : " . ($data['date'] ?? date('d/m/Y H:i')) . "\n");
        $p->text("Kasir : " . ($data['cashier'] ?? auth()->user()?->name ?? '-') . "\n");

        if (!empty($data['customer'])) {
            $p->text("Cust  : " . $data['customer'] . "\n");
        }
        if (!empty($data['vehicle'])) {
            $p->text("Kend  : " . $data['vehicle'] . "\n");
        }

        $p->text(str_repeat('-', 32) . "\n");

        // Items
        if (!empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $name = mb_substr($item['description'] ?? '', 0, 28);
                $p->text($name . "\n");
                $qty = str_pad($item['quantity'] ?? 1, 3, ' ', STR_PAD_LEFT);
                $price = str_pad(number_format($item['unit_price'] ?? 0, 0, ',', '.'), 10, ' ', STR_PAD_LEFT);
                $total = str_pad(number_format(($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0), 0, ',', '.'), 12, ' ', STR_PAD_LEFT);
                $p->text("{$qty} x{$price} ={$total}\n");
            }
        }

        $p->text(str_repeat('-', 32) . "\n");

        // Totals
        $p->setJustification(Printer::JUSTIFY_RIGHT);
        $p->text("TOTAL : Rp " . number_format($data['grand_total'] ?? 0, 0, ',', '.') . "\n");

        if (!empty($data['payment_method'])) {
            $p->setJustification(Printer::JUSTIFY_LEFT);
            $p->text("Bayar : " . $data['payment_method'] . "\n");
        }

        $p->text(str_repeat('-', 32) . "\n");

        // Footer
        $p->setJustification(Printer::JUSTIFY_CENTER);
        $p->text($data['footer'] ?? "Terima kasih!\n");
        $p->text("Barang yg sudah dibeli\ntidak dapat ditukar kembali\n");
        $p->text("\n");

        // Barcode (optional)
        if (!empty($data['barcode'])) {
            $p->setBarcodeHeight(50);
            $p->setBarcodeWidth(2);
            $p->barcode($data['barcode'], Printer::BARCODE_CODE128);
            $p->text("\n");
        }

        // QR Code (optional)
        if (!empty($data['qr'])) {
            $p->setJustification(Printer::JUSTIFY_CENTER);
            $p->qrCode($data['qr'], Printer::QR_ECLEVEL_L, 4);
            $p->text("\n");
        }

        // Cut paper
        $p->cut();
        $p->close();

        return $this;
    }

    /**
     * Print jobcard for mechanic
     */
    public function printJobcard(array $data): self
    {
        if (!$this->printer) throw new \RuntimeException('Printer not initialized.');

        $p = $this->printer;
        $p->setJustification(Printer::JUSTIFY_CENTER);
        $p->setTextSize(2, 2);
        $p->text("JOBCARD\n");
        $p->setTextSize(1, 1);
        $p->text(str_repeat('=', 32) . "\n");
        $p->setJustification(Printer::JUSTIFY_LEFT);
        $p->text("Job No : " . ($data['job_no'] ?? '-') . "\n");
        $p->text("Tgl    : " . ($data['date'] ?? '-') . "\n");
        $p->text("Cust   : " . ($data['customer'] ?? '-') . "\n");
        $p->text("Kend   : " . ($data['vehicle'] ?? '-') . "\n");
        $p->text("No Pol : " . ($data['number_plate'] ?? '-') . "\n");
        $p->text("KM     : " . ($data['odometer'] ?? '-') . "\n");
        $p->text(str_repeat('-', 32) . "\n");
        $p->text("Keluhan:\n");
        $p->text(wordwrap($data['complaint'] ?? '-', 32, "\n") . "\n");
        $p->text(str_repeat('-', 32) . "\n");
        $p->text("Kategori: " . ($data['category'] ?? '-') . "\n");
        $p->text("Teknisi : " . ($data['technician'] ?? '-') . "\n");
        $p->text(str_repeat('=', 32) . "\n");
        $p->setJustification(Printer::JUSTIFY_CENTER);
        $p->text("Tanda Tangan Customer:\n\n\n\n");
        $p->text("------------------------\n");
        $p->cut();
        $p->close();

        return $this;
    }

    /**
     * Open cash drawer (if supported)
     */
    public function openCashDrawer(): self
    {
        if (!$this->printer) throw new \RuntimeException('Printer not initialized.');
        $this->printer->pulse(0);
        $this->printer->close();
        return $this;
    }
}
