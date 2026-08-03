<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportContactRequest;
use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    /**
     * CSVエクスポート
     */
    public function export(ExportContactRequest $request): StreamedResponse
    {
        $query = Contact::with('category');

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
        if ($request->has('gender')) {
            $query->where('gender', $request->gender);
        }

        // カテゴリ検索
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // 日付検索
        if ($request->filled('date')) {

            $query->whereDate(
                'created_at',
                $request->date
            );

        }

        $contacts = $query
            ->latest()
            ->get();

        return response()->streamDownload(function () use ($contacts) {

            $handle = fopen('php://output', 'w');

            // BOM
            fwrite($handle, "\xEF\xBB\xBF");

            // header
            fputcsv($handle, [
                'ID',
                '氏名',
                '性別',
                'メール',
                '電話',
                '住所',
                '建物',
                'カテゴリ',
                '内容',
                '作成日時',
            ]);

            foreach ($contacts as $contact) {

                fputcsv($handle, [
                    $contact->id,

                    $contact->last_name.
                    $contact->first_name,

                    match ($contact->gender) {
                        1 => '男性',
                        2 => '女性',
                        3 => 'その他',
                        default => '',
                    },

                    $contact->email,

                    $contact->tel,

                    $contact->address,

                    $contact->building,

                    $contact->category->content,

                    $contact->detail,

                    $contact->created_at,
                ]);

            }

            fclose($handle);

        }, 'contacts.csv');
    }
}
