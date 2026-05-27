<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormSubmission extends CmsModel
{
    protected $table = 'form_submissions';

    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'payload' => 'array',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(FormSubmissionAnswer::class, 'submission_id')->orderBy('id');
    }
}
