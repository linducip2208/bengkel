<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\MediaAttachment;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MediaAttachmentController extends Controller
{
    private const ATTACHABLE_MAP = [
        'customer' => Customer::class,
        'vehicle' => Vehicle::class,
    ];

    public function store(Request $request)
    {
        $validated = $request->validate([
            'attachable_type' => ['required', Rule::in(array_keys(self::ATTACHABLE_MAP))],
            'attachable_id' => 'required|integer',
            'name' => 'nullable|string|max:255',
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,png,jpeg,doc,docx,xls,xlsx'],
        ]);

        $class = self::ATTACHABLE_MAP[$validated['attachable_type']];
        $attachable = $class::findOrFail($validated['attachable_id']);

        $file = $request->file('file');
        $path = $file->store('attachments', 'public');

        $attachable->mediaAttachments()->create([
            'name' => $validated['name'] ?: $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);

        return back()->with('success', 'Dokumen berhasil diunggah.');
    }

    public function destroy(MediaAttachment $media)
    {
        Storage::disk('public')->delete($media->file_path);
        $media->delete();

        return back()->with('success', 'Dokumen dihapus.');
    }
}
