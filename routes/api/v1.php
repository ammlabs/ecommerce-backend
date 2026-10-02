<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Admin\DestroyProductController as AdminDestroyProductController;
use App\Http\Controllers\Api\V1\Admin\ListOrdersController as AdminListOrdersController;
use App\Http\Controllers\Api\V1\Admin\ListProductsController as AdminListProductsController;
use App\Http\Controllers\Api\V1\Admin\ShowProductController as AdminShowProductController;
use App\Http\Controllers\Api\V1\Admin\StoreProductController as AdminStoreProductController;
use App\Http\Controllers\Api\V1\Admin\UpdateOrderStatusController as AdminUpdateOrderStatusController;
use App\Http\Controllers\Api\V1\Admin\UpdateProductController as AdminUpdateProductController;
use App\Http\Controllers\Api\V1\Auth\DeleteAllTokensController;
use App\Http\Controllers\Api\V1\Auth\DeleteTokenController;
use App\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\V1\Auth\ListTokensController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\ResetPasswordController;
use App\Http\Controllers\Api\V1\Auth\SendEmailVerificationNotificationController;
use App\Http\Controllers\Api\V1\Auth\ShowResetPasswordTokenController;
use App\Http\Controllers\Api\V1\Auth\VerifyEmailController;
use App\Http\Controllers\Api\V1\Catalog\ListCategoriesController;
use App\Http\Controllers\Api\V1\Catalog\ListProductsController;
use App\Http\Controllers\Api\V1\Catalog\ShowProductController;
use App\Http\Controllers\Api\V1\Orders\CreateOrderController;
use App\Http\Controllers\Api\V1\Orders\ListOrdersController;
use App\Http\Controllers\Api\V1\Orders\PayOrderController;
use App\Http\Controllers\Api\V1\Orders\ShowOrderController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', RegisterController::class)
    ->middleware(['idempotency', 'throttle:auth-register'])
    ->name('v1.auth.register');
Route::post('/auth/login', LoginController::class)
    ->middleware('throttle:auth-login')
    ->name('v1.auth.login');
Route::post('/auth/password/forgot', ForgotPasswordController::class)
    ->middleware(['idempotency', 'throttle:auth-password'])
    ->name('v1.auth.password.forgot');
Route::post('/auth/password/reset', ResetPasswordController::class)
    ->middleware('throttle:auth-password')
    ->name('v1.auth.password.reset');
Route::get('/auth/password/reset/{token}', ShowResetPasswordTokenController::class)
    ->name('password.reset');
Route::get('/auth/email/verify/{id}/{hash}', VerifyEmailController::class)
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::get('/products', ListProductsController::class)
    ->middleware('throttle:catalog')
    ->name('v1.products.index');
Route::get('/products/{slug}', ShowProductController::class)
    ->middleware('throttle:catalog')
    ->name('v1.products.show');
Route::get('/categories', ListCategoriesController::class)
    ->middleware('throttle:catalog')
    ->name('v1.categories.index');

Route::middleware(['auth:sanctum', 'throttle:auth-protected'])->group(function (): void {
    Route::get('/auth/me', MeController::class)
        ->middleware('abilities:auth:me')
        ->name('v1.auth.me');
    Route::post('/auth/logout', LogoutController::class)
        ->middleware('abilities:auth:logout')
        ->name('v1.auth.logout');
    Route::get('/auth/tokens', ListTokensController::class)
        ->middleware('abilities:auth:tokens:read')
        ->name('v1.auth.tokens.index');
    Route::delete('/auth/tokens', DeleteAllTokensController::class)
        ->middleware('abilities:auth:tokens:delete')
        ->name('v1.auth.tokens.destroy-all');
    Route::delete('/auth/tokens/{token_id}', DeleteTokenController::class)
        ->middleware('abilities:auth:tokens:delete')
        ->name('v1.auth.tokens.destroy');
    Route::post('/auth/email/verification-notification', SendEmailVerificationNotificationController::class)
        ->middleware(['abilities:auth:verification:send', 'throttle:6,1'])
        ->name('v1.auth.email.verification-notification');
    Route::post('/orders', CreateOrderController::class)
        ->middleware(['abilities:orders:write', 'idempotency'])
        ->name('v1.orders.store');
    Route::get('/orders', ListOrdersController::class)
        ->middleware('abilities:orders:read')
        ->name('v1.orders.index');
    Route::get('/orders/{id}', ShowOrderController::class)
        ->middleware('abilities:orders:read')
        ->name('v1.orders.show');
    Route::post('/orders/{id}/pay', PayOrderController::class)
        ->middleware('abilities:orders:write')
        ->name('v1.orders.pay');
});

Route::middleware(['auth:sanctum', 'throttle:auth-protected', 'can:admin'])
    ->prefix('admin')
    ->group(function (): void {
        Route::get('/products', AdminListProductsController::class)->name('v1.admin.products.index');
        Route::post('/products', AdminStoreProductController::class)->name('v1.admin.products.store');
        Route::get('/products/{id}', AdminShowProductController::class)->name('v1.admin.products.show');
        Route::put('/products/{id}', AdminUpdateProductController::class)->name('v1.admin.products.update');
        Route::delete('/products/{id}', AdminDestroyProductController::class)->name('v1.admin.products.destroy');
        Route::get('/orders', AdminListOrdersController::class)->name('v1.admin.orders.index');
        Route::patch('/orders/{id}', AdminUpdateOrderStatusController::class)->name('v1.admin.orders.update');
    });
