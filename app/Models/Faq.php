<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    use HasFactory;

    protected $fillable = [
        'question',
        'answer',
        'category',
        'tags',
        'sort_order',
        'is_active',
        'view_count',
        'helpful_count',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'view_count' => 'integer',
        'helpful_count' => 'integer'
    ];

    /**
     * Get the user who created this FAQ
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this FAQ
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope for active FAQs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific category
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Increment view count
     */
    public function incrementViewCount()
    {
        $this->increment('view_count');
    }

    /**
     * Increment helpful count
     */
    public function incrementHelpfulCount()
    {
        $this->increment('helpful_count');
    }
}