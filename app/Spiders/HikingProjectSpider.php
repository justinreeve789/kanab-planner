<?php

namespace App\Spiders;

use Generator;
use Nesk\Puphpeteer\Puppeteer;
use RoachPHP\Downloader\Middleware\RequestDeduplicationMiddleware;
use RoachPHP\Extensions\LoggerExtension;
use RoachPHP\Extensions\StatsCollectorExtension;
use RoachPHP\Http\Response;
use RoachPHP\Spider\BasicSpider;
use RoachPHP\Spider\ParseResult;

class HikingProjectSpider extends BasicSpider
{
    public array $startUrls = [
        'https://www.hikingproject.com/directory/8010541/southwestern-utah',
    ];

    public array $downloaderMiddleware = [
        RequestDeduplicationMiddleware::class,
    ];

    public array $spiderMiddleware = [
        //
    ];

    public array $itemProcessors = [
        //
    ];

    public array $extensions = [
        LoggerExtension::class,
        StatsCollectorExtension::class,
    ];

    public int $concurrency = 2;

    public int $requestDelay = 1;

    /**
     * @return Generator<ParseResult>
     */
    public function parse(Response $response): Generator
    {
        $areas = $response->filter('#subareas .area a')->links();
        foreach ($areas as $area) {
            $page->goto()
            yield $this->request('GET', $area->getUri(), 'parseArea');
        }
    }

    public function parseArea(Response $response): Generator
    {
        $trails = $response->filter('#trail-detail-cards-container')->links();
        foreach
        print_r($trails);
    }
}
