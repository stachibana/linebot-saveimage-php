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

  if ($event instanceof \LINE\LINEBot\Event\MessageEvent) {
    if($event instanceof \LINE\LINEBot\Event\MessageEvent\ImageMessage) {
      \Cloudinary::config(array(
        'cloud_name' => getenv('CLOUDINARY_NAME'),
        'api_key' => getenv('CLOUDINARY_KEY'),
        'api_secret' => getenv('CLOUDINARY_SECRET')
      ));

      $response = $bot->getMessageContent($event->getMessageId());
      $im = imagecreatefromstring($response->getRawBody());

      if ($im !== false) {
          $filename = uniqid();
          $directory_path = 'tmp';
          if(!file_exists($directory_path)) {
            if(mkdir($directory_path, 0777, true)) {
                chmod($directory_path, 0777);
            }
          }
          imagejpeg($im, $directory_path. '/' . $filename . '.jpg', 75);
      }

      $path = dirname(__FILE__) . '/' . $directory_path. '/' . $filename . '.jpg';
      $result = \Cloudinary\Uploader::upload($path);

      $bot->replyMessage($event->getReplyToken(),
          (new \LINE\LINEBot\MessageBuilder\MultiMessageBuilder())
            ->add(new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($result['url']))
        );
      ;
    }
  }
}

 ?>
