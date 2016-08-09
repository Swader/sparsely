<?php

namespace sparsely;

use Cake\Chronos\Chronos;
use GuzzleHttp\Client;
use Psr\Cache\CacheItemPoolInterface;

class Post
{
    /** day in seconds */
    const DAY = 86400;

    const CACHING = false;

    /** @var string */
    private $url;

    /** @var Client */
    private $client;

    /** @var CacheItemPoolInterface */
    private $cache;

    /** @var string */
    private $apiKey;

    /** @var string */
    private $secret;

    public function __construct(
        string $url,
        string $apiKey,
        string $secret,
        Client $client,
        CacheItemPoolInterface $cache
    ) {
        $url = rtrim($url, "/")."/";

        $this->url = $url;
        $this->client = $client;
        $this->cache = $cache;
        $this->apiKey = $apiKey;
        $this->secret = $secret;
    }

    public function getPublishedDate() : Chronos
    {
        $hash = md5($this->url . '-pubdatehash');

        $item = $this->cache->getItem($hash);

        if (!$item->isHit() || self::CACHING === false) {
            $response = $this->fetch();
            $data = $response['data'][0]['pub_date'];
            $item->set($data);
            $this->cache->save($item);
        }

        $data = $item->get();

        return Chronos::createFromFormat(
            'Y-m-d H:i:s', str_replace("T", " ", $data)
        );
    }

    private function getFirstMonthDate() : Chronos
    {
        $new = $this->getPublishedDate();
        return $new->addMonth();
    }

    private function fetch(array $data = [])
    {
        $merged = array_merge(
            $data, [
                'apikey' => $this->apiKey,
                'secret' => $this->secret,
                'url' => $this->url,
            ]
        );

        $r = $this->client->get(
            '', [
                'query' => $merged,
            ]
        );

        $body = $r->getBody();
        $bodyContents = $body->getContents();
        $decoded = json_decode($bodyContents, true);

        if (empty($decoded['data'])) {
            throw new \Exception(
                "Data is empty - did you pass in a valid URL?"
            );
        }

        return $decoded;
    }

    private function fetchAsync(array $data = [])
    {
        $merged = array_merge(
            $data, [
                'apikey' => $this->apiKey,
                'secret' => $this->secret,
                'url' => $this->url,
            ]
        );

        $promise = $this->client->getAsync(
            '', [
                'query' => $merged,
            ]
        );

        return $promise->then(function ($response) {
            $body = $response->getBody();
            $bodyContents = $body->getContents();
            $decoded = json_decode($bodyContents, true);

            if (empty($decoded['data'])) {
                throw new \Exception(
                    "Data is empty - did you pass in a valid URL?"
                );
            }

            return $decoded;
        });

    }

    public function getFirstMonthTraffic() : int
    {
        $pubDate = $this->getPublishedDate();
        $firstMonth = $this->getFirstMonthDate();

        $hash = md5($this->url . '-firstmonth');

        $item = $this->cache->getItem($hash);
        if (!$item->isHit() || self::CACHING === false) {
            $params = [
                'period_start' => $pubDate->toDateString(),
                'period_end' => $firstMonth->toDateString(),
            ];
            $response = $this->fetch($params);

            $data = $response['data'][0]['_hits'];
            $item->set($data);

            if ($firstMonth->isFuture()) {
                $item->expiresAfter(self::DAY);
            }

            $this->cache->save($item);
        }

        $data = $item->get();

        return (int)$data;
    }

    public function getTotalTraffic() : int
    {
        $pubDate = $this->getPublishedDate();
        $hash = md5($this->url . '-total');

        if ($pubDate->addMonth()->isFuture()) {
            return 0;
        }

        $item = $this->cache->getItem($hash);

        if (!$item->isHit() || self::CACHING === false) {
            $params = [
                'period_start' => $pubDate->toDateString(),
                'period_end' => Chronos::now()->toDateString(),
            ];
            $response = $this->fetch($params);

            $data = $response['data'][0]['_hits'];
            $item->set($data);

            $item->expiresAfter(self::DAY);
            $this->cache->save($item);
        }

        $data = $item->get();

        return (int)$data;
    }
}