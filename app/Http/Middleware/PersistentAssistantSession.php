<?php

namespace App\Http\Middleware;

use App\Models\Assistant\Session as AssistantSession;
use Closure;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;

class PersistentAssistantSession
{
    const DAYS_TO_SAVE = 7;

    public function handle($request, Closure $next)
    {
        if (!$request->hasCookie(AssistantSession::COOKIE_NAME)) {
            $sessionId = Session::getId();
            $hashedId = hash('sha256', $sessionId);
            Cookie::queue(AssistantSession::COOKIE_NAME, $hashedId, 60 * 24 * self::DAYS_TO_SAVE);
        }
        return $next($request);
    }
}
