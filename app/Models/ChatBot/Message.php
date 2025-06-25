<?php

namespace App\Models\ChatBot;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $guarded = ['id'];

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }
}
