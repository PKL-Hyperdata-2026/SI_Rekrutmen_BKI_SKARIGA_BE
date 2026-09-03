<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'category_id',
    'code',
    'name',
    'metadata',
    'sort_order',
    'is_active',
])]
class StandardType extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(StandardTypeCategory::class, 'category_id');
    }

    public function scopeByCategory($query, string $categoryCode)
    {
        return $query->whereHas('category', function ($q) use ($categoryCode) {
            $q->where('code', $categoryCode);
        });
    }
}
