<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MaterialController extends Controller
{
    private function getStorageDisk(): string
    {
        return config('filesystems.default') === 's3' ? 's3' : 'public';
    }

    public function store(Request $request, Course $course)
    {
        abort_if($course->user_id !== Auth::id(), 403);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'meeting_number' => 'nullable|integer|min:1|max:50',
            'external_link' => 'nullable|url|max:500',
            'notes' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,ppt,pptx,doc,docx,zip,rar,jpg,jpeg,png,webp,gif|max:20480', // Maksimal 20MB (SVG ditiadakan untuk mitigasi XSS)
        ]);

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('materials', $this->getStorageDisk());
        }

        $course->materials()->create([
            'title' => $validated['title'],
            'meeting_number' => $validated['meeting_number'] ?? null,
            'external_link' => $validated['external_link'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'file_path' => $filePath,
        ]);

        return redirect()->back()->with('success', 'Materi kuliah berhasil diunggah!');
    }

    public function update(Request $request, Material $material)
    {
        abort_if($material->course->user_id !== Auth::id(), 403);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'meeting_number' => 'nullable|integer|min:1|max:50',
            'external_link' => 'nullable|url|max:500',
            'notes' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,ppt,pptx,doc,docx,zip,rar,jpg,jpeg,png,webp,gif|max:20480',
        ]);

        if ($request->hasFile('file')) {
            $disk = $this->getStorageDisk();
            if ($material->file_path && Storage::disk($disk)->exists($material->file_path)) {
                Storage::disk($disk)->delete($material->file_path);
            }
            $material->file_path = $request->file('file')->store('materials', $disk);
        }

        $material->title = $validated['title'];
        $material->meeting_number = $validated['meeting_number'] ?? null;
        $material->external_link = $validated['external_link'] ?? null;
        if (array_key_exists('notes', $validated)) {
            $material->notes = $validated['notes'];
        }
        $material->save();

        return redirect()->back()->with('success', 'Materi kuliah berhasil diperbarui!');
    }

    public function preview(Material $material)
    {
        abort_if($material->course->user_id !== Auth::id(), 403);

        $disk = $this->getStorageDisk();

        if (!$material->file_path || !Storage::disk($disk)->exists($material->file_path)) {
            abort(404, 'Berkas materi tidak ditemukan.');
        }

        $extension = strtolower(pathinfo($material->file_path, PATHINFO_EXTENSION));
        if (in_array($extension, ['svg', 'html', 'htm', 'xml', 'exe', 'sh', 'php'])) {
            abort(403, 'Pratinjau berkas format ini dinonaktifkan demi alasan keamanan.');
        }

        $mime = Storage::disk($disk)->mimeType($material->file_path) ?: 'application/pdf';

        return Storage::disk($disk)->response($material->file_path, basename($material->file_path), [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . basename($material->file_path) . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function download(Material $material)
    {
        abort_if($material->course->user_id !== Auth::id(), 403);

        $disk = $this->getStorageDisk();

        if (!$material->file_path || !Storage::disk($disk)->exists($material->file_path)) {
            abort(404, 'Berkas materi tidak ditemukan.');
        }

        $extension = pathinfo($material->file_path, PATHINFO_EXTENSION);
        $cleanTitle = preg_replace('/[^A-Za-z0-9_\-]/', '_', $material->title);
        $filename = "{$cleanTitle}.{$extension}";

        return Storage::disk($disk)->download($material->file_path, $filename);
    }

    public function destroy(Material $material)
    {
        abort_if($material->course->user_id !== Auth::id(), 403);

        $disk = $this->getStorageDisk();

        if ($material->file_path && Storage::disk($disk)->exists($material->file_path)) {
            Storage::disk($disk)->delete($material->file_path);
        }

        $material->delete();

        return redirect()->back()->with('success', 'Materi kuliah berhasil dihapus.');
    }
}