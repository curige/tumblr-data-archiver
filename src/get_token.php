<?php

// OAuth1.0のアクセストークンを得るための一連の手順
// 要PECL OAuth

// 参考URL
// OAuth1.0のフローを概観できる
// https://developer.yahoo.co.jp/other/oauth/flow.html
// YAHOOのものだが、それぞれのEndpointにどのような項目を投げる必要があるのかが分かる
// https://developer.yahoo.co.jp/other/oauth/endpoint.html
// PECL OAuthの仕様例
// http://d.hatena.ne.jp/ritou/20110206/1296923253
// PECL OAuthのドキュメント
// http://jp2.php.net/manual/ja/book.oauth.php

require_once __DIR__ . '/../vendor/autoload.php';
$config = require_once __DIR__ . '/config.php';

$urls = [
    'request_token' => 'https://www.tumblr.com/oauth/request_token',
    'authorize' => 'https://www.tumblr.com/oauth/authorize',
    'access_token' => 'https://www.tumblr.com/oauth/access_token',
];

$oauth = new \OAuth($config['consumer_key'], $config['consumer_secret'], OAUTH_SIG_METHOD_HMACSHA1, OAUTH_AUTH_TYPE_URI);
$oauth->enableSSLChecks();

// 最後のGETを省略すると腐ったリクエストをして411が返されてしまうらしい
// https://stackoverflow.com/questions/43791876/getting-411-error-bad-request-in-evernote
// https://stackoverflow.com/questions/42243299/does-evernote-change-the-api-validation-for-the-content-length
$token = $oauth->getRequestToken($urls['request_token'], $urls['authorize'], 'GET');
$oauth->setToken($token['oauth_token'], $token['oauth_token_secret']);

echo 'ブラウザで開く: ', "{$urls['authorize']}?oauth_token={$token['oauth_token']}", "\n";
echo 'verifierを入力: ';
$verifier = trim(fgets(STDIN));

// ドキュメントでは引数が3つとなっているが騙されないように
$accessToken = $oauth->getAccessToken($urls['access_token'], '', $verifier, 'GET');
var_dump($accessToken);
