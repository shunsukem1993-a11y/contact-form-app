<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    /**
     * 一括代入を許可する属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'content',
    ];

    /**
     * カテゴリに属するお問い合わせ一覧
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }
}
