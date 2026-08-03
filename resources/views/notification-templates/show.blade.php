@extends('layouts.app')

@section('title', 'Detail Notification Template')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Detail Notification Template</h4>
    <div>
        <a href="{{ route('notification-templates.edit', $template) }}" class="btn btn-warning btn-sm">
            <i class="bi bi-pencil"></i> Edit
        </a>
        <a href="{{ route('notification-templates.preview', $template) }}" class="btn btn-info btn-sm">
            <i class="bi bi-eye"></i> Preview
        </a>
        <a href="{{ route('notification-templates.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><strong>Informasi Template</strong></div>
    <div class="card-body">
        <table class="table table-sm table-borderless mb-0">
            <tr>
                <td width="150" class="text-muted">Name</td>
                <td><strong>{{ $template->name }}</strong></td>
            </tr>
            <tr>
                <td class="text-muted">Slug</td>
                <td><code>{{ $template->slug }}</code></td>
            </tr>
            <tr>
                <td class="text-muted">Channel</td>
                <td>
                    @if($template->channel === 'email')
                        <span class="badge bg-info">Email</span>
                    @else
                        <span class="badge bg-success">WhatsApp</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="text-muted">Status</td>
                <td>
                    @if($template->is_active)
                        <span class="badge bg-success">Active</span>
                    @else
                        <span class="badge bg-secondary">Inactive</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>
</div>

@if($template->subject)
<div class="card mb-3">
    <div class="card-header"><strong>Subject</strong></div>
    <div class="card-body">
        <p class="mb-0">{{ $template->subject }}</p>
    </div>
</div>
@endif

<div class="card mb-3">
    <div class="card-header"><strong>Body</strong></div>
    <div class="card-body">
        <pre class="mb-0" style="white-space:pre-wrap;">{{ $template->body }}</pre>
    </div>
</div>

<div class="mt-3">
    <a href="{{ route('notification-templates.index') }}" class="btn btn-secondary">Kembali</a>
</div>
@endsection
