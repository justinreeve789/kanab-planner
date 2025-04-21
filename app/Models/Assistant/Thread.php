<?php

namespace App\Models\Assistant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Thread extends Model
{
    use SoftDeletes;

    protected $guarded = [];
    protected $table = 'assistant_threads';

    public function assistant_messages(): HasMany {
        return $this->hasMany(Message::class, 'assistant_thread_id', 'id');
    }

    public function assistant_session(): BelongsTo {
        return $this->belongsTo(Session::class, 'assistant_session_id', 'id');
    }
}
