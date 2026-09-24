<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\ProfileController;
use App\Models\Assignment;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Http\Request;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard Utama: Menampilkan ringkasan matkul & deadline terdekat
    Route::get('/dashboard', function (Request $request) {
        $user = $request->user();
        
        $courses = $user->courses()
            ->withCount(['assignments' => fn($q) => $q->where('status', '!=', 'completed')])
            ->latest()
            ->get();

        $upcomingAssignments = Assignment::whereIn('course_id', $user->courses()->pluck('id'))
            ->where('status', '!=', 'completed')
            ->orderBy('deadline', 'asc')
            ->with('course')
            ->take(30)
            ->get();

        return Inertia::render('Dashboard', [
            'courses' => $courses,
            'upcomingAssignments' => $upcomingAssignments,
        ]);
    })->name('dashboard');

    // Manajemen Mata Kuliah
    Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');
    Route::put('/courses/{course}', [CourseController::class, 'update'])->name('courses.update');
    Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');

    // Manajemen Tugas Kuliah
    Route::post('/courses/{course}/assignments', [AssignmentController::class, 'store'])->name('assignments.store');
    Route::put('/assignments/{assignment}', [AssignmentController::class, 'update'])->name('assignments.update');
    Route::patch('/assignments/{assignment}/status', [AssignmentController::class, 'updateStatus'])->name('assignments.updateStatus');
    Route::delete('/assignments/{assignment}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');

    // Manajemen Materi Kuliah
    Route::post('/courses/{course}/materials', [MaterialController::class, 'store'])->name('materials.store');
    Route::match(['put', 'post'], '/materials/{material}', [MaterialController::class, 'update'])->name('materials.update');
    Route::get('/materials/{material}/preview', [MaterialController::class, 'preview'])->name('materials.preview');
    Route::get('/materials/{material}/download', [MaterialController::class, 'download'])->name('materials.download');
    Route::delete('/materials/{material}', [MaterialController::class, 'destroy'])->name('materials.destroy');

    // Profil Mahasiswa (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Webhook Cron Scheduler (Gratis untuk Cloud Deployment via cron-job.org)
Route::get('/cron/run-schedule', function (Request $request) {
    $secret = config('app.key');
    $providedKey = $request->query('key');

    if (!$providedKey || !hash_equals((string) $secret, (string) $providedKey)) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized cron trigger.',
        ], 403);
    }

    \Illuminate\Support\Facades\Artisan::call('schedule:run');
    $output = \Illuminate\Support\Facades\Artisan::output();

    return response()->json([
        'status' => 'success',
        'message' => 'Scheduler executed successfully.',
        'output' => $output,
        'timestamp' => now()->toIso8601String(),
    ]);
});

require __DIR__.'/auth.php';