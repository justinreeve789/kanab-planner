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
//        $contentHtml = self::convertInlineNumberedListToHtml($this->attributes['content']);
        $contentHtml = self::transformLinks($this->attributes['content']);
        return $contentHtml;
    }

    /**
     * TODO: Cache the data.
     */
    public static function transformLinks(string $message): string {
        $locations = Location::whereNotNull('url')->get();
        $trails = Trail::whereNotNull('url')->get();
        foreach ($locations as $resource) {
            $modifiedResourceName = str_ireplace(['the ', ' national park', ' restaurant'], '', strtolower($resource->name));
            $modifiedResourceName = trim(strtolower(preg_replace("/[^a-zA-Z0-9 ]/", "", $modifiedResourceName)));
            $modifiedMessage = str_ireplace(['the ', ' national park', ' restaurant'], '', strtolower($message));
            $modifiedMessage = trim(strtolower(preg_replace("/[^a-zA-Z0-9 ]/", "", $modifiedMessage)));
            if (stripos($modifiedMessage, $modifiedResourceName) !== false) {
                $message = str_ireplace($resource->name, '<a href="' . $resource->url . '" target="_blank"><span>' . $resource->name . '</span></a>', $message);
            }
        }
        foreach ($trails as $resource) {
            $modifiedResourceName = str_ireplace(['the ', ' hike', ' loop', ' trail'], '', strtolower($resource->name));
            $modifiedResourceName = trim(strtolower(preg_replace("/[^a-zA-Z0-9 ]/", "", $modifiedResourceName)));
            $modifiedMessage = str_ireplace(['the ', ' hike', ' loop', ' trail'], '', strtolower($message));
            $modifiedMessage = trim(strtolower(preg_replace("/[^a-zA-Z0-9 ]/", "", $modifiedMessage)));
            if (stripos($modifiedMessage, $modifiedResourceName) !== false) {
                $message = str_ireplace($resource->name, '<a href="' . $resource->url . '" target="_blank"><span>' . $resource->name . '</span></a>', $message);
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

    /**
     * TODO: Fix this. It truncates the end.
     */
    public static function convertInlineNumberedListToHtml($text) {
        // Match everything before the numbered list
        if (preg_match('/^(.*?)(\s*\d+\.\s+)/', $text, $prefixMatch)) {
            $prefix = trim($prefixMatch[1]);
            // Split into list items
            $items = preg_split('/\s(?=\d+\.\s)/', $text);

            // Filter out non-list parts (like the first sentence or ending sentence)
            $listItems = [];
            foreach ($items as $item) {
                if (preg_match('/^\d+\.\s*(.*)$/', $item, $m)) {
                    $listItems[] = trim($m[1]);
                }
            }

            // Rebuild the output
            $html = "<p>{$prefix}:</p>\n<ol>";
            foreach ($listItems as $li) {
                $html .= '<li>' . htmlspecialchars($li) . '</li>';
            }
            $html .= '</ol>';

            return $html;
        }

        // If no match, just return escaped version
        return '<p>' . htmlspecialchars($text) . '</p>';
    }
}
