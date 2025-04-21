<?php

namespace App\Models\Assistant;

use App\Models\Assistant\Session as AssistantSession;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cookie;

class Session extends Model
{
    use SoftDeletes;

    const COOKIE_NAME = 'assistant_session_key';
    const DAYS_TO_SAVE = 7;

    protected $appends = ['session_key'];
    protected $guarded = ['id'];
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

    public function getSessionKeyAttribute()
    {
        return $this->attributes['session_key'] ?? Cookie::get(self::COOKIE_NAME);
    }

//    protected function session_key(): Attribute
//    {
//        return Attribute::make(
//            get: function(): string {
//                if (!isset($this->attributes['session_key'])) {
//                    $this->attributes['session_key'] = Cookie::get(self::COOKIE_NAME);
//                }
//                return $this->attributes['session_key'];
//            },
//            set: function($sessionKey): void {
//                $this->attributes['session_key'] = $sessionKey;
//            }
//        );
//    }

    public static function createOrUpdateSessionCookie(): void
    {
        $sessionId = \Illuminate\Support\Facades\Session::getId();
        $hashedId = hash('sha256', $sessionId);
        Cookie::queue(AssistantSession::COOKIE_NAME, $hashedId, 60 * 24 * self::DAYS_TO_SAVE);
    }
}
