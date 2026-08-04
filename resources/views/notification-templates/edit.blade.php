@extends('layouts.app')

@section('title', 'Edit Notification Template - Aplikasi Bengkel Terbaik')

@section('content')
<h4 class="mb-3">Edit Notification Template</h4>

<div class="card">
    <div class="card-body">
        <form action="{{ route('notification-templates.update', $template) }}" method="POST">
            @csrf @method('PUT')
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $template->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Slug <span class="text-danger">*</span></label>
                    <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $template->slug) }}" required>
                    @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Channel <span class="text-danger">*</span></label>
                    <select name="channel" class="form-select @error('channel') is-invalid @enderror" required>
                        <option value="email" {{ old('channel', $template->channel) === 'email' ? 'selected' : '' }}>Email</option>
                        <option value="whatsapp" {{ old('channel', $template->channel) === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                    </select>
                    @error('channel')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Active</label>
                    <div class="form-check mt-2">
                        <input type="checkbox" name="is_active" class="form-check-input" value="1" {{ old('is_active', $template->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label">Active</label>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Subject</label>
                <input type="text" name="subject" class="form-control" value="{{ old('subject', $template->subject) }}">
            </div>
            <div class="mb-3">
                <label class="form-label">Body <span class="text-danger">*</span></label>
                <textarea name="body" class="form-control @error('body') is-invalid @enderror" rows="10" required>{{ old('body', $template->body) }}</textarea>
                @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="card bg-light mb-3">
                <div class="card-body">
                    <h6>Available Placeholders</h6>
                    <div class="row small">
                        <div class="col-md-4"><code>{customer_name}</code> - Customer name</div>
                        <div class="col-md-4"><code>{vehicle_plate}</code> - Vehicle license plate</div>
                        <div class="col-md-4"><code>{service_date}</code> - Service date</div>
                        <div class="col-md-4"><code>{job_no}</code> - Job number</div>
                        <div class="col-md-4"><code>{invoice_number}</code> - Invoice number</div>
                        <div class="col-md-4"><code>{total_amount}</code> - Total amount</div>
                        <div class="col-md-4"><code>{payment_method}</code> - Payment method</div>
                        <div class="col-md-4"><code>{workshop_name}</code> - Workshop name</div>
                        <div class="col-md-4"><code>{workshop_phone}</code> - Workshop phone</div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Update Template</button>
            <a href="{{ route('notification-templates.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection
