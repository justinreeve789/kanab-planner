<?php

namespace App\Models\Assistant;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cookie;

class Session extends Model
{
    const COOKIE_NAME = 'assistant_session_key';

    protected $guarded = [];
    protected $table = 'assistant_sessions';

    public function assistant_messages(): HasMany
    {
        return $this->hasMany(Message::class, 'assistant_session_id', 'id');
    }

    public function assistant_thread(): BelongsTo
    {
        return $this->belongsTo(Thread::class, 'id', 'assistant_thread_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id', 'user_id');
    }

    protected function sessionKey(): Attribute
    {
        return Attribute::make(
            get: function(): string {
                if (!isset($this->session_key)) {
                    $this->session_key = Cookie::get(self::COOKIE_NAME);
                }
                return $this->session_key;
            },
            set: function($sessionKey): void {
                $this->session_key = $sessionKey;
            }
        );
    }
}
