<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AssignmentController extends Controller
{
    public function store(Request $request, Course $course)
    {
        abort_if($course->user_id !== Auth::id(), 403);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'assignment_type' => 'nullable|string|in:individual,group',
            'description' => 'nullable|string',
            'deadline' => 'required|date',
            'submission_url' => 'nullable|url|max:500',
            'links' => 'nullable|array',
            'links.*.title' => 'nullable|string|max:100',
            'links.*.url' => 'nullable|url|max:500',
        ]);

        $links = [];
        if (!empty($validated['links']) && is_array($validated['links'])) {
            foreach ($validated['links'] as $item) {
                $url = trim($item['url'] ?? '');
                if ($url && filter_var($url, FILTER_VALIDATE_URL)) {
                    $links[] = [
                        'title' => trim($item['title'] ?? '') ?: 'Link Terkait',
                        'url' => $url,
                    ];
                }
            }
        }

        $submissionUrl = $validated['submission_url'] ?? null;
        if (!empty($links)) {
            $submissionUrl = $links[0]['url'];
        } elseif (!empty($submissionUrl)) {
            $links = [
                [
                    'title' => 'Link Pengumpulan',
                    'url' => $submissionUrl,
                ],
            ];
        }

        $course->assignments()->create([
            'title' => $validated['title'],
            'assignment_type' => $validated['assignment_type'] ?? 'individual',
            'description' => $validated['description'] ?? null,
            'deadline' => $validated['deadline'],
            'submission_url' => $submissionUrl,
            'links' => !empty($links) ? $links : null,
        ]);

        return redirect()->back()->with('success', 'Tugas berhasil ditambahkan!');
    }

    public function update(Request $request, Assignment $assignment)
    {
        abort_if($assignment->course->user_id !== Auth::id(), 403);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'assignment_type' => 'nullable|string|in:individual,group',
            'description' => 'nullable|string',
            'deadline' => 'required|date',
            'submission_url' => 'nullable|url|max:500',
            'links' => 'nullable|array',
            'links.*.title' => 'nullable|string|max:100',
            'links.*.url' => 'nullable|url|max:500',
        ]);

        $links = [];
        if (!empty($validated['links']) && is_array($validated['links'])) {
            foreach ($validated['links'] as $item) {
                $url = trim($item['url'] ?? '');
                if ($url && filter_var($url, FILTER_VALIDATE_URL)) {
                    $links[] = [
                        'title' => trim($item['title'] ?? '') ?: 'Link Terkait',
                        'url' => $url,
                    ];
                }
            }
        }

        $submissionUrl = $validated['submission_url'] ?? null;
        if (!empty($links)) {
            $submissionUrl = $links[0]['url'];
        } elseif (!empty($submissionUrl)) {
            $links = [
                [
                    'title' => 'Link Pengumpulan',
                    'url' => $submissionUrl,
                ],
            ];
        }

        // Cek jika waktu deadline diubah: reset reminder agar dapat terkirim kembali
        $oldDeadline = Carbon::parse($assignment->deadline)->format('Y-m-d H:i');
        $newDeadline = Carbon::parse($validated['deadline'])->format('Y-m-d H:i');

        if ($oldDeadline !== $newDeadline) {
            $assignment->reminder_h24_sent_at = null;
            $assignment->reminder_h3_sent_at = null;
        }

        $assignment->title = $validated['title'];
        $assignment->assignment_type = $validated['assignment_type'] ?? 'individual';
        $assignment->description = $validated['description'] ?? null;
        $assignment->deadline = $validated['deadline'];
        $assignment->submission_url = $submissionUrl;
        $assignment->links = !empty($links) ? $links : null;
        $assignment->save();

        return redirect()->back()->with('success', 'Tugas berhasil diperbarui!');
    }

    public function updateStatus(Request $request, Assignment $assignment)
    {
        abort_if($assignment->course->user_id !== Auth::id(), 403);

        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,completed',
        ]);

        $assignment->update($validated);

        $message = $validated['status'] === 'completed'
            ? 'Tugas berhasil ditandai selesai! 🎉'
            : 'Status tugas berhasil diperbarui.';

        return redirect()->back()->with('success', $message);
    }

    public function destroy(Assignment $assignment)
    {
        abort_if($assignment->course->user_id !== Auth::id(), 403);

        $assignment->delete();

        return redirect()->back()->with('success', 'Tugas berhasil dihapus.');
    }
}