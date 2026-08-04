<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactApiTest extends TestCase
{
    use RefreshDatabase;

    private function validRequestData(): array
    {
        $category = Category::factory()->create();

        $tags = Tag::factory()
            ->count(2)
            ->create();

        return [
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'test@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区1-1-1',
            'building' => '渋谷ビル301',
            'category_id' => $category->id,
            'detail' => 'お問い合わせ内容です。',
            'tag_ids' => $tags->pluck('id')->toArray(),
        ];
    }

    /** @test */
    public function お問い合わせ一覧を取得できる(): void
    {
        Contact::factory()
            ->count(3)
            ->create();

        $response = $this->getJson('/api/v1/contacts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);
    }

    /** @test */
    public function 名前またはメールでお問い合わせを検索できる(): void
    {
        // Arrange
        Contact::factory()->create([
            'first_name' => '山田',
            'last_name' => '太郎',
            'email' => 'yamada@example.com',
        ]);

        Contact::factory()->create([
            'first_name' => '佐藤',
            'last_name' => '花子',
            'email' => 'sato@example.com',
        ]);

        // Act
        $response = $this->getJson(
            '/api/v1/contacts?keyword=山田'
        );

        // Assert
        $response
            ->assertOk()
            ->assertJsonFragment([
                'first_name' => '山田',
            ])
            ->assertJsonMissing([
                'first_name' => '佐藤',
            ]);
    }

    /** @test */
    public function 性別でお問い合わせを検索できる(): void
    {
        // Arrange
        Contact::factory()->create([
            'gender' => 1,
        ]);

        Contact::factory()->create([
            'gender' => 2,
        ]);

        // Act
        $response = $this->getJson('/api/v1/contacts?gender=1');

        // Assert
        $response
            ->assertOk()
            ->assertJsonFragment([
                'gender' => 1,
            ]);
    }

    /** @test */
    public function カテゴリーでお問い合わせを検索できる(): void
    {
        // Arrange
        $category = Category::factory()->create();

        Contact::factory()->create([
            'category_id' => $category->id,
        ]);

        // Act
        $response = $this->getJson(
            "/api/v1/contacts?category_id={$category->id}"
        );

        // Assert
        $response
            ->assertOk();
    }

    /** @test */
    public function 作成日でお問い合わせを検索できる(): void
    {
        // Arrange
        Contact::factory()->create([
            'created_at' => now(),
        ]);

        // Act
        $response = $this->getJson(
            '/api/v1/contacts?date=' . now()->format('Y-m-d')
        );

        // Assert
        $response
            ->assertOk();
    }

    /** @test */
    public function お問い合わせを登録できる(): void
    {
        $request = $this->validRequestData();

        $response = $this->postJson(
            '/api/v1/contacts',
            $request
        );

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data',
            ]);

        $this->assertDatabaseHas('contacts', [
            'first_name' => '山田',
            'email' => 'test@example.com',
        ]);

        $contact = Contact::first();

        $this->assertCount(
            2,
            $contact->tags
        );
    }

    /** @test */
    public function 複数条件（性別、カテゴリー、キーワード）でお問い合わせを検索できる(): void
    {
        // Arrange
        $category = Category::factory()->create([
            'content' => '商品',
        ]);

        Contact::factory()->create([
            'first_name' => '山田',
            'gender' => 1,
            'category_id' => $category->id,
        ]);

        // 検索対象外
        Contact::factory()->create([
            'first_name' => '佐藤',
            'gender' => 2,
            'category_id' => $category->id,
        ]);

        Contact::factory()->create([
            'first_name' => '山田',
            'gender' => 1,
        ]);

        // Act
        $response = $this->getJson(
            "/api/v1/contacts?" . http_build_query([
                'keyword' => '山田',
                'gender' => 1,
                'category_id' => $category->id,
            ])
        );

        // Assert
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'first_name' => '山田',
            ]);
    }

    /** @test */
    public function お問い合わせ詳細を取得できる(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->getJson(
            "/api/v1/contacts/{$contact->id}"
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ]);
    }

    /** @test */
    public function お問い合わせを更新できる(): void
    {
        $contact = Contact::factory()->create();

        $category = Category::factory()->create();

        $tags = Tag::factory()
            ->count(2)
            ->create();

        $request = [
            'first_name' => '佐藤',
            'last_name' => '次郎',
            'gender' => 2,
            'email' => 'update@example.com',
            'tel' => '08012345678',
            'address' => '大阪府大阪市1-1-1',
            'building' => null,
            'category_id' => $category->id,
            'detail' => '更新しました。',
            'tag_ids' => $tags->pluck('id')->toArray(),
        ];

        $response = $this->putJson(
            "/api/v1/contacts/{$contact->id}",
            $request
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
            ]);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'first_name' => '佐藤',
            'email' => 'update@example.com',
        ]);
    }

    /** @test */
    public function お問い合わせを削除できる(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->deleteJson(
            "/api/v1/contacts/{$contact->id}"
        );

        $response->assertStatus(204);

        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id,
        ]);
    }
}
