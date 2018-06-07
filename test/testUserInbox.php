<?php
set_time_limit(0);
require __DIR__ . '/../vendor/autoload.php';

$username = $argv[1];
$password = $argv[2];
$debug = true;
$truncatedDebug = true;

$ig = new \InstagramAPI\Instagram($debug, $truncatedDebug);

try {
  $ig->login($username, $password, 21600);
} catch (\Exception $e) {
  echo 'Something went wrong trying to login: ' . $e->getMessage() . "\n";
  exit(0);
}
try {
  $has_older = 1;
  $cursor = null;
  while ($has_older) {
    $inboxResponse = $ig->direct->getInbox($cursor);
    $inboxObject = json_decode(json_encode($inboxResponse));
    $inbox = $inboxObject->inbox;
    $threads = $inbox->threads;
    $cursor = $inbox->oldest_cursor;
    $has_older = $inbox->has_older;
    printf("%s\n", $cursor);
    array_map(function($thread) use ($ig) {
      if (array_key_exists(0, $thread->users)) {
        printf("\n\n*****************************\n");
        printf("%s: %s\n", $thread->users[0]->username,
          isset($thread->items[0]->text) ? $thread->items[0]->text : '');
      }
    }, $threads);
    sleep(mt_rand(3,8));
  }
  printf("TERMINADO...\n");
} catch (\Exception $e) {
  echo 'Something went wrong trying to get recent activity: ' . $e->getMessage() . "\n";
}
