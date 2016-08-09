<?php

use Dotenv\Dotenv;
use GuzzleHttp\Client;
use sparsely\Output;
use sparsely\Post;
use Stash\Driver\Memcache;
use Stash\Pool;

require_once '../vendor/autoload.php';

$dotenv = new Dotenv('../');
$dotenv->load();
$dotenv->required('API_KEY');
$dotenv->required('SECRET');

$url = $_GET['url'] ?? null;
if (!$url) {
    Output::error([], "Missing post URL!");
}

$mode = $_GET['mode'] ?? null;
if (!$mode) {
    Output::error([], "Missing mode!");
}

switch ($mode) {
    case "postFirstMonth":
        $config = [
            'base_uri' => 'https://api.parsely.com/v2/analytics/post/detail',
        ];
        $method = 'getFirstMonthTraffic';
        break;
    default:
        Output::error([], "Invalid mode");
        $config = [];
}
$client = new Client($config);

$post = new Post(
    $url, getenv('API_KEY'), getenv('SECRET'), $client, new Pool(new Memcache())
);

try {
    $data = [
        'hits' => $post->getFirstMonthTraffic()
    ];
    Output::success($data);
} catch (\Exception $e) {
    Output::error([], $e->getMessage());
}