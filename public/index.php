<?php

use Dotenv\Dotenv;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use sparsely\Output;
use sparsely\Post;
use Stash\Driver\Memcache;
use Stash\Pool;

require_once '../vendor/autoload.php';

$dotenv = new Dotenv('../');
$dotenv->load();

$apikey = getenv('API_KEY');
$secret = getenv('SECRET');

$url = $_GET['url'] ?? null;
$urls = $_GET['urls'] ?? [];
if (!$url && !$urls) {
    Output::error(
        [],
        "Missing post URL(s)! Use the query param `url` to send one, or `urls[]` to send several."
    );
}

if ($url && $urls) {
    Output::error([], "Use either urls[] or url, not both query params!");
}

$mode = $_GET['mode'] ?? null;
if (!$mode) {
    Output::error([], "Missing mode!");
}

if ($url) {
    $urls = [$url];
}

$memcachePool = new Pool(new Memcache());

try {
    switch ($mode) {

        case "postFirstMonth":
            $client = new Client(
                [
                    'base_uri' => 'https://api.parsely.com/v2/analytics/post/detail',
                ]
            );

            $result = [];

            foreach ($urls as $url) {
                $post = new Post(
                    $url, $apikey, $secret, $client, $memcachePool
                );
                $result[$url] = $post->getFirstMonthTraffic();
            }

            Output::success($result);

            break;

        case "postTotal":
            $client = new Client(
                [
                    'base_uri' => 'https://api.parsely.com/v2/analytics/post/detail',
                ]
            );

            $result = [];

            foreach ($urls as $url) {
                $post = new Post(
                    $url, $apikey, $secret, $client, $memcachePool
                );
                $result[$url] = $post->getTotalTraffic();
            }

            Output::success($result);

            break;

        case "postInfo":
            $client = new Client(
                [
                    'base_uri' => 'https://api.parsely.com/v2/analytics/post/detail',
                ]
            );

            $result = [];

            foreach ($urls as $url) {
                $post = new Post(
                    $url, $apikey, $secret, $client, $memcachePool
                );
                $result[$url] = [
                    'firstMonth' => $post->getFirstMonthTraffic(),
                    'total' => $post->getTotalTraffic(),
                ];
            }

            Output::success($result);

            break;

        default:
            Output::error([], "Invalid mode");
            $config = [];
    }
} catch (\Exception $e) {
    Output::error([], $e->getMessage());
}