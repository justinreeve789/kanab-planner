<?php
use OpenAI\Laravel\Facades\OpenAI;

class OpenAIService
{
    public function query() {
        $result = OpenAI::completions()->create([
            'model' => 'text-davinci-003',
            'prompt' => 'PHP is',
        ]);

        echo $result['choices'][0]['text']; // an open-source, widely-used, server-side scripting language.
    }
}