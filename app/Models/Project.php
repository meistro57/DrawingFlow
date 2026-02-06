<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_number',
        'name',
        'customer_id',
        'description',
        'address',
        'city',
        'state',
        'zip',
        'start_date',
        'target_completion_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'target_completion_date' => 'date',
        ];
    }

    // Relationships

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function drawingRequests(): HasMany
    {
        return $this->hasMany(DrawingRequest::class);
    }

    public function submittals(): HasMany
    {
        return $this->hasMany(DrawingSubmittal::class);
    }

    public function fabQueueEntries(): HasMany
    {
        return $this->hasMany(FabQueue::class);
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
