<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Database\Factories\SalesmanFactory;

/**
 * @use HasFactory<SalesmanFactory>
 */
class Salesman extends Model
{
    /** @use HasFactory<SalesmanFactory> */
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'first_name',
        'last_name', 
        'titles_before',
        'titles_after',
        'prosight_id',
        'email',
        'phone',
        'gender_code',
        'marital_status_code',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'titles_before' => 'array',
        'titles_after' => 'array',
    ];

    /**
     * Get the gender for this salesman.
     *
     * @return BelongsTo<Gender, $this>
     */
    public function gender(): BelongsTo
    {
        return $this->belongsTo(Gender::class, 'gender_code', 'code');
    }

    /**
     * Get the marital status for this salesman.
     *
     * @return BelongsTo<MaritalStatus, $this>
     */
    public function maritalStatus(): BelongsTo
    {
        return $this->belongsTo(MaritalStatus::class, 'marital_status_code', 'code');
    }

    /**
     * Generate display name from titles and names.
     */
    public function getDisplayNameAttribute(): string
    {
        $parts = [];
        
        // Add titles before
        if ($this->titles_before) {
            $parts = array_merge($parts, $this->titles_before);
        }
        
        // Add first and last name
        $parts[] = $this->first_name;
        $parts[] = $this->last_name;
        
        // Add titles after
        if ($this->titles_after) {
            $parts = array_merge($parts, $this->titles_after);
        }
        
        return implode(' ', $parts);
    }
}
