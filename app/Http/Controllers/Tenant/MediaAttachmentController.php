<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\MediaAttachment;
use App\Models\ServiceObservationPoint;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MediaAttachmentController extends Controller
{
    private const ATTACHABLE_MAP = [
        'customer' => Customer::class,
        'vehicle' => Vehicle::class,
        'observation_point' => ServiceObservationPoint::class,
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
        // Business documents live on the PRIVATE disk — never web-readable
        // without authentication. Download goes through attachments.download.
        $path = $file->store('attachments', 'local');

        $attachable->mediaAttachments()->create([
            'name' => $validated['name'] ?: $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);

        return back()->with('success', 'Dokumen berhasil diunggah.');
    }

    /**
     * Authenticated download. Legacy files uploaded to the public disk keep
     * working; new uploads are streamed from local storage.
     */
    public function download(MediaAttachment $media)
    {
        if (Storage::disk('local')->exists($media->file_path)) {
            return Storage::disk('local')->download($media->file_path, $media->name);
        }

        abort_unless(Storage::disk('public')->exists($media->file_path), 404, 'Berkas tidak ditemukan.');

        return Storage::disk('public')->download($media->file_path, $media->name);
    }

    public function destroy(MediaAttachment $media)
    {
        // Only the uploader or a supervisor may delete documents (IDOR guard).
        $isUploader = (int) $media->uploaded_by === (int) auth()->id();
        $isSupervisor = auth()->user()?->hasAnyRole(['super_admin', 'admin', 'manager']);

        abort_unless($isUploader || $isSupervisor, 403, 'Anda tidak berhak menghapus dokumen ini.');

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($media->file_path)) {
                Storage::disk($disk)->delete($media->file_path);
                break;
            }
        }
        $media->delete();

        return back()->with('success', 'Dokumen dihapus.');
    }
}
