<?php

namespace App\Chat\Live;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Shared plumbing for live feeds: short timeouts, caching, and failures reported as null instead of exceptions,
 * so a tool can say "couldn't check right now" and link the official page.
 */
abstract class LiveClient
{
    public const TIMEOUT_SECONDS = 8;

    protected function http(): PendingRequest
    {
        return Http::acceptJson()->timeout(self::TIMEOUT_SECONDS)->retry(1, 250, throw: false);
    }

    /** GET JSON, cached for $minutes. Null on any failure (failures are not cached). */
    protected function getJson(string $url, array $query = [], int $minutes = 15, array $headers = []): ?array
    {
        $key = 'chat:live:' . md5($url . '?' . http_build_query($query) . json_encode($headers));
        if (($cached = Cache::get($key)) !== null) {
            return $cached;
        }
        try {
            $response = $this->http()->withHeaders($headers)->get($url, $query);
        } catch (ConnectionException $e) {
            Log::info('Live feed unreachable', ['url' => $url, 'error' => $e->getMessage()]);
            return null;
        }
        if (!$response->successful() || !is_array($response->json())) {
            Log::info('Live feed failed', ['url' => $url, 'status' => $response->status()]);
            return null;
        }
        $data = $response->json();
        Cache::put($key, $data, now()->addMinutes($minutes));
        return $data;
    }

    protected static function minutes(string $source): int
    {
        return (int) config("chat.sources.cache_minutes.{$source}", 15);
    }
}
