<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\Transformers\TextNormalizer;
use Rubix\ML\Transformers\WordCountVectorizer;
use Rubix\ML\Transformers\TfIdfTransformer;
use Rubix\ML\Kernels\Distance\Cosine;

class TrainML extends Command
{
    protected $signature = 'train:phrases';
    protected $description = 'Train a similarity model for detecting map-related intent';

    public function handle()
    {
        $seedPhrases = [
            'show me a map',
            'open the map',
            'display a map',
            'map of the area',
            'navigate to location',
            'map view',
            'view map',
            'I need a map',
            'how do I see the map',
            'check the map',
        ];

        $candidatePhrases = [
            'where is the location?',
            'can I see directions?',
            'show directions on a map',
            'what does the area look like?',
            'open directions',
            'how do I get there?',
            'I want to see a map',
            'get a map',
            'please show me the area',
            'view a map',
            'show me the route',
            'navigate me there',
            'display the route',
            'launch map view',
            'help me navigate',
            'look at the location',
            'pull up the map',
            'find on the map',
            'access the map',
            'can I open a map?',
            'I’d like to view a map',
            'open up Google Maps',
            'how do I see where this is?',
        ];

        // Tokenizer
        $tokenize = fn($phrases) => array_map(
            fn($p) => [implode(' ', explode(' ', strtolower($p)))], // Wrap the whole string in an array
            $phrases
        );

        // Combine all phrases for consistent transformation
        $allPhrases = array_merge($seedPhrases, $candidatePhrases, ['view a map']);

        // Wrap each phrase group in Unlabeled datasets
        $allDataset = Unlabeled::build($tokenize($allPhrases));

        // Set up transformers
        $normalizer = new TextNormalizer();
        $vectorizer = new WordCountVectorizer();
        $tfidf = new TfIdfTransformer();

        // Transform all phrases
        $normalizer->transform($allDataset);
        $vectorizer->fit($allDataset);
        $vectorizer->transform($allDataset);
        $tfidf->fit($allDataset);
        $tfidf->transform($allDataset);

        // Split the transformed dataset
        $seedCount = count($seedPhrases);
        $candidateCount = count($candidatePhrases);

        $seedDataset = $allDataset->slice(0, $seedCount);
        $candidateDataset = $allDataset->slice($seedCount, $candidateCount);
        $targetDataset = $allDataset->slice($seedCount + $candidateCount, 1);

        $targetVec = $targetDataset->samples()[0]; // vector for "view a map"
        $candidateVectors = $candidateDataset->samples();

        // Compare using Cosine similarity
        $distance = new Cosine();
        $results = [];

        foreach ($candidateVectors as $i => $vec) {
            $similarity = 1 - $distance->compute($targetVec, $vec);
            $results[] = [
                'phrase' => $candidatePhrases[$i],
                'similarity' => $similarity,
            ];
        }

        // Sort and show top 20
        usort($results, fn($a, $b) => $b['similarity'] <=> $a['similarity']);
        $top = array_slice($results, 0, 20);

        $this->info("Top 20 phrases similar to 'view a map':\n");
        foreach ($top as $item) {
            $this->line(sprintf('[%.3f] %s', $item['similarity'], $item['phrase']));
        }

        return 0;
    }
}