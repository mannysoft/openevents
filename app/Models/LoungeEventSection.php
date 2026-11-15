<?php

namespace App\Models;

class LoungeEventSection extends MyBaseModel
{
    protected $connection = 'mysql_lounge';

    protected $table = 'event_sections';

    protected $fillable = [
        'section_content',
        'section_image',
        'event_id',
    ];

    public function scopeIncludePlaceholder($query, $isEditing = false)
    {
        // Return all if editing
        if ($isEditing) {
            return $query;
        }

        return $query->where('is_placeholder', false);
    }

    public function getSectionImageAttribute($value)
    {
        if ($this->is_placeholder) {
            return 'https://placehold.co/600x400';
        }

        return $value;
    }

    public function getSectionContentAttribute($value)
    {
        if ($this->is_placeholder) {
            return 'Placeholder content... This is a placeholder section. Please edit this section to add your own content.';
        }

        return $value;
    }

}
