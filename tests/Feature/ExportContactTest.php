<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportContactTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ログインユーザー
     */
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /** @test */
    public function csvをダウンロードできる(): void
    {
        Contact::factory()->count(3)->create();

        $response = $this
            ->actingAs($this->user)
            ->get(route('contacts.export'));

        $response->assertOk();

        $response->assertHeader(
            'content-disposition',
            'attachment; filename=contacts.csv'
        );
    }

    /** @test */
    public function 検索条件付きでcsvをダウンロードできる(): void
    {
        $category = Category::factory()->create();

        Contact::factory()->create([
            'category_id' => $category->id,
            'first_name' => '太郎',
            'gender' => 1,
            'created_at' => '2026-08-03',
        ]);

        Contact::factory()->create([
            'first_name' => '花子',
            'gender' => 2,
        ]);

        $response = $this
            ->actingAs($this->user)
            ->get(route('contacts.export', [
                'keyword' => '太郎',
                'gender' => 1,
                'category_id' => $category->id,
                'date' => '2026-08-03',
            ]));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('太郎', $content);
        $this->assertStringNotContainsString('花子', $content);
    }

    /** @test */
    public function フィルタ未指定で全件を新着順にエクスポートできる(): void
    {
        Contact::factory()
            ->createdAt(now()->subDay())
            ->create([
                'first_name' => '古い',
            ]);

        Contact::factory()
            ->createdAt(now())
            ->create([
                'first_name' => '新しい',
            ]);

        $response = $this
            ->actingAs($this->user)
            ->get(route('contacts.export'));

        $content = $response->streamedContent();

        $this->assertTrue(
            strpos($content, '新しい')
            <
            strpos($content, '古い')
        );
    }

    /** @test */
    public function csvフォーマットが仕様通りで出力される(): void
    {
        $category = Category::factory()->create([
            'content' => '商品トラブル',
        ]);

        Contact::factory()->create([
            'category_id' => $category->id,
            'gender' => 1,
        ]);

        Contact::factory()->create([
            'category_id' => $category->id,
            'gender' => 2,
        ]);

        Contact::factory()->create([
            'category_id' => $category->id,
            'gender' => 3,
        ]);

        $response = $this
            ->actingAs($this->user)
            ->get(route('contacts.export'));

        $content = $response->streamedContent();

        // BOM
        $this->assertStringStartsWith(
            "\xEF\xBB\xBF",
            $content
        );

        // ヘッダー
        $this->assertStringContainsString(
            'ID,氏名,性別,メール,電話,住所,建物,カテゴリ,内容,作成日時',
            $content
        );

        // 性別
        $this->assertStringContainsString(
            '男性',
            $content
        );

        $this->assertStringContainsString(
            '女性',
            $content
        );

        $this->assertStringContainsString(
            'その他',
            $content
        );

        // カテゴリ
        $this->assertStringContainsString(
            '商品トラブル',
            $content
        );
    }

    /** @test */
    public function 正しい検索条件でcsvを出力できる(): void
    {
        $category = Category::factory()->create();

        $response = $this
            ->actingAs($this->user)
            ->get(route('contacts.export', [
                'keyword' => 'test',
                'gender' => 1,
                'category_id' => $category->id,
                'date' => now()->toDateString(),
            ]));

        $response->assertOk();
    }

    /** @test */
    public function 不正な性別ではバリデーションエラーになる(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->get(route('contacts.export', [
                'gender' => 4,
            ]));

        $response->assertSessionHasErrors('gender');
    }

    /** @test */
    public function 存在しないカテゴリ_i_dではバリデーションエラーになる(): void
    {
        $response = $this
            ->actingAs($this->user)
            ->get(route('contacts.export', [
                'category_id' => 9999,
            ]));

        $response->assertSessionHasErrors('category_id');
    }
}
