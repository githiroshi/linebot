<?php

require_once __DIR__ . '/vendor/autoload.php';

$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(getenv('CHANNEL_ACCESS_TOKEN'));
$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => getenv('CHANNEL_SECRET')]);

$signature = $_SERVER["HTTP_" . \LINE\LINEBot\Constant\HTTPHeader::LINE_SIGNATURE];
try {
  $events = $bot->parseEventRequest(file_get_contents('php://input'), $signature);
} catch(\LINE\LINEBot\Exception\InvalidSignatureException $e) {
  error_log("parseEventRequest failed. InvalidSignatureException => ".var_export($e, true));
} catch(\LINE\LINEBot\Exception\UnknownEventTypeException $e) {
  error_log("parseEventRequest failed. UnknownEventTypeException => ".var_export($e, true));
} catch(\LINE\LINEBot\Exception\UnknownMessageTypeException $e) {
  error_log("parseEventRequest failed. UnknownMessageTypeException => ".var_export($e, true));
} catch(\LINE\LINEBot\Exception\InvalidEventRequestException $e) {
  error_log("parseEventRequest failed. InvalidEventRequestException => ".var_export($e, true));
}

foreach ($events as $event) {
  if (!($event instanceof \LINE\LINEBot\Event\MessageEvent)) {
    error_log('Non message event has come');
    continue;
  }
  if (!($event instanceof \LINE\LINEBot\Event\MessageEvent\TextMessage)) {
    error_log('Non text message has come');
    continue;
  }
  $bot->replyText($event->getReplyToken(), makeTemplate($event->getText()));
}

function makeTemplate($length){
  return [
    "type" => "template",
    "altText" => "どの言葉にしますか？",
    "template" => [
      "type" => "buttons",
      "titel" => "Menu",
      "text" => "作る文字列の種類を選択",
      "actions" => makeButtonTemplateData($length)
      ]
  ];
}

function makeButton($length){
  return
  [
    [
      "type" => "postback",
      "label" => "半角英数",
      "data" => "lang=half&length=" . $length
    ],
    [
      "type" => "postback",
      "label" => "全角日本語",
      "data" => "lang=half&length=" . $length
    ],
    [
      "type" => "postback",
      "label" => "半角記号",
      "data" => "lang=half&length=" . $length
    ]
  ];
}
 ?>
