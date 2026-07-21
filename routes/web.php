<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\PollController;
use App\Http\Controllers\KbArticleController;
use App\Http\Controllers\KbCategoryController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\TwoFactorController;
use Illuminate\Support\Facades\Route;

// Page d'accueil publique
Route::get('/', function () {
    return view('welcome');
});

// Routes protégées par authentification
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:view dashboard')
        ->name('dashboard');

    // === MODULE CHAT ===
    Route::prefix('chat')->name('chat.')->group(function () {
        Route::get('/', [ChatController::class, 'index'])->name('index');
        Route::get('/users/search', [ChatController::class, 'searchUsers'])->name('users.search');
        Route::get('/conversation/{id}', [ChatController::class, 'show'])->name('show');
        Route::post('/message', [ChatController::class, 'sendMessage'])
            ->middleware('permission:send messages')->name('message.store');
        Route::post('/message/{message}/react', [ChatController::class, 'toggleReaction'])
            ->middleware('permission:send messages')->name('message.react');
        Route::post('/typing', [ChatController::class, 'typing'])->name('typing');
        Route::post('/conversation', [ChatController::class, 'createConversation'])
            ->middleware('permission:view messages')->name('conversation.store');
    });

    // === MODULE SONDAGES ===
    Route::prefix('polls')->name('polls.')->group(function () {
        Route::get('/', [PollController::class, 'index'])->name('index');
        Route::get('/create', [PollController::class, 'create'])
            ->middleware('permission:create polls')->name('create');
        Route::post('/', [PollController::class, 'store'])
            ->middleware('permission:create polls')->name('store');
        Route::get('/{poll}', [PollController::class, 'show'])->name('show');
        Route::delete('/{poll}', [PollController::class, 'destroy'])->name('destroy');
        Route::post('/{poll}/vote', [PollController::class, 'vote'])
            ->middleware('permission:vote polls')->name('vote');
        Route::get('/{poll}/results', [PollController::class, 'results'])->name('results');
        Route::get('/{poll}/export', [PollController::class, 'exportCsv'])->name('export');
    });

    // === MODULE KNOWLEDGE BASE ===
    Route::prefix('knowledge')->name('knowledge.')->group(function () {
        Route::get('/', [KbArticleController::class, 'index'])->name('index');

        Route::prefix('categories')->name('categories.')->middleware('permission:manage categories')->group(function () {
            Route::get('/', [KbCategoryController::class, 'index'])->name('index');
            Route::post('/', [KbCategoryController::class, 'store'])->name('store');
            Route::patch('/{category}', [KbCategoryController::class, 'update'])->name('update');
            Route::delete('/{category}', [KbCategoryController::class, 'destroy'])->name('destroy');
        });

        Route::get('/create', [KbArticleController::class, 'create'])
            ->middleware('permission:create articles')->name('create');
        Route::post('/', [KbArticleController::class, 'store'])
            ->middleware('permission:create articles')->name('store');
        Route::get('/{article:slug}', [KbArticleController::class, 'show'])->name('show');
        Route::get('/{article:slug}/edit', [KbArticleController::class, 'edit'])
            ->middleware('permission:edit articles')->name('edit');
        Route::put('/{article:slug}', [KbArticleController::class, 'update'])
            ->middleware('permission:edit articles')->name('update');
        Route::delete('/{article:slug}', [KbArticleController::class, 'destroy'])->name('destroy');
        Route::post('/{article:slug}/publish', [KbArticleController::class, 'publish'])
            ->middleware('permission:publish articles')->name('publish');
        Route::post('/{article:slug}/favorite', [KbArticleController::class, 'toggleFavorite'])->name('favorite');
    });

    // === MODULE RÉUNIONS ===
    Route::prefix('meetings')->name('meetings.')->group(function () {
        Route::get('/', [MeetingController::class, 'index'])->name('index');
        Route::get('/create', [MeetingController::class, 'create'])
            ->middleware('permission:create meetings')->name('create');
        Route::post('/', [MeetingController::class, 'store'])
            ->middleware('permission:create meetings')->name('store');
        Route::get('/join/{token}', [MeetingController::class, 'joinByLink'])->name('join-by-link');
        Route::get('/{meeting}', [MeetingController::class, 'show'])->name('show');
        Route::get('/{meeting}/edit', [MeetingController::class, 'edit'])->name('edit');
        Route::put('/{meeting}', [MeetingController::class, 'update'])->name('update');
        Route::delete('/{meeting}', [MeetingController::class, 'destroy'])->name('destroy');
        Route::post('/{meeting}/respond', [MeetingController::class, 'respond'])->name('respond');
        Route::get('/{meeting}/room', [MeetingController::class, 'room'])
            ->middleware('permission:join meetings')->name('room');
        Route::post('/{meeting}/end', [MeetingController::class, 'end'])->name('end');
    });

    // === ADMINISTRATION ===
    Route::prefix('admin')->name('admin.')->middleware('permission:view users')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/role', [AdminUserController::class, 'updateRole'])
            ->middleware('permission:edit users')->name('users.role');
    });

    // Profil utilisateur (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // === AUTHENTIFICATION À DEUX FACTEURS ===
    Route::prefix('user/two-factor')->name('two-factor.')->group(function () {
        Route::post('/enable', [TwoFactorController::class, 'enable'])->name('enable');
        Route::post('/confirm', [TwoFactorController::class, 'confirm'])->name('confirm');
        Route::delete('/disable', [TwoFactorController::class, 'disable'])->name('disable');
        Route::post('/recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])->name('recovery-codes');
    });
});

// Inclure les routes d'authentification de Breeze
require __DIR__.'/auth.php';