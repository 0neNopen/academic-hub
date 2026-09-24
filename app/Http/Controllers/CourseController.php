<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class CourseController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'lecturer_name' => 'nullable|string|max:255',
            'day_of_week' => 'nullable|string|max:20',
            'start_time' => 'nullable',
            'end_time' => 'nullable',
            'color' => 'nullable|string|max:20',
        ]);

        $request->user()->courses()->create($validated);

        return redirect()->back()->with('success', 'Mata kuliah berhasil ditambahkan!');
    }

    public function show(Course $course)
    {
        // Pastikan mahasiswa hanya bisa melihat matkul miliknya sendiri
        abort_if($course->user_id !== Auth::id(), 403);

        $course->load([
            'materials' => fn($q) => $q->orderBy('meeting_number', 'asc')->latest(),
            'assignments' => fn($q) => $q->orderBy('deadline', 'asc'),
        ]);

        return Inertia::render('Courses/Show', [
            'course' => $course,
        ]);
    }

    public function update(Request $request, Course $course)
    {
        abort_if($course->user_id !== Auth::id(), 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'lecturer_name' => 'nullable|string|max:255',
            'day_of_week' => 'nullable|string|max:20',
            'start_time' => 'nullable',
            'end_time' => 'nullable',
            'color' => 'nullable|string|max:20',
        ]);

        $course->update($validated);

        return redirect()->back()->with('success', 'Mata kuliah berhasil diperbarui!');
    }

    public function destroy(Course $course)
    {
        abort_if($course->user_id !== Auth::id(), 403);
        $course->delete();

        return redirect()->route('dashboard')->with('success', 'Mata kuliah berhasil dihapus.');
    }
}