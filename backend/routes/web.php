<?php

use App\Http\Controllers\Api\V1\Public\SitemapController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

/*
| Phase 4.5c sitemap-fix — root-path sitemap. Search engines crawl
| /sitemap.xml by default (Google checks root first), so this route
| must live at the application root, not under /api/v1. It used to
| be registered in routes/api.php at /api/v1/sitemap.xml — that
| location returned a valid response but was unreachable to crawlers.
|
| Controller is the existing Phase 4.5b SitemapController in the
| Api\V1\Public namespace; only the route binding moved. The
| response stays application/xml with a 1-hour Cache-Control TTL.
*/
Route::get('/sitemap.xml', [SitemapController::class, 'index'])
    ->name('sitemap');
