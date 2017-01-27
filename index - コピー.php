<?php
require_once __DIR__ . '/vendor/autoload.php';
use Symfony\Component\HttpFoundation\Request;

$app = new Silex\Application();
$app->post('/callback', function (Request $request) use ($app) {
    $client = new GuzzleHttp\Client();
    $body = json_decode($request->getContent(), true);
    foreach ($body['result'] as $msg) {
        // get from and message
        $from = $msg['content']['from'];
        $message = $msg['content']['text'];
        // get context from Redis
        $redis = new Predis\Client(getenv('REDISTOGO_URL'));
        $context = $redis->get($from);
        // chat API
        $response = chat($message, $context);
        // save context to Redis
        $redis->set($from, $response->context);

        $res_content = $msg['content'];
        $res_content['text'] = $response;
        $requestOptions = [
            'body' => json_encode([
                'to' => [$from],
                'toChannel' => 1383378250, #Fixed value
                'eventType' => '138311608800106203', #Fixed value
                "content" => $res_content,
            ]),
            'headers' => [
                'Content-Type' => 'application/json; charset=UTF-8',
                'X-Line-ChannelID' => getenv('CHANNEL_ID'),
                'X-Line-ChannelSecret' => getenv('CHANNEL_SECRET'),
                'X-Line-Trusted-User-With-ACL' => getenv('LINE_CHANNEL_MID'),
            ],
            'proxy' => [
                'https' => getenv('FIXIE_URL'),
            ],
        ];
        try {
            $client->request('post', 'https://trialbot-api.line.me/v1/events', $requestOptions);
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }
    return 'OK';
});

$app->run();

function chat($message, $context) {
    $api_key = getenv('DOCOMO_API_KEY');
    $api_url = sprintf('https://api.apigw.smt.docomo.ne.jp/dialogue/v1/dialogue?APIKEY=%s', $api_key);
    $req_body = array(
        'utt' => $message,
        'context' => $context,
    );
    $req_body['context'] = $message;

    $headers = array(
        'Content-Type: application/json; charset=UTF-8',
    );
    $options = array(
        'http'=>array(
            'method'  => 'POST',
            'header'  => implode("\r\n", $headers),
            'content' => json_encode($req_body),
            )
        );
    $stream = stream_context_create($options);
    $res = json_decode(file_get_contents($api_url, false, $stream));

    return $res->utt;
}
