<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\LoginController as AdminLoginController;
use App\Http\Controllers\Frontend\CartController;
use App\Http\Controllers\Frontend\CategoryController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\LoginController as FrontendLoginController;
use App\Http\Controllers\Frontend\PageController;
use App\Http\Controllers\Frontend\ProductController;
use App\Http\Controllers\Frontend\RegisterController;

/*
|--------------------------------------------------------------------------
| Backend entry point
|--------------------------------------------------------------------------
|
| The customer storefront is served by the separate React application on
| port 5173. The Laravel application is the admin backend, so its root URL
| must never open the legacy storefront view.
|
*/
Route::get('/', fn () => redirect()->route('admin.login'))->name('backend.home');

/* Legacy storefront routes kept available for existing links. */

Route::get('/storefront', [HomeController::class, 'index'])->name('home');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add/{product}', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/buy-now/{product}', [CartController::class, 'buyNow'])->name('cart.buyNow');
Route::post('/cart/remove/{product}', [CartController::class, 'remove'])->name('cart.remove');
Route::get('/category/{slug}', [CategoryController::class, 'show'])->name('categories.show');
Route::get('/page/{slug}', [PageController::class, 'show'])->name('pages.show');

Route::get('/media/{path}', function (string $path) {
    $relativePath = ltrim($path, '/');
    if (str_contains($relativePath, '..')) {
        abort(404);
    }

    $candidates = [
        storage_path('app/public/' . $relativePath),
        storage_path('app/public/farm-gallery/' . $relativePath),
        storage_path('app/public/farm-gallery/prakgallery/' . basename($relativePath)),
        storage_path('app/public/food-awareness/' . $relativePath),
        storage_path('app/public/food-awareness/' . basename($relativePath)),
        base_path('../frontend/src/assets/images/' . $relativePath),
        base_path('../frontend/src/assets/images/' . basename($relativePath)),
        base_path('../frontend/src/assets/images/Final packaging/' . $relativePath),
        base_path('../frontend/src/assets/images/Final packaging/' . basename($relativePath)),
        base_path('../frontend/src/assets/images/PRAKGALLERY/' . basename($relativePath)),
        base_path('../frontend/src/assets/videos_ghee/' . basename($relativePath)),
        public_path($relativePath),
        public_path('assets/' . basename($relativePath)),
    ];

    foreach ($candidates as $candidate) {
        $resolved = realpath($candidate);
        if ($resolved && is_file($resolved)) {
            return response()->file($resolved, [
                'Access-Control-Allow-Origin' => '*',
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }
    }

    abort(404);
})->where('path', '.*')->name('media.file');

/*
|--------------------------------------------------------------------------
| Removed Storefront Routes
|--------------------------------------------------------------------------
|
| This Laravel app now serves the backend/admin interface only.
|
*/

Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register'])->name('register.post');
Route::get('/login', [FrontendLoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [FrontendLoginController::class, 'login'])->name('login.post');
Route::get('/my-account', fn () => view('frontend.account'))->name('account');
Route::post('/logout', [FrontendLoginController::class, 'logout'])->name('logout');

Route::get('/preview-family-pack-email', function () {
    $items = [
        [
            'name' => 'Organic Bansi Whole Wheat Atta',
            'package_size' => '10 kg (Vacuum Packed)',
            'qty' => 1,
            'price' => 740,
            'label' => 'Stone-Milled · 0% Maida · Eco-Fresh Sealed',
        ],
        [
            'name' => 'A2 Vedic Desi Gir Cow Cultured Bilona Ghee',
            'package_size' => '1 Litre (Glass Jar)',
            'qty' => 2,
            'price' => 1650,
            'label' => 'Traditional Curd Churned · High OPO Fats',
        ],
        [
            'name' => 'Natural Unpolished Toor & Moong Dal Basket',
            'package_size' => '3 kg (Vacuum Pouch)',
            'qty' => 1,
            'price' => 520,
            'label' => 'Zero Chemical Polish · Batch Cleaned',
        ],
        [
            'name' => 'Wood Cold-Pressed Groundnut & Mustard Oil Duo',
            'package_size' => '3 Litres (Food Grade)',
            'qty' => 1,
            'price' => 870,
            'label' => 'Unrefined Kolhu Pressed · Rich in MUFA',
        ],
        [
            'name' => 'Natural Chemical-Free Organic Jaggery Powder',
            'package_size' => '1 kg (Eco Pouch)',
            'qty' => 1,
            'price' => 130,
            'label' => 'Zero Added Sugar · Iron Rich',
        ],
        [
            'name' => 'Natural Himalayan Pink Rock Salt',
            'package_size' => '1 kg',
            'qty' => 1,
            'price' => 55,
            'label' => 'Low Sodium · Trace Minerals',
        ],
    ];

    $monthlyTotal = 0;
    foreach ($items as $item) {
        $monthlyTotal += ($item['price'] * $item['qty']);
    }

    $recipientName = 'Dhruv Parekh';
    $orderDate = now()->subDays(27)->format('d M Y');
    $reminderDate = now()->format('d M Y');
    $renewalDate = now()->addDays(3)->format('d M Y');
    $reorderUrl = 'http://localhost:5173/family-pack?reorder=dhruvparekh605';
    $whatsappText = "Namaste Prakruti! I would like to refill my 30-Day Family Ration Basket (Est. Total: ₹"
        . number_format($monthlyTotal, 2)
        . "). Please confirm my previous monthly basket items.";
    $whatsappUrl = 'https://wa.me/919876543210?text=' . urlencode($whatsappText);

    return view('emails.family_pack_reminder', [
        'recipientName' => $recipientName,
        'items'         => $items,
        'monthlyTotal'  => $monthlyTotal,
        'orderDate'     => $orderDate,
        'reminderDate'  => $reminderDate,
        'renewalDate'   => $renewalDate,
        'reorderUrl'    => $reorderUrl,
        'whatsappUrl'   => $whatsappUrl,
    ]);
});

/*
|--------------------------------------------------------------------------
| Admin Panel Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')
    ->name('admin.')
    ->group(base_path('routes/admin.php'));

