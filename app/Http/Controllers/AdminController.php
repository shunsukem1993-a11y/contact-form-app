<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Contact;

class AdminController extends Controller
{
    // お問い合わせ一覧表示
    public function index()
    {
        $categories = Category::all();

        $contacts = Contact::with([
            'category',
            'tags',
        ])
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(7);

        return view('admin.index', compact(
            'categories',
            'contacts'
        ));
    }

    // お問い合わせ詳細表示
    public function show(Contact $contact)
    {
        $contact->load([
            'category',
            'tags',
        ]);

        return view('admin.show', compact('contact'));
    }

    // お問い合わせ削除
    public function destroy(Contact $contact)
    {
        $contact->delete();

        return redirect()
            ->route('admin.index')
            ->with('success', 'お問い合わせを削除しました。');
    }
}
