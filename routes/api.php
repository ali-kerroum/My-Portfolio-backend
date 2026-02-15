<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ExperienceController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\SkillController;
use App\Http\Controllers\Api\ContactLinkController;
use App\Http\Controllers\Api\PageViewController;
use App\Http\Controllers\Api\ContactMessageController;
use App\Http\Controllers\Api\SettingController;

/*
|--------------------------------------------------------------------------
| Public API Routes (read-only for the portfolio frontend)
|--------------------------------------------------------------------------
*/
Route::get('/projects', [ProjectController::class, 'index']);
Route::get('/projects/{project}', [ProjectController::class, 'show']);
Route::get('/experiences', [ExperienceController::class, 'index']);
Route::get('/experiences/{experience}', [ExperienceController::class, 'show']);
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/skills', [SkillController::class, 'index']);
Route::get('/contact-links', [ContactLinkController::class, 'index']);

// Public: track page views & submit contact messages
Route::post('/page-views', [PageViewController::class, 'store']);
Route::post('/contact-messages', [ContactMessageController::class, 'store']);

// Public: get visible sections for the portfolio
Route::get('/settings/visible-sections', [SettingController::class, 'visibleSections']);

// Public: get hero content
Route::get('/settings/hero', [SettingController::class, 'heroContent']);

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Protected Admin Routes (CRUD operations)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Projects CRUD
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::put('/projects/{project}', [ProjectController::class, 'update']);
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);
    Route::post('/projects/reorder', [ProjectController::class, 'reorder']);
    Route::post('/upload-file', [ProjectController::class, 'uploadFile']);

    // Experiences CRUD
    Route::post('/experiences', [ExperienceController::class, 'store']);
    Route::put('/experiences/{experience}', [ExperienceController::class, 'update']);
    Route::delete('/experiences/{experience}', [ExperienceController::class, 'destroy']);
    Route::post('/experiences/reorder', [ExperienceController::class, 'reorder']);

    // Services CRUD
    Route::post('/services', [ServiceController::class, 'store']);
    Route::put('/services/{service}', [ServiceController::class, 'update']);
    Route::delete('/services/{service}', [ServiceController::class, 'destroy']);
    Route::post('/services/reorder', [ServiceController::class, 'reorder']);

    // Skills CRUD
    Route::post('/skills', [SkillController::class, 'store']);
    Route::put('/skills/{skill}', [SkillController::class, 'update']);
    Route::delete('/skills/{skill}', [SkillController::class, 'destroy']);
    Route::post('/skills/reorder', [SkillController::class, 'reorder']);

    // Contact Links CRUD
    Route::post('/contact-links', [ContactLinkController::class, 'store']);
    Route::put('/contact-links/{contactLink}', [ContactLinkController::class, 'update']);
    Route::delete('/contact-links/{contactLink}', [ContactLinkController::class, 'destroy']);
    Route::post('/contact-links/reorder', [ContactLinkController::class, 'reorder']);

    // Page views stats (admin)
    Route::get('/page-views/stats', [PageViewController::class, 'stats']);

    // Contact messages (admin)
    Route::get('/contact-messages', [ContactMessageController::class, 'index']);
    Route::get('/contact-messages/unread-count', [ContactMessageController::class, 'unreadCount']);
    Route::put('/contact-messages/{contactMessage}/read', [ContactMessageController::class, 'markRead']);
    Route::delete('/contact-messages/{contactMessage}', [ContactMessageController::class, 'destroy']);

    // Settings (admin)
    Route::get('/settings/sections', [SettingController::class, 'sections']);
    Route::put('/settings/sections', [SettingController::class, 'updateSections']);
    Route::put('/settings/hero', [SettingController::class, 'updateHeroContent']);
    Route::post('/settings/hero/upload-image', [SettingController::class, 'uploadHeroImage']);
});
