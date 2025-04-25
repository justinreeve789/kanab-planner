<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

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
                'headers' => ['X-Api-Key' => env('NPS_API_KEY')],
                'json' => $params,
            ]);
            return json_decode($response->getBody()->getContents());
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            Log::error((string) $e);

            // TODO: Figure out why Guzzle doesn't work sometimes (i.e. for /thingstodo).
            if ($type == 'GET') {
                $queryString = '?api_key=' . env('NPS_API_KEY') . '&' . http_build_query($params);
                $response = file_get_contents(self::API_URL . $endpoint . $queryString);
                return json_decode($response);
            }
        }
    }

    public function getAlerts($params = [])
    {
        $alerts = [];
        $start = 0;
        $limit = 50;
        do {
            $params['start'] = $start;
            $params['limit'] = $limit;
            $response = $this->request('GET', '/alerts', $params);
            if ($response) {
                $alerts = array_merge($alerts, $response->data);
            }
            $start += $limit;
        } while (!empty($response->data));
        return $alerts;
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
            } else {
                break;
            }
            $params['start'] = ++$pageNumber;
        } while ($response->total > (($response->start + 1) * $response->limit));
        return $trails;
    }
}
