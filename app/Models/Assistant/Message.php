<?php

namespace App\Models\Assistant;

use App\Models\Location;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class Message extends Model
{
    use SoftDeletes;

    protected $appends = ['content_html'];
    protected $casts = ['is_assistant' => 'bool'];
    protected $guarded = [];
    protected $table = 'assistant_messages';

    public function assistant_thread(): BelongsTo {
        return $this->belongsTo(Thread::class, 'assistant_thread_id', 'id');
    }

    public function getContentHtmlAttribute() {
        return self::transformLinks($this->attributes['content']);
    }

    /**
     * TODO: Cache the data.
     */
    public static function transformLinks(string $message): string {
        $locations = Location::whereNotNull('url')->get();
        $trails = Trail::whereNotNull('url')->get();
        foreach ($locations as $resource) {
            $modifiedResourceName = str_ireplace(['restaurant'], '', $resource->name);
            $modifiedResourceName = strtolower(preg_replace("/[^a-zA-Z0-9 ]/", "", $modifiedResourceName));
            $modifiedMessage = strtolower(preg_replace("/[^a-zA-Z0-9 ]/", "", $message));
            if (stripos($modifiedMessage, $modifiedResourceName) !== false) {
                $message = str_replace($resource->name, '<a href="' . $resource->url . '" target="_blank"><span>' . $resource->name . '</span></a>', $message);
            }
        }
        foreach ($trails as $resource) {
            $modifiedResourceName = str_ireplace(['hike', 'trail'], '', $resource->name);
            $modifiedResourceName = strtolower(preg_replace("/[^a-zA-Z0-9 ]/", "", $modifiedResourceName));
            $modifiedMessage = strtolower(preg_replace("/[^a-zA-Z0-9 ]/", "", $message));
            if (stripos($modifiedMessage, $modifiedResourceName) !== false) {
                $message = str_replace($resource->name, '<a href="' . $resource->url . '" target="_blank"><span>' . $resource->name . '</span></a>', $message);
            }
        }
        return $message;
//        $analysis = $this->phraseService->analyzePhraseWithSpacy($message);
//        if (isset($analysis['PROPN'])) {
//            foreach ($analysis['PROPN'] as $word) {
//                Log::info($word);
//                $location = Location::search($word)->where('name', $word)->options(['rankingScoreThreshold' => 0.9])->first();
//                if ($location && $location->url !== null) {
//                    Log::info($location->url);
//                    $message = str_replace($word, '<a href="' . $location->url . '" target="_blank">' . $word . '</a>', $message);
//                }
//            }
//        }
//        return $message;
    }
}
