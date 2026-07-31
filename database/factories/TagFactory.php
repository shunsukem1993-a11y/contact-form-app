<?php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                '質問',
                '要望',
                '不具合報告',
                'ご意見',
                'その他',
            ]),
        ];
    }
}