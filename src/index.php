<?php

include __DIR__ . "/libs/BaiduBce.phar";

use BaiduBce\BceClientConfigOptions;
use BaiduBce\Http\HttpHeaders;
use BaiduBce\Log\LogFactory;
use BaiduBce\Log\MonoLogFactory;
use BaiduBce\Services\Cdn\CdnClient;
use BaiduBce\Util\MimeTypes;
use BaiduBce\Util\Time;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LogLevel;

$g_CDN_TEST_CONFIG = [
    'credentials' => array(
        'accessKeyId' => getenv("secretId"),
        'secretAccessKey' => getenv("secretKey"),
    ),
    'endpoint' => 'http://cdn.baidubce.com',
];
$__handler = new StreamHandler(STDERR, Logger::DEBUG);
$__handler->setFormatter(
    new LineFormatter(null, null, false, true)
);
LogFactory::setInstance(
    new MonoLogFactory(array($__handler))
);
LogFactory::setLogLevel(LogLevel::DEBUG);

$client = new CdnClient($g_CDN_TEST_CONFIG);

function main_handler($event, $context)
{
    global $client;
    $data = json_decode($event->body);
    $code = $data->code;
    if ($code != 0) {
        return $data->message;
    }
    $url = $data->data->url;
    $isFreeze = (bool)$data->data->forbidden_status == 1;

    if ($isFreeze) {
        $refresh_tasks = [
            [
                "url" => $url
            ]
        ];
        $res_r = $client->purge($refresh_tasks);
        $res_r_m = $client->listPurgeStatus($res_r->id);
        print_r($res_r_m);
    }
    return [
        "isBase64Encoded" => true,
        "statusCode" => 200,
        "headers" => [],
        "body" => ""
    ];
}
