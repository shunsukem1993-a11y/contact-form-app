<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * テスト用データ
     */
    private function validData(): array
    {
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();

        return [
            'first_name' => '太郎',
            'last_name' => '山田',
            'gender' => 1,
            'email' => 'test@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区',
            'building' => 'テストビル',
            'category_id' => $category->id,
            'tag_ids' => [$tag->id],
            'detail' => 'テストお問い合わせ',
        ];
    }

    /** @test */
    public function 姓が未入力の場合バリデーションエラーになる(): void
    {
        $data = $this->validData();
        $data['last_name'] = '';

        $response = $this->post(route('contacts.confirm'), $data);

        $response->assertSessionHasErrors('last_name');
    }

    /** @test */
    public function 名が未入力の場合バリデーションエラーになる(): void
    {
        $data = $this->validData();
        $data['first_name'] = '';

        $response = $this->post(route('contacts.confirm'), $data);

        $response->assertSessionHasErrors('first_name');
    }

    /** @test */
    public function 性別が未選択の場合バリデーションエラーになる(): void
    {
        $data = $this->validData();
        $data['gender'] = '';

        $response = $this->post(route('contacts.confirm'), $data);

        $response->assertSessionHasErrors('gender');
    }

    /** @test */
    public function メールアドレスが未入力の場合バリデーションエラーになる(): void
    {
        $data = $this->validData();
        $data['email'] = '';

        $response = $this->post(route('contacts.confirm'), $data);

        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function メールアドレス形式が不正の場合バリデーションエラーになる(): void
    {
        $data = $this->validData();
        $data['email'] = 'test';

        $response = $this->post(route('contacts.confirm'), $data);

        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function 電話番号が数字以外の場合バリデーションエラーになる(): void
    {
        $data = $this->validData();
        $data['tel'] = '090-1234-abcd';

        $response = $this->post(route('contacts.confirm'), $data);

        $response->assertSessionHasErrors('tel');
    }

    /** @test */
    public function 電話番号が10文字未満の場合バリデーションエラーになる(): void
    {
        $data = $this->validData();
        $data['tel'] = '090123456';

        $response = $this->post(route('contacts.confirm'), $data);

        $response->assertSessionHasErrors('tel');
    }

    /** @test */
    public function 電話番号が12文字以上の場合バリデーションエラーになる(): void
    {
        $data = $this->validData();
        $data['tel'] = '090123456789';

        $response = $this->post(route('contacts.confirm'), $data);

        $response->assertSessionHasErrors('tel');
    }

    /** @test */
    public function 住所が未入力の場合バリデーションエラーになる(): void
    {
        $data = $this->validData();
        $data['address'] = '';

        $response = $this->post(route('contacts.confirm'), $data);

        $response->assertSessionHasErrors('address');
    }

    /** @test */
    public function 建物名が未入力でも送信できる(): void
    {
        $data = $this->validData();
        $data['building'] = '';

        $response = $this->post(route('contacts.confirm'), $data);

        $response->assertSessionDoesntHaveErrors();
        $response->assertStatus(200);
    }

    /** @test */
    public function カテゴリーが未選択の場合バリデーションエラーになる(): void
    {
        $data = $this->validData();
        $data['category_id'] = '';

        $response = $this->post(route('contacts.confirm'), $data);

        $response->assertSessionHasErrors('category_id');
    }

    /** @test */
    public function 存在しないカテゴリー_i_dの場合バリデーションエラーになる(): void
    {
        $data = $this->validData();
        $data['category_id'] = 9999;

        $response = $this->post(route('contacts.confirm'), $data);

        $response->assertSessionHasErrors('category_id');
    }

    /** @test */
    public function お問い合わせ内容が120文字以内の場合送信できる(): void
    {
        $data = $this->validData();
        $data['detail'] = str_repeat('a', 120);

        $response = $this->post(route('contacts.confirm'), $data);

        $response->assertSessionDoesntHaveErrors();
        $response->assertStatus(200);
    }

    /** @test */
    public function お問い合わせ内容が121文字以上の場合バリデーションエラーになる(): void
    {
        $data = $this->validData();
        $data['detail'] = str_repeat('a', 121);

        $response = $this->post(route('contacts.confirm'), $data);

        $response->assertSessionHasErrors('detail');
    }

    /** @test */
    public function 存在しないタグ_i_dの場合バリデーションエラーになる(): void
    {
        $data = $this->validData();
        $data['tag_ids'] = [9999];

        $response = $this->post(route('contacts.confirm'), $data);

        $response->assertSessionHasErrors('tag_ids.0');
    }
}
