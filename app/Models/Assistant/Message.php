<?php

namespace App\Models\Assistant;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $casts = ['is_assistant' => 'bool'];
    protected $guarded = [];
    protected $table = 'assistant_messages';

    public function assistant_session(): BelongsTo {
        return $this->belongsTo(Session::class, 'id', 'assistant_session_id');
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class, 'id', 'user_id');
    }
}
