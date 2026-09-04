<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'code',
    'name',
    'description',
])]
class StandardTypeCategory extends Model
{
    use HasFactory, SoftDeletes;

    public function standardTypes(): HasMany
    {
        return $this->hasMany(StandardType::class, 'category_id');
    }
}
