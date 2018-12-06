<?php

ini_set('date.timezone', 'Asia/Tokyo');

require_once __DIR__ . '/../vendor/autoload.php';

function makeDataDir($dataDirPrefix, $blogName)
{
    $dir = $dataDirPrefix . '/' . basename($blogName);
    exec("rm -rf $dir");
    exec("mkdir -p $dir");
    return $dir;
}

function getClient(array $oauth)
{
    $client = new Tumblr\API\Client(
        $oauth['consumer_key'],
        $oauth['consumer_secret'],
        $oauth['token'],
        $oauth['token_secret']
    );
    return $client;
}

function generatePosts($client, $blogName) {
    $limit = 20;
    $offset = 0;
    while($offset < 1000) { // 1000 = fail safe
        //var_dump($client->getUserInfo());
        $res = $client->getBlogPosts($blogName, [
            'limit' => $limit,
            'offset' => $offset,
            //'reblog_info' => true,
            'notes_info' => true,
        ]);

        if (empty($res->posts)) {
            break;
        }

        foreach($res->posts as $post) {
            yield $post;
            sleep(2);
        }

        $offset += $limit;
    }
}

function saveImagesOnPhoto($dataDir, array $photos) {
    foreach($photos as $photo) {
        saveFromUrl($dataDir, $photo->original_size->url);
    }
}

function saveImagesOnText($dataDir, $body) {
    preg_match_all('/https:\/\/[a-z0-9\/_\-\.]+(jpg|png|gif|jpeg)/i', $body, $res);
    if (isset($res[0]) && !empty($res[0])) {
        foreach($res[0] as $url) {
            saveFromUrl($dataDir, $url);
        }
    }
}

function saveVideo($dataDir, $videoUrl) {
    saveFromUrl($dataDir, $videoUrl);
}

function saveFromUrl($dataDir, $url) {
    $parsedUrl = parse_url($url);
    $dir = trim(dirname($parsedUrl['path']), '/');
    $file = basename($parsedUrl['path']);
    $savePath = "{$dataDir}/{$parsedUrl['host']}/{$dir}/{$file}";
    if (file_exists($savePath)) {
        return;
    }
    exec("mkdir -p '{$dataDir}/{$parsedUrl['host']}/{$dir}'");
    file_put_contents($savePath, file_get_contents($url));
}

function main($blogName, $config) {
    $dataDir = makeDataDir($config['data_dir_prefix'], $blogName);
    $client = getClient($config['oauth']);
    $fp = fopen("{$dataDir}/response.json", "w");
    foreach(generatePosts($client, $blogName) as $post) {
        echo json_encode($post, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE), "\n";
        fputs($fp, json_encode($post, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . "\n");
        switch($post->type) {
        case 'photo':
            saveImagesOnPhoto($dataDir, $post->photos);
            break;
        case 'text':
            saveImagesOnText($dataDir, $post->body);
            break;
        case 'video':
            saveVideo($dataDir, $post->video_url);
            break;
        }
    }
    fclose($fp);
}

$config = require_once __DIR__ . '/config.php';
foreach($config['blog_name_list'] as $blogName) {
    echo "BEGIN $blogName at ", date('Y-m-d H:i:s'), "\n";
    main($blogName, $config);
    echo "END $blogName at ", date('Y-m-d H:i:s'), "\n";
    sleep(10);
}
