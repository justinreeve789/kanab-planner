<?php

namespace App\Models\Assistant;

use App\Services\OpenAIService;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Config;

class Assistant extends Model
{
    use SoftDeletes;

    const OPENAI_ASSISTANT_TYPE = 'openai_assistant';

    protected $guarded = [];
    protected $table = 'assistants';

    public static function get($resource = null, $openAIService = null): Assistant {
        $resourceTable = $resource ? $resource->getTable() : null;
        $resourceId = $resource ? $resource->id : null;
        $assistant = self::where([
            'resource_table' => $resourceTable,
            'resource_id' => $resourceId,
        ])->first();

        // TODO: Add validation here or in the OpenAI response to make sure the assistant still exists, and re-create it if it doesn't.

        if (!$assistant) {
            if ($openAIService === null) {
                $openAIService = new OpenAIService();
            }
            $openAIAssistant = $openAIService->createAssistant(Config::get('openai.finetune_model'), $resource);
            if ($openAIAssistant) {
                $assistant = self::create([
                    'assistant_id' => $openAIAssistant->id,
                    'assistant_type' => self::OPENAI_ASSISTANT_TYPE,
                    'resource_table' => $resourceTable,
                    'resource_id' => $resourceId,
                ]);
            }
        }

        if (!$assistant) {
            throw new Exception('Could not create assistant.');
        }

        return $assistant;
    }
}
