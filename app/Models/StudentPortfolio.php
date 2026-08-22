<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentPortfolio extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_alumni_id',
        'category_id',
        'title',
        'description',
        'file_path',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    // Relasi: Satu student memiliki banyak portfolio
    public function studentAlumni(): BelongsTo
    {
        return $this->belongsTo(StudentAlumni::class, 'student_alumni_id');
    }

    // Relasi: Satu portfolio termasuk satu jenis type
    public function category(): BelongsTo
    {
        return $this->belongsTo(StandardType::class, 'category_id')
            ->byCategory('portfolio_type');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
