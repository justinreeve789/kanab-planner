<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Rubix\ML\Classifiers\KNearestNeighbors;
use Rubix\ML\Classifiers\NaiveBayes;
use Rubix\ML\Datasets\Labeled;
use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\Pipeline;
use Rubix\ML\Persisters\Filesystem;
use Rubix\ML\Transformers\TextNormalizer;
use Rubix\ML\Transformers\WordCountVectorizer;
use Rubix\ML\Tokenizers\Whitespace;
use Rubix\ML\Transformers\TfIdfTransformer;


class CustomRegExTokenizer
{
    public function transform(array $samples)
    {
        return array_map(function ($sample) {
            // Tokenizing using regex to split on word boundaries
            return preg_split('/\W+/', $sample[0]);
        }, $samples);
    }
}

class TrainML extends Command
{
    protected $signature = 'train:phrase {phrase?}';
    protected $description = 'Train and classify phrase intent.';

    public function handle()
    {
        $modelPath = storage_path('app/intent.model');

        // Sample labeled training data
        $phrases = [
            'view trail map',
            'show me the trail map',
            'can I see the map?',
            'display the route',
            'open the trail route',
            'where is the trail map?',
            'need a map of the path',
            'pull up the route map',
            'launch the trail view',
            'map please',
            'show path map',
            'visualize the hike',
            'trail layout',
            'open trail directions',
            'give me the hiking map',
            'map the trail',
            'need to see the route',
            'hiking trail map please',
            'get trail layout',
            'where’s the hiking map?',
            'access the trail map',
            'bring up map',
            'route viewer',
            'I want to see the trail',
            'pull up hiking map',
            'launch map',
            'I need the route map',
            'map of trail',
            'trail navigation map',
            'visual trail guide',
            'display the trail',
            'I want to view the trail map',
            'can you show me the map?',
            'access route info',
            'map of this trail',
            'load trail info',
            'I need to see where I’m going',
            'how do I see the trail map?',
            'give me a trail overview',
            'show me the trail route',
            'where’s the route?',
            'is there a trail map?',
            'give me a visual of the trail',
            'access map of trail',
            'see trail path',
            'navigate trail',
            'map out the trail',
            'download trail map',
            'view current trail location',
            'let me see the hiking trail'
        ];
        $labels = array_fill(0, count($phrases), 'view trail map'); // one intent
        $dataset = Labeled::build($phrases, $labels);

        $estimator = new KNearestNeighbors(3);

        $pipeline = new Pipeline([
            new WordCountVectorizer(new CustomRegexTokenizer()), // Tokenizer (you can adjust the tokenizer)
            new TfIdfTransformer(),
            new KNearestNeighbors(3),
        ]);

        $pipeline->train($dataset);

        $newPhrases = [
            'Where can I find the trail map?',
            'I need a map of the trails',
            'What’s the hiking route?',
            'Can you show me the hiking trails?',
            'Gimme a route',
            'I need a beer',
            '',
        ];
        $dataset = new Unlabeled($newPhrases);

        $predictions = $pipeline->predict($dataset);

        print_r($predictions);
    }
}