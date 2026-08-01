<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoryIds = Category::pluck('id');
        $tags = Tag::all();

        // お問い合わせデータ20件作成
        Contact::factory()
            ->count(20)
            ->make()
            ->each(function ($contact) use ($categoryIds, $tags) {

                // 既存カテゴリからランダムに設定
                $contact->category_id = $categoryIds->random();
                $contact->save();

                // タグを1〜3件ランダム付与
                $contact->tags()->attach(
                    $tags->random(rand(1, 3))->pluck('id')->all()
                );
            });
    }
}
