<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiDocsAuthController;
use App\Http\Controllers\ArtisanCommandController;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/docs/api');
    }
    return view('auth.login');
})->name('login');

Route::middleware('guest')->group(function () {
    Route::post('/login', [ApiDocsAuthController::class, 'login'])
        ->name('login.submit');
});

Route::get('/logout', [ApiDocsAuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/artisan', [ArtisanCommandController::class, 'index'])->name('artisan.index');
    Route::post('/artisan/execute', [ArtisanCommandController::class, 'execute'])->name('artisan.execute');
});



//fcm logs routes
Route::get('fcm', function () {

    $path = storage_path('logs/fcm.log');
    // return ( $path);

    if (!File::exists($path)) {
        abort(404, 'Log file not found.');
    }

    return Response::make(File::get($path), 200, [
        'Content-Type' => 'text/plain',
    ]);
});