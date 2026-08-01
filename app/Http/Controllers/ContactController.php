<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\View\View;

class ContactController extends Controller
{
    /**
     * お問い合わせ入力画面
     */
    public function index(): View
    {
        $categories = Category::all();
        $tags = Tag::all();

        if (session()->has('contact')) {
            session()->flashInput(session('contact'));
        }

        return view('contact.index', compact('categories', 'tags'));
    }

    /**
     * お問い合わせ確認画面
     */
    public function confirm(StoreContactRequest $request): View
    {
        $validated = $request->validated();

        session()->put('contact', $validated);

        $category = Category::find($validated['category_id']);

        $tags = Tag::whereIn(
            'id',
            $validated['tag_ids'] ?? []
        )->get();

        return view('contact.confirm', compact(
            'validated',
            'category',
            'tags'
        ));
    }

    /**
     * 保存処理
     */
    public function store(StoreContactRequest $request)
    {
        $contactData = $request->validated();

        // contactsテーブル保存
        $contact = Contact::create([
            'first_name' => $contactData['first_name'],
            'last_name' => $contactData['last_name'],
            'gender' => $contactData['gender'],
            'email' => $contactData['email'],
            'tel' => $contactData['tel'],
            'address' => $contactData['address'],
            'building' => $contactData['building'] ?? null,
            'category_id' => $contactData['category_id'],
            'detail' => $contactData['detail'],
        ]);

        // タグ登録
        if (! empty($contactData['tag_ids'])) {
            $contact->tags()->attach(
                $contactData['tag_ids']
            );
        }

        session()->forget('contact');

        return redirect()
            ->route('contacts.thanks');
    }

    /**
     * サンクスページ
     */
    public function thanks(): View
    {
        return view('contact.thanks');
    }
}
