<?php

namespace App\Models\Assistant;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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

    public const LINK_TARGETS_CACHE_KEY = 'assistant-message-link-targets';
    public const LINK_TARGETS_CACHE_SECONDS = 3600;

    /**
     * Escape the message, then link every known place or trail name in it (case-insensitive, whole words), longest
     * names first, each spot of text at most once.
     *
     * The message is rendered with v-html, so escaping comes first: it used to be inserted raw, along with the names
     * and URLs, and overlapping names ("Zion" inside "Zion National Park") produced nested anchors.
     */
    public static function transformLinks(string $message): string {
        $escaped = e($message);
        $targets = self::linkTargets();
        if (empty($targets)) {
            return $escaped;
        }

        $alternation = implode('|', array_map(fn($name) => preg_quote(e($name), '/'), array_keys($targets)));
        $lookup = array_change_key_case($targets, CASE_LOWER);

        return preg_replace_callback('/(?<![\w&])(' . $alternation . ')(?!\w)/iu', function($match) use ($lookup) {
            $url = $lookup[mb_strtolower(html_entity_decode($match[1]))] ?? null;
            if ($url === null) {
                return $match[0];
            }
            return '<a href="' . e($url) . '" target="_blank" rel="noopener noreferrer"><span>' . $match[1] . '</span></a>';
        }, $escaped);
    }

    /**
     * [name => url] for usable locations and trails with an http(s) URL, longest names first. Cached, since every
     * serialized message needs it.
     */
    public static function linkTargets(): array
    {
        return Cache::remember(self::LINK_TARGETS_CACHE_KEY, self::LINK_TARGETS_CACHE_SECONDS, function() {
            $rows = DB::table('locations')->where('is_usable', 1)->whereNotNull('url')->get(['name', 'url'])
                ->concat(DB::table('trails')->where('is_usable', 1)->whereNotNull('url')->get(['name', 'url']));
            $targets = [];
            foreach ($rows as $row) {
                $name = trim((string) $row->name);
                if (mb_strlen($name) >= 3 && preg_match('#^https?://#i', $row->url) && !isset($targets[$name])) {
                    $targets[$name] = $row->url;
                }
            }
            uksort($targets, fn($a, $b) => mb_strlen($b) <=> mb_strlen($a));
            return $targets;
        });
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
