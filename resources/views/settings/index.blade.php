@extends('layouts.app')

@section('title')
Settings - {{ config('app.name') }}
@endsection

@section('content')
<h4 class="mb-3">Settings</h4>

<form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

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
                        <input type="file" name="logo" class="form-control">
                        @if(!empty($settings['company_logo']))
                            <small class="text-muted">Current: {{ $settings['company_logo'] }}</small>
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
                            <input type="password" name="settings[smtp_password]" class="form-control" value="{{ $emailSettings['smtp_password'] ?? '' }}">
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
@endsection
