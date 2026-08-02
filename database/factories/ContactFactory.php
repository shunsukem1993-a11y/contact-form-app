<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
            'category_id' => Category::inRandomOrder()->value('id'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'gender' => fake()->numberBetween(1, 3),
            'email' => fake()->unique()->safeEmail(),
            'tel' => fake()->numerify('090########'),
            'address' => fake()->address(),
            'building' => fake()->optional()->secondaryAddress(),
            'detail' => fake()->randomElement([
                '商品の使い方について教えてください。',
                '注文内容を変更したいです。',
                '配送予定日を確認したいです。',
                '見積もりをお願いしたいです。',
                'サービスについて詳しく知りたいです。',
                '不具合が発生しているため対応をお願いします。',
                '返品・交換の手続きを教えてください。',
            ]),
        ];
    }

    /**
     * 作成日時を指定する
     */
    public function createdAt($date): static
    {
        return $this->state([
            'created_at' => $date,
            'updated_at' => $date,
        ]);
    }
}
