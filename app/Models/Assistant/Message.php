<?php

namespace App\Models\Assistant;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use SoftDeletes;

    protected $casts = ['is_assistant' => 'bool'];
    protected $guarded = [];
    protected $table = 'assistant_messages';

    public function assistant_thread(): BelongsTo {
        return $this->belongsTo(Thread::class, 'assistant_thread_id', 'id');
    }
}
