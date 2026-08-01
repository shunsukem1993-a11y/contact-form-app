<?php

use App\Http\Controllers\ContactController;
use App\Models\Category;
use App\Models\Contact;
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

// お問い合わせ
Route::get('/', [ContactController::class, 'index'])
    ->name('contacts.index');

Route::post('/contacts/confirm', [ContactController::class, 'confirm'])
    ->name('contacts.confirm');

Route::post('/contacts', [ContactController::class, 'store'])
    ->name('contacts.store');

Route::get('/thanks', [ContactController::class, 'thanks'])
    ->name('contacts.thanks');

// 管理画面（仮）
Route::middleware('auth')->group(function () {

    Route::get('/admin', function () {
        $categories = Category::all();
        $contacts = Contact::paginate(7);

        return view('admin.index', compact('categories', 'contacts'));
    })->name('admin.index');

});
