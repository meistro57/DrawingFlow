<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'zip',
        'country',
        'notes',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    // Relationships

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function drawingRequests(): HasMany
    {
        return $this->hasMany(DrawingRequest::class);
    }

    public function submittals(): HasMany
    {
        return $this->hasMany(DrawingSubmittal::class);
    }

    public function workflow(): HasOne
    {
        return $this->hasOne(CustomerWorkflow::class);
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
