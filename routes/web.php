<?php

use Illuminate\Support\Facades\Route;
use App\Models\Category;
use App\Models\Contact;

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

// トップページはログイン画面へ
Route::get('/', function () {
    return redirect()->route('login');
});

// 仮ルート（管理画面実装時に置き換え）
Route::middleware('auth')->group(function () {

    // 管理画面（仮）
    Route::get('/admin', function () {

        $categories = Category::all();
        $contacts = Contact::with([
            'category',
            'tags',
        ])->paginate(7);

        return view('admin.index', compact('categories', 'contacts'));
    })->name('admin.index');

});