<?php

namespace Tests\Unit\Requests\Api\V1;

use App\Http\Requests\Api\V1\StoreContactRequest;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreContactRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validRequestData(): array
    {
        $category = Category::factory()->create();

        return [
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'test@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区1-1-1',
            'building' => '渋谷ビル301',
            'category_id' => $category->id,
            'detail' => '商品の配送について確認したいです。',
            'tag_ids' => [],
        ];
    }

    /** @test */
    public function 正しいお問い合わせデータの場合バリデーション成功する(): void
    {
        // Arrange
        $request = $this->validRequestData();

        // Act
        $validator = Validator::make(
            $request,
            (new StoreContactRequest)->rules()
        );

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function 必須項目が入力されていない場合バリデーションエラーになる(): void
    {
        // Arrange
        $request = [];

        // Act
        $validator = Validator::make(
            $request,
            (new StoreContactRequest)->rules()
        );

        // Assert
        $this->assertTrue($validator->fails());

        $errors = $validator->errors()->toArray();

        $this->assertArrayHasKey('first_name', $errors);
        $this->assertArrayHasKey('last_name', $errors);
        $this->assertArrayHasKey('gender', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('tel', $errors);
        $this->assertArrayHasKey('address', $errors);
        $this->assertArrayHasKey('category_id', $errors);
        $this->assertArrayHasKey('detail', $errors);
    }

    /** @test */
    public function first_nameが256文字の場合バリデーションエラーになる(): void
    {
        // Arrange
        $request = $this->validRequestData();
        $request['first_name'] = str_repeat('あ', 256);

        // Act
        $validator = Validator::make(
            $request,
            (new StoreContactRequest)->rules()
        );

        // Assert
        $this->assertTrue($validator->fails());

        $this->assertArrayHasKey(
            'first_name',
            $validator->errors()->toArray()
        );
    }

    /** @test */
    public function genderが1から3の場合バリデーション成功する(): void
    {
        // Arrange
        $genders = [1, 2, 3];

        // Act / Assert
        foreach ($genders as $gender) {
            $request = $this->validRequestData();

            $request['gender'] = $gender;

            $validator = Validator::make(
                $request,
                (new StoreContactRequest)->rules()
            );

            $this->assertFalse($validator->fails());
        }
    }

    /** @test */
    public function telが10桁または11桁の場合バリデーション成功する(): void
    {
        // Arrange
        $telephoneNumbers = [
            '0123456789',
            '09012345678',
        ];

        // Act / Assert
        foreach ($telephoneNumbers as $telephoneNumber) {
            $request = $this->validRequestData();
            $request['tel'] = $telephoneNumber;

            $validator = Validator::make(
                $request,
                (new StoreContactRequest)->rules()
            );

            $this->assertFalse($validator->fails());
        }
    }

    /** @test */
    public function detailが120文字の場合バリデーション成功する(): void
    {
        // Arrange
        $request = $this->validRequestData();
        $request['detail'] = str_repeat('あ', 120);

        // Act
        $validator = Validator::make(
            $request,
            (new StoreContactRequest)->rules()
        );

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function first_nameが255文字の場合バリデーション成功する(): void
    {
        // Arrange
        $request = $this->validRequestData();
        $request['first_name'] = str_repeat('あ', 255);

        // Act
        $validator = Validator::make(
            $request,
            (new StoreContactRequest)->rules()
        );

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function genderが不正な値の場合バリデーションエラーになる(): void
    {
        // Arrange
        $request = $this->validRequestData();
        $request['gender'] = 4;

        // Act
        $validator = Validator::make(
            $request,
            (new StoreContactRequest)->rules()
        );

        // Assert
        $this->assertTrue($validator->fails());

        $this->assertArrayHasKey(
            'gender',
            $validator->errors()->toArray()
        );
    }

    /** @test */
    public function telがハイフン付きの場合バリデーションエラーになる(): void
    {
        // Arrange
        $request = $this->validRequestData();
        $request['tel'] = '090-1234-5678';

        // Act
        $validator = Validator::make(
            $request,
            (new StoreContactRequest)->rules()
        );

        // Assert
        $this->assertTrue($validator->fails());

        $this->assertArrayHasKey(
            'tel',
            $validator->errors()->toArray()
        );
    }

    /** @test */
    public function telが12桁の場合バリデーションエラーになる(): void
    {
        // Arrange
        $request = $this->validRequestData();
        $request['tel'] = '090123456789';

        // Act
        $validator = Validator::make(
            $request,
            (new StoreContactRequest)->rules()
        );

        // Assert
        $this->assertTrue($validator->fails());

        $this->assertArrayHasKey(
            'tel',
            $validator->errors()->toArray()
        );
    }

    /** @test */
    public function email形式が不正な場合バリデーションエラーになる(): void
    {
        // Arrange
        $request = $this->validRequestData();
        $request['email'] = 'invalid-email';

        // Act
        $validator = Validator::make(
            $request,
            (new StoreContactRequest)->rules()
        );

        // Assert
        $this->assertTrue($validator->fails());

        $this->assertArrayHasKey(
            'email',
            $validator->errors()->toArray()
        );
    }

    /** @test */
    public function buildingが255文字の場合バリデーション成功する(): void
    {
        // Arrange
        $request = $this->validRequestData();
        $request['building'] = str_repeat('あ', 255);

        // Act
        $validator = Validator::make(
            $request,
            (new StoreContactRequest)->rules()
        );

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function buildingが256文字の場合バリデーションエラーになる(): void
    {
        // Arrange
        $request = $this->validRequestData();
        $request['building'] = str_repeat('あ', 256);

        // Act
        $validator = Validator::make(
            $request,
            (new StoreContactRequest)->rules()
        );

        // Assert
        $this->assertTrue($validator->fails());

        $this->assertArrayHasKey(
            'building',
            $validator->errors()->toArray()
        );
    }

    /** @test */
    public function category_idが存在しない場合バリデーションエラーになる(): void
    {
        // Arrange
        $request = $this->validRequestData();
        $request['category_id'] = 999;

        // Act
        $validator = Validator::make(
            $request,
            (new StoreContactRequest)->rules()
        );

        // Assert
        $this->assertTrue($validator->fails());

        $this->assertArrayHasKey(
            'category_id',
            $validator->errors()->toArray()
        );
    }

    /** @test */
    public function tag_idsに存在するタグ_i_dが指定された場合バリデーション成功する(): void
    {
        // Arrange
        $tags = Tag::factory()->count(2)->create();

        $request = $this->validRequestData();
        $request['tag_ids'] = $tags->pluck('id')->toArray();

        // Act
        $validator = Validator::make(
            $request,
            (new StoreContactRequest)->rules()
        );

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function tag_idsに存在しないタグ_i_dが含まれる場合バリデーションエラーになる(): void
    {
        // Arrange
        $request = $this->validRequestData();
        $request['tag_ids'] = [999];

        // Act
        $validator = Validator::make(
            $request,
            (new StoreContactRequest)->rules()
        );

        // Assert
        $this->assertTrue($validator->fails());

        $this->assertArrayHasKey(
            'tag_ids.0',
            $validator->errors()->toArray()
        );
    }

    /** @test */
    public function detailが121文字の場合バリデーションエラーになる(): void
    {
        // Arrange
        $request = $this->validRequestData();
        $request['detail'] = str_repeat('あ', 121);

        // Act
        $validator = Validator::make(
            $request,
            (new StoreContactRequest)->rules()
        );

        // Assert
        $this->assertTrue($validator->fails());

        $this->assertArrayHasKey(
            'detail',
            $validator->errors()->toArray()
        );
    }
}
