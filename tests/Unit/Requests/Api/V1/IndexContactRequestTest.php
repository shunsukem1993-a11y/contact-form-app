<?php

namespace Tests\Unit\Requests\Api\V1;

use App\Http\Requests\Api\V1\IndexContactRequest;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class IndexContactRequestTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 検索パラメータが正しい場合バリデーション成功する(): void
    {
        // Arrange
        $category = Category::factory()->create();

        $request = [
            'keyword' => '田中',
            'gender' => 1,
            'category_id' => $category->id,
            'date' => '2026-08-01',
            'per_page' => 20,
            'page' => 1,
        ];

        // Act
        $validator = Validator::make(
            $request,
            (new IndexContactRequest)->rules()
        );

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function genderが1から3の場合バリデーション成功する(): void
    {
        // Arrange
        $genders = [1, 2, 3];

        // Act / Assert
        foreach ($genders as $gender) {
            $validator = Validator::make(
                [
                    'gender' => $gender,
                ],
                (new IndexContactRequest)->rules()
            );

            $this->assertFalse($validator->fails());
        }
    }

    /** @test */
    public function keywordが255文字の場合バリデーション成功する(): void
    {
        // Arrange
        $request = [
            'keyword' => str_repeat('a', 255),
        ];

        // Act
        $validator = Validator::make(
            $request,
            (new IndexContactRequest)->rules()
        );

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function per_pageが1の場合バリデーション成功する(): void
    {
        // Arrange
        $request = [
            'per_page' => 1,
        ];

        // Act
        $validator = Validator::make(
            $request,
            (new IndexContactRequest)->rules()
        );

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function per_pageが100の場合バリデーション成功する(): void
    {
        // Arrange
        $request = [
            'per_page' => 100,
        ];

        // Act
        $validator = Validator::make(
            $request,
            (new IndexContactRequest)->rules()
        );

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function genderが不正な値の場合バリデーションエラーになる(): void
    {
        // Arrange
        $request = [
            'gender' => 4,
        ];

        // Act
        $validator = Validator::make(
            $request,
            (new IndexContactRequest)->rules()
        );

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(
            'gender',
            $validator->errors()->toArray()
        );
    }

    /** @test */
    public function 存在しないcategory_idの場合バリデーションエラーになる(): void
    {
        // Arrange
        $request = [
            'category_id' => 999,
        ];

        // Act
        $validator = Validator::make(
            $request,
            (new IndexContactRequest)->rules()
        );

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(
            'category_id',
            $validator->errors()->toArray()
        );
    }

    /** @test */
    public function keywordが256文字の場合バリデーションエラーになる(): void
    {
        // Arrange
        $request = [
            'keyword' => str_repeat('a', 256),
        ];

        // Act
        $validator = Validator::make(
            $request,
            (new IndexContactRequest)->rules()
        );

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(
            'keyword',
            $validator->errors()->toArray()
        );
    }

    /** @test */
    public function dateが不正な形式の場合バリデーションエラーになる(): void
    {
        // Arrange
        $request = [
            'date' => 'test',
        ];

        // Act
        $validator = Validator::make(
            $request,
            (new IndexContactRequest)->rules()
        );

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(
            'date',
            $validator->errors()->toArray()
        );
    }

    /** @test */
    public function per_pageが101の場合バリデーションエラーになる(): void
    {
        // Arrange
        $request = [
            'per_page' => 101,
        ];

        // Act
        $validator = Validator::make(
            $request,
            (new IndexContactRequest)->rules()
        );

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(
            'per_page',
            $validator->errors()->toArray()
        );
    }

    /** @test */
    public function pageが0の場合バリデーションエラーになる(): void
    {
        // Arrange
        $request = [
            'page' => 0,
        ];

        // Act
        $validator = Validator::make(
            $request,
            (new IndexContactRequest)->rules()
        );

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(
            'page',
            $validator->errors()->toArray()
        );
    }
}
