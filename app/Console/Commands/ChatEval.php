<?php

namespace App\Console\Commands;

use App\Chat\ChatAgent;
use App\Chat\ChatContext;
use App\Chat\Eval\TemplateFiller;
use App\Chat\History;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Runs the question templates (resources/chat/question_templates.json) through the assistant and reports, per
 * question, which tools it used against the ones the template expects, failures, length and time. Writes JSON and
 * Markdown to storage/app/chat-eval. --dry fills the templates without calling the model.
 */
class ChatEval extends Command
{
    protected $signature = 'chat:eval
        {--ids= : Template ids, e.g. 1,2,10-20}
        {--category= : Only this category}
        {--limit=0 : Stop after this many}
        {--seed=1 : Random seed for filling placeholders}
        {--dry : Only print the filled questions}';

    protected $description = 'Ask the trip assistant the question templates and score tool use';

    public function handle(): int
    {
        $templates = json_decode(File::get(config('chat.eval.templates_path')), true);
        $templates = $this->select($templates);
        $filler = new TemplateFiller((int) $this->option('seed'), ChatContext::now());

        if ($this->option('dry')) {
            foreach ($templates as $t) {
                $filled = $filler->fill($t);
                $this->line(sprintf('%3d %-24s %s%s', $t['id'], $t['category'], $filled['setup'] ? '[' . implode(' / ', $filled['setup']) . '] ' : '', $filled['question']));
            }
            return self::SUCCESS;
        }

        $agent = app(ChatAgent::class);
        $results = [];
        $bar = $this->output->createProgressBar(count($templates));
        foreach ($templates as $t) {
            $filled = $filler->fill($t);
            $history = [];
            $started = microtime(true);
            foreach ($filled['setup'] as $turn) {
                $setup = $agent->reply(new ChatContext(ChatContext::now(), history: $history), $turn);
                $history = History::forStorage([...$history, ...$setup->transcript]);
            }
            $reply = $agent->reply(new ChatContext(ChatContext::now(), history: $history), $filled['question']);
            $expected = $t['tools'];
            $used = $reply->toolsUsed();
            $recall = $expected ? count(array_intersect($expected, $used)) / count($expected) : null;
            $failedTools = array_values(array_unique(array_column(array_filter($reply->toolTrace, fn($x) => !$x['ok']), 'name')));
            $results[] = [
                'id' => $t['id'],
                'category' => $t['category'],
                'question' => $filled['question'],
                'setup' => $filled['setup'],
                'expected_tools' => $expected,
                'used_tools' => $used,
                'tool_recall' => $recall !== null ? round($recall, 2) : null,
                'failed_tools' => $failedTools,
                'answer' => $reply->text,
                'component' => $reply->component,
                'sources' => count($reply->sources),
                'words' => str_word_count($reply->text),
                'seconds' => round(microtime(true) - $started, 1),
                'model_failed' => $reply->failed,
                'top_answer_includes' => $t['top_answer'],
                'pass' => !$reply->failed && trim($reply->text) !== '' && ($recall === null || $recall >= 0.5),
            ];
            $bar->advance();
        }
        $bar->finish();
        $this->newLine(2);

        $dir = config('chat.eval.report_dir');
        File::ensureDirectoryExists($dir);
        $stamp = now()->format('Ymd-His');
        File::put("{$dir}/{$stamp}.json", json_encode(['model' => $agent->model()->name(), 'seed' => (int) $this->option('seed'), 'results' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        File::put("{$dir}/{$stamp}.md", $this->markdown($results, $agent->model()->name()));

        $this->table(['Category', 'Questions', 'Passed', 'Tool recall', 'Avg seconds'], $this->summary($results));
        $this->info("Report: {$dir}/{$stamp}.md");
        return self::SUCCESS;
    }

    private function select(array $templates): array
    {
        if ($ids = $this->option('ids')) {
            $wanted = [];
            foreach (explode(',', $ids) as $part) {
                [$from, $to] = array_pad(explode('-', trim($part)), 2, null);
                $wanted = [...$wanted, ...range((int) $from, (int) ($to ?? $from))];
            }
            $templates = array_filter($templates, fn($t) => in_array($t['id'], $wanted, true));
        }
        if ($category = $this->option('category')) {
            $templates = array_filter($templates, fn($t) => strcasecmp($t['category'], $category) === 0);
        }
        $templates = array_values($templates);
        return (int) $this->option('limit') > 0 ? array_slice($templates, 0, (int) $this->option('limit')) : $templates;
    }

    private function summary(array $results): array
    {
        $rows = [];
        foreach (collect($results)->groupBy('category') as $category => $group) {
            $recalls = $group->pluck('tool_recall')->filter(fn($r) => $r !== null);
            $rows[] = [
                $category,
                $group->count(),
                $group->where('pass', true)->count(),
                $recalls->isNotEmpty() ? round($recalls->avg(), 2) : '-',
                round($group->avg('seconds'), 1),
            ];
        }
        return $rows;
    }

    private function markdown(array $results, string $model): string
    {
        $lines = ["# Chat eval: {$model}", '', '| Category | Questions | Passed | Tool recall | Avg seconds |', '| --- | --- | --- | --- | --- |'];
        foreach ($this->summary($results) as $row) {
            $lines[] = '| ' . implode(' | ', $row) . ' |';
        }
        $lines[] = '';
        foreach ($results as $r) {
            $lines[] = "## {$r['id']}. {$r['question']}" . ($r['pass'] ? '' : ' (needs review)');
            if ($r['setup']) {
                $lines[] = '*After:* ' . implode(' / ', $r['setup']);
            }
            $lines[] = '*Tools:* used ' . (implode(', ', $r['used_tools']) ?: 'none') . '; expected ' . (implode(', ', $r['expected_tools']) ?: 'none')
                . ($r['failed_tools'] ? '; failed ' . implode(', ', $r['failed_tools']) : '');
            $lines[] = '*A top answer includes:* ' . $r['top_answer_includes'];
            $lines[] = '';
            $lines[] = '> ' . str_replace("\n", "\n> ", $r['answer'] ?: '(no answer)');
            $lines[] = '';
        }
        return implode("\n", $lines) . "\n";
    }
}
