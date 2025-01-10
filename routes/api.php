<?php

use App\Http\Controllers\Auth\CodeCheckController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\MasterData\PostCategoryController;
use App\Http\Controllers\PostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\BaseController;
use App\Http\Controllers\MasterData\PlantRecomendation;
use App\Http\Controllers\MasterData\SeasonController;
use App\Http\Controllers\DiscussesController;
use App\Http\Controllers\PlantDisease\Guest\PlantDisease;
use App\Http\Controllers\PlantDisease\User\AddHistoryController;
use App\Http\Controllers\DiscussesCommentController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::group(['prefix' => 'auth'], function ($route) {
    $route->get('google', [SocialiteController::class, 'redirectToProvider']);
    $route->get('google/callback', [SocialiteController::class, 'handleProviderCallback']);

    $route->post('password/email',  ForgotPasswordController::class);
    $route->post('password/code/check', CodeCheckController::class);
    $route->post('password/reset', ResetPasswordController::class);

    $route->post('register', RegisterController::class);
    $route->post('login', LoginController::class);
    $route->post('logout', LogoutController::class)->middleware('auth:sanctum');
});

Route::group(['prefix' => 'master'], function ($route) {
    $route->apiResource('post-category', PostCategoryController::class)->only(['index', 'show']);
    $route->apiResource('post-category', PostCategoryController::class)->except(['index', 'show'])->middleware('can:action_master');


    $route->get('season/current', [SeasonController::class, 'current_season']);
    $route->apiResource('season', SeasonController::class)->only(['index', 'show']);
    $route->apiResource('season', SeasonController::class)->except(['index', 'show'])->middleware('can:action_master');

    $route->apiResource('plant-recomendations', PlantRecomendation::class)->only(['index', 'show']);
    $route->apiResource('plant-recomendations', PlantRecomendation::class)->except(['index', 'show'])->middleware('can:action_master');
});

Route::middleware('auth:sanctum')->prefix('discusses')->group(function () {
    Route::get('/', [DiscussesController::class, 'index'])->name('discusses.index');
    Route::get('/{id}', [DiscussesController::class, 'show'])->name('discusses.show');
    Route::post('/', [DiscussesController::class, 'store'])->name('discusses.store');
    Route::put('/{id}', [DiscussesController::class, 'update'])->name('discusses.update');
    Route::delete('/{id}', [DiscussesController::class, 'destroy'])->name('discusses.destroy');

    Route::prefix('comment')->group(function () {
        Route::get('/{discuss_id}', [DiscussesCommentController::class, 'index'])->name('discusses.comment.index');
        Route::post('/', [DiscussesCommentController::class, 'create'])->name('discusses.comment.create');
        Route::put('/{id}', [DiscussesCommentController::class, 'update'])->name('discusses.comment.update');
        Route::delete('/{id}', [DiscussesCommentController::class, 'destroy'])->name('discusses.comment.destroy');
    });
});

Route::apiResource('post', PostController::class)->only(['index', 'show']);
Route::apiResource('post', PostController::class)->except(['index', 'show'])->middleware('auth:sanctum');
Route::post('post/comment', [PostController::class, 'comment'])->middleware('auth:sanctum');

Route::group(['prefix' => 'plant-disease'], function ($route) {
    $route->post('guest', PlantDisease::class);
    $route->post('user', AddHistoryController::class)->middleware('auth:sanctum');
});

Route::any('{any}', function () {
    $controller = new BaseController();
    return $controller->sendError('Route not found', 404);
})->where('any', '.*');
