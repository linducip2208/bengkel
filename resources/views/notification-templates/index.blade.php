@extends('layouts.app')

@section('title')
Notification Templates - {{ config('app.name') }}
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Notification Templates</h4>
    <a href="{{ route('notification-templates.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Create Template</a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Channel</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($templates as $template)
                <tr>
                    <td>{{ $template->name }}</td>
                    <td><code>{{ $template->slug }}</code></td>
                    <td>
                        @if($template->channel === 'email')
                            <span class="badge bg-info">Email</span>
                        @else
                            <span class="badge bg-success">WhatsApp</span>
                        @endif
                    </td>
                    <td>
                        @if($template->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('notification-templates.preview', $template) }}" class="btn btn-sm btn-info" title="Preview"><i class="bi bi-eye"></i></a>
                        <a href="{{ route('notification-templates.edit', $template) }}" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('notification-templates.destroy', $template) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this template?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center">No templates found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-2">{{ $templates->links() }}</div>
@endsection
