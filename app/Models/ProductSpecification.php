<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductSpecification extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'contact_id',
        'created_by',
        'specification_date',
        'item_name',
        'product_type',
        'description',
        'unit',
        'unit_price',
        'price_updated_at',
        'material',
        'material_details',
        'design_details',
        'size_details',
        'branding_details',
        'quality_requirements',
        'special_instructions',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'specification_date' => 'date',
            'unit_price' => 'decimal:2',
            'price_updated_at' => 'date',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function artifacts(): HasMany
    {
        return $this->hasMany(ProductSpecificationArtifact::class);
    }
}