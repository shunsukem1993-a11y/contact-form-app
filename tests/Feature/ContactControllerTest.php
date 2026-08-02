<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function お問い合わせ入力画面が表示される(): void
    {
        // Arrange
        $categories = Category::factory()->count(3)->create();
        $tags = Tag::factory()->count(3)->create();

        // Act
        $response = $this->get(route('contacts.index'));

        // Assert
        $response->assertStatus(200);

        // ビュー変数
        $response->assertViewHas('categories');
        $response->assertViewHas('tags');

        // カテゴリー名が表示される
        foreach ($categories as $category) {
            $response->assertSee($category->content);
        }

        // タグ名が表示される
        foreach ($tags as $tag) {
            $response->assertSee($tag->name);
        }
    }

    /** @test */
    public function お問い合わせ確認画面が表示される(): void
    {
        // Arrange
        $category = Category::factory()->create();

        $tags = Tag::factory()->count(2)->create();

        $formData = [
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'test@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区',
            'building' => 'テストビル',
            'category_id' => $category->id,
            'tag_ids' => $tags->pluck('id')->toArray(),
            'detail' => 'テストお問い合わせ',
        ];

        // Act
        $response = $this->post(route('contacts.confirm'), $formData);

        // Assert
        $response->assertStatus(200);

        // 確認画面が表示される
        $response->assertViewIs('contact.confirm');

        // 入力内容が表示される
        $response->assertSee('山田');
        $response->assertSee('太郎');
        $response->assertSee('男性');
        $response->assertSee('test@example.com');
        $response->assertSee('09012345678');
        $response->assertSee('東京都渋谷区');
        $response->assertSee('テストビル');
        $response->assertSee('テストお問い合わせ');

        // カテゴリー名が表示される
        $response->assertSee($category->content);

        // タグ名が表示される
        foreach ($tags as $tag) {
            $response->assertSee($tag->name);
        }

        // 入力内容がセッションに保持される
        $response->assertSessionHas('contact');

        $this->assertEquals(
            $formData,
            session('contact')
        );
    }

    /** @test */
    public function セッションの入力内容が入力画面へ引き継がれる(): void
    {
        // Arrange
        session([
            'contact' => [
                'first_name' => '太郎',
                'last_name' => '山田',
                'email' => 'test@example.com',
                'detail' => 'セッション引き継ぎテスト',
            ],
        ]);

        // Act
        $response = $this->get(route('contacts.index'));

        // Assert
        $response->assertStatus(200);

        // old入力値として引き継がれていることを確認
        $response->assertSessionHasInput('first_name', '太郎');
        $response->assertSessionHasInput('last_name', '山田');
        $response->assertSessionHasInput('email', 'test@example.com');
        $response->assertSessionHasInput('detail', 'セッション引き継ぎテスト');
    }

    /** @test */
    public function お問い合わせを登録できる(): void
    {
        // Arrange
        $category = Category::factory()->create();

        $tags = Tag::factory()->count(2)->create();

        $formData = [
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'test@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区',
            'building' => 'テストビル',
            'category_id' => $category->id,
            'tag_ids' => $tags->pluck('id')->toArray(),
            'detail' => 'テストお問い合わせ',
        ];

        // Act
        $response = $this->post(route('contacts.store'), $formData);

        // Assert
        // /thanksへリダイレクト
        $response->assertRedirect(route('contacts.thanks'));

        // contactsテーブルへ保存
        $this->assertDatabaseHas('contacts', [
            'first_name' => '太郎',
            'last_name' => '山田',
            'email' => 'test@example.com',
            'category_id' => $category->id,
        ]);

        // 保存されたContact取得
        $contact = Contact::firstOrFail();

        // contact_tagへ保存（複数タグ）
        foreach ($tags as $tag) {
            $this->assertDatabaseHas('contact_tag', [
                'contact_id' => $contact->id,
                'tag_id' => $tag->id,
            ]);
        }
    }

    /** @test */
    public function タグ未選択でもお問い合わせを登録できる(): void
    {
        // Arrange
        $category = Category::factory()->create();

        $formData = [
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'test@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区',
            'building' => 'テストビル',
            'category_id' => $category->id,
            'detail' => 'タグなしお問い合わせ',
        ];

        // Act
        $response = $this->post(route('contacts.store'), $formData);

        // Assert
        $response->assertRedirect(route('contacts.thanks'));

        // Contact保存確認
        $this->assertDatabaseHas('contacts', [
            'first_name' => '太郎',
            'last_name' => '山田',
            'email' => 'test@example.com',
            'category_id' => $category->id,
        ]);

        // タグなしなので中間テーブルに保存されないことを確認
        $this->assertDatabaseCount('contact_tag', 0);
    }

    /** @test */
    public function サンクスページが表示される(): void
    {
        // Act
        $response = $this->get(route('contacts.thanks'));

        // Assert
        $response->assertStatus(200);

        $response->assertViewIs('contact.thanks');
    }
}
