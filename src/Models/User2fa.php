<?php

namespace Partybussen\Nova2fa\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User2fa extends Model
{
    /**
     * @var string
     */
    protected $table   = 'user_2fa';

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('nova2fa.models.user'));
    }
}