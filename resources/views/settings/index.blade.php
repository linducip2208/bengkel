@extends('layouts.app')

@section('title')
Settings - {{ config('app.name') }}
@endsection

@section('content')
@php
    $invoiceSections = app(\App\Services\SettingsService::class)->getInvoiceSections();
    $invoiceSectionDefs = [
        'company' => 'Info Perusahaan (logo, nama, alamat)',
        'customer' => 'Info Pelanggan & Kendaraan',
        'items' => 'Tabel Item / Rincian',
        'totals' => 'Subtotal, Diskon, Pajak & Total',
        'payments' => 'Riwayat Pembayaran',
        'notes' => 'Catatan Invoice',
        'footer' => 'Footer & Ucapan Terima Kasih',
    ];
    $invoiceSectionOrder = array_values(array_unique(array_merge($invoiceSections, array_keys($invoiceSectionDefs))));
@endphp
<h4 class="mb-3">Settings</h4>

<form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <ul class="nav nav-tabs mb-3" id="settingsTabs" role="tablist">
        <li class="nav-item"><button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#general">General</button></li>
        <li class="nav-item"><button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#email">Email</button></li>
        <li class="nav-item"><button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#whatsapp">WhatsApp</button></li>
        <li class="nav-item"><button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#invoice">Invoice</button></li>
        <li class="nav-item"><button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#notification">Notification</button></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="general">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">System Name</label>
                            <input type="text" name="settings[company_name]" class="form-control" value="{{ $settings['company_name'] ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tax ID (NPWP)</label>
                            <input type="text" name="settings[company_tax_id]" class="form-control" value="{{ $settings['company_tax_id'] ?? '' }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="settings[company_address]" class="form-control" rows="2">{{ $settings['company_address'] ?? '' }}</textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="settings[company_phone]" class="form-control" value="{{ $settings['company_phone'] ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="settings[company_email]" class="form-control" value="{{ $settings['company_email'] ?? '' }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Logo</label>
                        <input type="file" name="logo" class="form-control" accept="image/*">
                        @if(!empty($settings['company_logo']))
                            <div class="mt-2">
                                <img src="{{ asset('storage/' . $settings['company_logo']) }}" style="max-height:100px;border:1px solid #ddd;border-radius:8px;padding:6px;">
                                <small class="text-muted ms-2">{{ $settings['company_logo'] }}</small>
                            </div>
                        @endif
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Timezone</label>
                            <select name="settings[timezone]" class="form-select">
                                @foreach(['Asia/Jakarta', 'Asia/Makassar', 'Asia/Jayapura', 'UTC'] as $tz)
                                    <option value="{{ $tz }}" {{ ($settings['timezone'] ?? 'Asia/Jakarta') === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Date Format</label>
                            <select name="settings[date_format]" class="form-select">
                                @foreach(['d/m/Y', 'm/d/Y', 'Y-m-d', 'd-m-Y'] as $fmt)
                                    <option value="{{ $fmt }}" {{ ($settings['date_format'] ?? 'd/m/Y') === $fmt ? 'selected' : '' }}>{{ $fmt }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Currency</label>
                            <select name="settings[currency]" class="form-select">
                                <option value="IDR" {{ ($settings['currency'] ?? 'IDR') === 'IDR' ? 'selected' : '' }}>IDR (Rp)</option>
                                <option value="USD" {{ ($settings['currency'] ?? 'IDR') === 'USD' ? 'selected' : '' }}>USD ($)</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Language</label>
                        <select name="settings[language]" class="form-select" style="max-width:300px">
                            <option value="id" {{ ($settings['language'] ?? 'id') === 'id' ? 'selected' : '' }}>Bahasa Indonesia</option>
                            <option value="en" {{ ($settings['language'] ?? 'id') === 'en' ? 'selected' : '' }}>English</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="email">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">SMTP Host</label>
                            <input type="text" name="settings[smtp_host]" class="form-control" value="{{ $emailSettings['smtp_host'] ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">SMTP Port</label>
                            <input type="text" name="settings[smtp_port]" class="form-control" value="{{ $emailSettings['smtp_port'] ?? '587' }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Encryption</label>
                            <select name="settings[smtp_encryption]" class="form-select">
                                @foreach(['tls', 'ssl', ''] as $enc)
                                    <option value="{{ $enc }}" {{ ($emailSettings['smtp_encryption'] ?? 'tls') === $enc ? 'selected' : '' }}>{{ $enc ?: 'None' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Username</label>
                            <input type="text" name="settings[smtp_username]" class="form-control" value="{{ $emailSettings['smtp_username'] ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <input type="password" name="settings[smtp_password]" class="form-control" placeholder="{{ !empty($emailSettings['smtp_password']) ? '(tersimpan)' : '' }}" autocomplete="new-password">
                            @if(!empty($emailSettings['smtp_password']))<small class="text-muted">Password tersimpan. Kosongkan jika tidak ingin diubah.</small>@endif
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">From Address</label>
                            <input type="email" name="settings[mail_from_address]" class="form-control" value="{{ $emailSettings['mail_from_address'] ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">From Name</label>
                            <input type="text" name="settings[mail_from_name]" class="form-control" value="{{ $emailSettings['mail_from_name'] ?? '' }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="whatsapp">
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Gateway Provider</label>
                        <select name="settings[whatsapp_provider]" class="form-select" style="max-width:300px">
                            @foreach(['none' => 'None', 'fonnte' => 'Fonnte', 'wablas' => 'Wablas', 'wa_business' => 'WA Business API'] as $val => $label)
                                <option value="{{ $val }}" {{ ($whatsappSettings['whatsapp_provider'] ?? 'none') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">API URL</label>
                        <input type="text" name="settings[whatsapp_api_url]" class="form-control" value="{{ $whatsappSettings['whatsapp_api_url'] ?? '' }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">API Key</label>
                        <input type="text" name="settings[whatsapp_api_key]" class="form-control" value="{{ $whatsappSettings['whatsapp_api_key'] ?? '' }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Device/Sender ID</label>
                        <input type="text" name="settings[whatsapp_sender_id]" class="form-control" value="{{ $whatsappSettings['whatsapp_sender_id'] ?? '' }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="invoice">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Invoice Prefix</label>
                            <input type="text" name="settings[invoice_prefix]" class="form-control" value="{{ $invoiceSettings['invoice_prefix'] ?? 'INV' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Starting Number</label>
                            <input type="number" name="settings[invoice_starting_number]" class="form-control" value="{{ $invoiceSettings['invoice_starting_number'] ?? '1' }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Footer Text</label>
                        <textarea name="settings[invoice_footer]" class="form-control" rows="2">{{ $invoiceSettings['invoice_footer'] ?? '' }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Terms & Conditions</label>
                        <textarea name="settings[invoice_terms]" class="form-control" rows="4">{{ $invoiceSettings['invoice_terms'] ?? '' }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Default Template PDF</label>
                        <input type="hidden" name="settings[invoice_template]" id="invoiceTemplateInput" value="{{ $invoiceSettings['invoice_template'] ?? 'modern' }}">
                        <p class="text-muted small mb-3">Klik template untuk memilih. Template ini digunakan untuk download PDF & kirim email.</p>

                        <div class="row g-3">
                            {{-- Modern --}}
                            <div class="col-md-3">
                                <div class="card template-card {{ ($invoiceSettings['invoice_template'] ?? 'modern') === 'modern' ? 'border-primary shadow' : 'border' }}" data-template="modern" style="cursor:pointer;transition:all 0.2s;">
                                    <div class="card-body p-0">
                                        <div style="background:linear-gradient(135deg,#1a56db,#2563eb);border-radius:4px 4px 0 0;padding:8px 10px;">
                                            <div style="width:50px;height:46px;background:rgba(255,255,255,.15);border-radius:4px;display:inline-block;vertical-align:middle;"></div>
                                            <div style="display:inline-block;margin-left:6px;vertical-align:middle;">
                                                <div style="width:70px;height:6px;background:rgba(255,255,255,.5);border-radius:2px;margin-bottom:4px;"></div>
                                                <div style="width:90px;height:5px;background:rgba(255,255,255,.25);border-radius:2px;"></div>
                                            </div>
                                            <div style="float:right;text-align:right;">
                                                <div style="width:45px;height:7px;background:#fff;border-radius:2px;margin-bottom:3px;"></div>
                                                <div style="width:35px;height:5px;background:rgba(255,255,255,.7);border-radius:2px;"></div>
                                            </div>
                                        </div>
                                        <div style="padding:8px 10px;">
                                            <div style="display:flex;gap:8px;">
                                                <div style="flex:1"><div style="width:100%;height:4px;background:#e2e8f0;border-radius:2px;margin-bottom:3px;"></div><div style="width:70%;height:3px;background:#f1f5f9;border-radius:2px;"></div></div>
                                                <div style="flex:1;text-align:right;"><div style="width:60%;height:4px;background:#e2e8f0;border-radius:2px;margin-bottom:3px;margin-left:auto;"></div><div style="width:40%;height:3px;background:#f1f5f9;border-radius:2px;margin-left:auto;"></div></div>
                                            </div>
                                            <div style="margin:6px 0;display:flex;gap:6px;">
                                                <div style="flex:1;"><div style="background:#1a56db;border-radius:2px;height:3px;margin-bottom:2px;"></div><div style="background:#f1f5f9;border-radius:1px;height:2px;margin-bottom:2px;"></div><div style="background:#f1f5f9;border-radius:1px;height:2px;"></div></div>
                                                <div style="width:18px;"><div style="background:#1a56db;border-radius:2px;height:3px;margin-bottom:2px;"></div><div style="background:#f1f5f9;border-radius:1px;height:2px;margin-bottom:2px;"></div><div style="background:#f1f5f9;border-radius:1px;height:2px;"></div></div>
                                                <div style="width:22px;"><div style="background:#1a56db;border-radius:2px;height:3px;margin-bottom:2px;"></div><div style="background:#f1f5f9;border-radius:1px;height:2px;margin-bottom:2px;"></div><div style="background:#f1f5f9;border-radius:1px;height:2px;"></div></div>
                                            </div>
                                            <div style="border-top:1px solid #e2e8f0;padding-top:4px;margin-top:4px;font-size:9px;color:#1a56db;font-weight:600;">Grand Total &mdash; Rp 0</div>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-transparent text-center py-1 small fw-semibold {{ ($invoiceSettings['invoice_template'] ?? 'modern') === 'modern' ? 'text-primary' : '' }}">Modern</div>
                                </div>
                            </div>

                            {{-- Classic --}}
                            <div class="col-md-3">
                                <div class="card template-card {{ ($invoiceSettings['invoice_template'] ?? 'modern') === 'classic' ? 'border-primary shadow' : 'border' }}" data-template="classic" style="cursor:pointer;transition:all 0.2s;">
                                    <div class="card-body p-0">
                                        <div style="border-bottom:2px solid #000;padding:8px 10px;">
                                            <div style="float:left;width:50px;height:46px;border:1px solid #ccc;display:flex;align-items:center;justify-content:center;font-size:18px;">&#x1F527;</div>
                                            <div style="margin-left:58px;">
                                                <div style="width:80px;height:6px;background:#000;border-radius:0;margin-bottom:4px;"></div>
                                                <div style="width:60px;height:4px;background:#666;border-radius:0;"></div>
                                            </div>
                                        </div>
                                        <div style="padding:8px 10px;">
                                            <div style="display:flex;font-size:8px;font-family:serif;">
                                                <div style="flex:1"><strong>Kepada:</strong><br><div style="width:60px;height:3px;background:#ccc;margin:2px 0;"></div><div style="width:45px;height:2px;background:#eee;"></div></div>
                                                <div style="flex:1;text-align:right"><strong>No:</strong> INV-001<br><strong>Tgl:</strong> 01/08/26</div>
                                            </div>
                                            <div style="margin:5px 0;border-bottom:1px solid #000;font-size:8px;display:flex;justify-content:space-between;padding-bottom:2px;">
                                                <span>Deskripsi</span><span>Qty</span><span>Total</span>
                                            </div>
                                            <div style="font-size:8px;display:flex;justify-content:space-between;border-bottom:1px solid #eee;padding:2px 0;"><span>Service</span><span>1</span><span>Rp 0</span></div>
                                            <div style="text-align:right;font-size:9px;font-weight:bold;border-top:1px solid #000;padding-top:3px;margin-top:3px;">TOTAL: Rp 0</div>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-transparent text-center py-1 small fw-semibold {{ ($invoiceSettings['invoice_template'] ?? 'modern') === 'classic' ? 'text-primary' : '' }}">Classic</div>
                                </div>
                            </div>

                            {{-- Minimal --}}
                            <div class="col-md-3">
                                <div class="card template-card {{ ($invoiceSettings['invoice_template'] ?? 'modern') === 'minimal' ? 'border-primary shadow' : 'border' }}" data-template="minimal" style="cursor:pointer;transition:all 0.2s;">
                                    <div class="card-body p-0" style="font-family:Inter,sans-serif;">
                                        <div style="padding:10px 10px 6px;display:flex;justify-content:space-between;align-items:flex-start;">
                                            <div>
                                                <div style="width:70px;height:7px;background:#111;border-radius:2px;margin-bottom:3px;"></div>
                                                <div style="width:50px;height:4px;background:#aaa;border-radius:1px;"></div>
                                            </div>
                                            <div style="text-align:right;">
                                                <span style="display:inline-block;padding:2px 8px;border-radius:10px;font-size:7px;background:#e8f5e9;color:#2e7d32;font-weight:600;">LUNAS</span>
                                                <div style="font-size:8px;color:#999;margin-top:2px;">#INV-001</div>
                                            </div>
                                        </div>
                                        <div style="padding:0 10px;">
                                            <div style="margin:5px 0;display:flex;font-size:8px;gap:5px;">
                                                <div style="flex:1"><div style="color:#aaa;font-size:7px;text-transform:uppercase;">Kepada</div><div style="width:55px;height:4px;background:#333;margin:2px 0;"></div><div style="width:40px;height:3px;background:#ccc;"></div></div>
                                                <div style="flex:1"><div style="color:#aaa;font-size:7px;text-transform:uppercase;">Tanggal</div><div style="width:45px;height:4px;background:#333;margin:2px 0;"></div></div>
                                            </div>
                                            <div style="border-bottom:1px solid #e0e0e0;font-size:7px;color:#999;text-transform:uppercase;display:flex;justify-content:space-between;padding-bottom:2px;margin-top:4px;">
                                                <span>#</span><span>Deskripsi</span><span>Qty</span><span>Jumlah</span>
                                            </div>
                                            <div style="font-size:8px;display:flex;justify-content:space-between;padding:3px 0;border-bottom:1px solid #f5f5f5;"><span style="color:#bbb;">1</span><span>Service</span><span>1</span><span>Rp 0</span></div>
                                            <div style="display:flex;justify-content:flex-end;margin-top:3px;border-top:1px solid #1a1a1a;padding-top:3px;font-size:10px;font-weight:700;">Rp 0</div>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-transparent text-center py-1 small fw-semibold {{ ($invoiceSettings['invoice_template'] ?? 'modern') === 'minimal' ? 'text-primary' : '' }}">Minimal</div>
                                </div>
                            </div>

                            {{-- Thermal --}}
                            <div class="col-md-3">
                                <div class="card template-card {{ ($invoiceSettings['invoice_template'] ?? 'modern') === 'thermal' ? 'border-primary shadow' : 'border' }}" data-template="thermal" style="cursor:pointer;transition:all 0.2s;">
                                    <div class="card-body p-0" style="font-family:'Courier New',monospace;background:#fffef7;">
                                        <div style="padding:8px 10px;text-align:center;font-size:10px;font-weight:bold;border-bottom:1px dashed #000;">
                                            Bengkel<br><span style="font-size:7px;">Jl. Raya No.1</span>
                                        </div>
                                        <div style="padding:6px 10px;text-align:center;font-weight:bold;font-size:9px;">
                                            INVOICE<br>INV-001
                                        </div>
                                        <div style="padding:0 10px;font-size:7px;border-top:1px dashed #000;">
                                            <div style="display:flex;justify-content:space-between;"><span>Tgl</span><span>01/08/26</span></div>
                                            <div style="display:flex;justify-content:space-between;"><span>Pelanggan</span><span>Customer</span></div>
                                        </div>
                                        <div style="padding:0 10px;border-top:1px dashed #000;font-size:7px;">
                                            <div style="display:flex;justify-content:space-between;"><span>Service</span><span>1x Rp 0</span></div>
                                        </div>
                                        <div style="padding:4px 10px;border-top:1px solid #000;text-align:right;font-size:9px;font-weight:bold;">
                                            TOTAL: Rp 0
                                        </div>
                                        <div style="padding:4px 10px;text-align:center;border-top:1px solid #000;">
                                            <span style="border:1px solid #000;padding:1px 10px;font-size:7px;font-weight:bold;">LUNAS</span>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-transparent text-center py-1 small fw-semibold {{ ($invoiceSettings['invoice_template'] ?? 'modern') === 'thermal' ? 'text-primary' : '' }}">Thermal</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <h6 class="mb-3">Tampilan (Warna & Font)</h6>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Warna Aksen Invoice</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" name="settings[invoice_accent_color]" class="form-control form-control-color" value="{{ $invoiceSettings['invoice_accent_color'] ?? '#2563eb' }}" style="width:70px;height:44px;padding:4px;">
                                <input type="text" class="form-control form-control-sm" value="{{ $invoiceSettings['invoice_accent_color'] ?? '#2563eb' }}" style="max-width:110px;" oninput="document.querySelector('input[name=\'settings[invoice_accent_color]\'][type=color]').value = this.value;">
                            </div>
                            <small class="text-muted">Warna header, border & aksen pada PDF dan halaman invoice publik.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Font Invoice</label>
                            <select name="settings[invoice_font]" class="form-select">
                                @foreach(['Inter' => 'Inter', 'Poppins' => 'Poppins', 'Roboto' => 'Roboto', 'Lato' => 'Lato', 'Open Sans' => 'Open Sans', 'Helvetica' => 'Helvetica', 'Times New Roman' => 'Times New Roman', 'Courier New' => 'Courier New'] as $val => $label)
                                    <option value="{{ $val }}" {{ ($invoiceSettings['invoice_font'] ?? 'Inter') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Font utama untuk template PDF invoice.</small>
                        </div>
                    </div>
                    <hr>
                    <h6>Info Pembayaran</h6>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Bank / Rekening</label>
                            <input type="text" name="settings[bank_account]" class="form-control" value="{{ $settings['bank_account'] ?? '' }}" placeholder="BTN 19501300000955 a.n. ...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">QRIS</label>
                            <select name="settings[qris_available]" class="form-select">
                                <option value="0" {{ ($settings['qris_available'] ?? '0') == '0' ? 'selected' : '' }}>Tidak</option>
                                <option value="1" {{ ($settings['qris_available'] ?? '0') == '1' ? 'selected' : '' }}>Tersedia</option>
                            </select>
                        </div>
                    </div>
                    <hr>
                    <h6 class="mb-3">Tata Letak Bagian Invoice</h6>
                    <p class="text-muted small mb-3">Aktifkan/nonaktifkan bagian yang tampil di invoice dan atur urutannya dengan tombol panah.</p>
                    <input type="hidden" name="settings[invoice_sections]" id="invoiceSectionsInput" value="{{ json_encode($invoiceSections) }}">
                    <div id="invoiceSectionsList">
                        @foreach ($invoiceSectionOrder as $section)
                        <div class="invoice-section-item d-flex align-items-center gap-2 border rounded p-2 mb-2" data-key="{{ $section }}">
                            <span class="text-muted" style="cursor:grab;"><i class="fas fa-grip-vertical"></i></span>
                            <input class="form-check-input invoice-section-checkbox" type="checkbox" value="{{ $section }}" {{ in_array($section, $invoiceSections) ? 'checked' : '' }}>
                            <label class="form-check-label flex-grow-1">{{ $invoiceSectionDefs[$section] ?? $section }}</label>
                            <button type="button" class="btn btn-sm btn-outline-secondary btn-move-up" title="Naik"><i class="fas fa-arrow-up"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-secondary btn-move-down" title="Turun"><i class="fas fa-arrow-down"></i></button>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="notification">
            <div class="card">
                <div class="card-body">
                    <h6>Auto-Send Notifications</h6>
                    @foreach(['service_created' => 'Service Created', 'service_completed' => 'Service Completed', 'invoice_generated' => 'Invoice Generated', 'payment_received' => 'Payment Received', 'service_reminder' => 'Service Reminder'] as $key => $label)
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="settings[notify_{{ $key }}]" value="1" {{ ($notificationSettings['notify_' . $key] ?? '') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label">{{ $label }}</label>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Settings</button>
    </div>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const cards = document.querySelectorAll('.template-card');
    const input = document.getElementById('invoiceTemplateInput');

    cards.forEach(card => {
        card.addEventListener('click', function () {
            const template = this.dataset.template;
            input.value = template;

            cards.forEach(c => {
                c.classList.remove('border-primary', 'shadow');
                c.querySelector('.card-footer').classList.remove('text-primary');
            });
            this.classList.add('border-primary', 'shadow');
            this.querySelector('.card-footer').classList.add('text-primary');
        });

        card.addEventListener('mouseenter', function () {
            if (!this.classList.contains('border-primary')) {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 4px 12px rgba(0,0,0,.08)';
            }
        });
        card.addEventListener('mouseleave', function () {
            if (!this.classList.contains('border-primary')) {
                this.style.transform = '';
                this.style.boxShadow = '';
            }
        });
    });

    // ── Invoice layout builder ──
    const sectionsInput = document.getElementById('invoiceSectionsInput');
    const sectionCheckboxes = document.querySelectorAll('.invoice-section-checkbox');

    function syncInvoiceSections() {
        const keys = [];
        document.querySelectorAll('.invoice-section-checkbox:checked').forEach(function (cb) {
            keys.push(cb.value);
        });
        sectionsInput.value = JSON.stringify(keys);
    }

    sectionCheckboxes.forEach(function (cb) {
        cb.addEventListener('change', syncInvoiceSections);
    });

    document.querySelectorAll('.btn-move-up').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const item = this.closest('.invoice-section-item');
            const prev = item.previousElementSibling;
            if (prev && prev.classList.contains('invoice-section-item')) {
                item.parentNode.insertBefore(item, prev);
                syncInvoiceSections();
            }
        });
    });

    document.querySelectorAll('.btn-move-down').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const item = this.closest('.invoice-section-item');
            const next = item.nextElementSibling;
            if (next && next.classList.contains('invoice-section-item')) {
                item.parentNode.insertBefore(next, item);
                syncInvoiceSections();
            }
        });
    });

    document.querySelector('form').addEventListener('submit', syncInvoiceSections);
});
</script>
@endpush
@endsection
