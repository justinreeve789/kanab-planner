<?php

namespace App\Services;

use GuzzleHttp\Client;

class NPSService
{
    const API_URL = 'https://developer.nps.gov/api/v1';

    public function __construct()
    {
        $this->client = new Client();
    }

    protected function request($type, $endpoint, $params = [])
    {
        try {
            $response = $this->client->request($type, self::API_URL . $endpoint, [
                'headers' => ['Accept' => 'application/json', 'X-Api-Key' => env('NPS_API_KEY')],
                'json' => $params,
            ]);
            return json_decode($response->getBody()->getContents());
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            // TODO: Error-handling.
            print_r((string) $e);
        }
    }

    public function getEvents($params = [])
    {
        $events = [];
        $pageNumber = 1;
        do {
            $response = $this->request('GET', '/events', $params);
            if ($response) {
                $events = array_merge($events, $response->data);
            }
            $params['pagenumber'] = ++$pageNumber;
        } while ($response->total > ($response->pagenumber * $response->pagesize));
        return $events;
    }

    public function getTrails($params = [])
    {
        $trails = [];
        $pageNumber = 0;
        do {
            $response = $this->request('GET', '/thingstodo', $params);
            if ($response) {
                $trails = array_merge($trails, $response->data);
            }
            $params['start'] = ++$pageNumber;
        } while ($response->total > ($response->pagenumber * $response->pagesize));
        return $trails;
    }
}
