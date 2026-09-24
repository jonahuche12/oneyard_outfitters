<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductSpecificationArtifact extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'product_specification_id',
        'artifact_type',
        'title',
        'description',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'uploaded_by',
        'is_current',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'is_current' => 'boolean',
        ];
    }

    public function productSpecification(): BelongsTo
    {
        return $this->belongsTo(ProductSpecification::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}