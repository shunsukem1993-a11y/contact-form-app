<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;

class AdminController extends Controller
{
    // お問い合わせ一覧表示
    public function index(IndexContactRequest $request)
    {
        $categories = Category::all();

        $tags = Tag::all();

        $query = Contact::with([
            'category',
            'tags',
        ]);

        // 名前・メール検索
        if ($request->filled('keyword')) {

            $keyword = $request->keyword;

            $query->where(function ($q) use ($keyword) {
                $q->where('first_name', 'like', "%{$keyword}%")
                    ->orWhere('last_name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        // 性別検索
        if ($request->filled('gender') && $request->gender != 0) {
            $query->where('gender', $request->gender);
        }

        // カテゴリー検索
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // 日付検索
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $contacts = $query
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(7);

        return view('admin.index', compact(
            'categories',
            'contacts',
            'tags'
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
