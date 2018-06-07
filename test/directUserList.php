<?php
set_time_limit(0);
require __DIR__ . '/../vendor/autoload.php';

$debug = false;
$truncatedDebug = false;
$ig = new \InstagramAPI\Instagram($debug, $truncatedDebug);

$username = $argv[1];
$password = $argv[2];
$file_list = $argv[3];

$msg = "Olá! Tudo bom? :) Notamos que sua conta está sem atividades pois sua " .
  "senha não foi atualizada em nosso sistema. Pode ser que sua conta também " .
  "precise ser verificada, nesse caso é só seguir o passo a passo que está " .
  "disponível em sua conta e finalizar a verificação de conta com o código " .
  "enviado pelo Instagram para o seu e-mail. Se você tiver dificuldades em " .
  "atualizar sua senha ou verificar sua conta é só nos escrever e vamos " .
  "ajudar você.";

function load_list_to_array($file_list) {
  try {
    $array = file($file_list, FILE_IGNORE_NEW_LINES);
  } catch (\Exception $listEx) {
    printf("Impossible to load users list: \"%s\"\n",
      $listEx->getMessage());
    die();
  }
  return $array;
}

function prepare_username($username) {
  $trimmed = trim($username);
  $lowered = strtolower($trimmed);
  return $lowered;
}

function save_list($dest_file, $usersArray) {
  try {
    $fp = fopen($dest_file, 'w');
    foreach ($usersArray as $u) {
      fwrite($fp, $u . PHP_EOL);
    }
    fclose($fp);
  }
  catch (\Exception $exportEx) {
    printf("Could not save already notified users list: \"%s\"\n",
      $exportEx->getMessage());
  }
}

function next_exec_hour() {
  $h = (int) date('G') + mt_rand(1, 3);
  if ($h > 24) return $h - 24;
  return $h;
}

function save_exec_hour($hour) {
  try {
    $fp = fopen('/tmp/next_exec_hour.txt', 'w');
    fwrite($fp, $hour);
    fclose($fp);
    printf("Saved the next execution hour: at %s\n", $hour);
  }
  catch (\Exception $saveExecHour) {
    printf("Could not save the next exec hour: \"%s\"\n",
      $saveExecHour->getMessage());
  }
}

function remove_user_from_list($list, $user) {
  $new_list = array_filter($list, function($u) use ($user) {
    return $u !== $user;
  });
  return $new_list;
}

function create_pid_file() {
  try {
    file_put_contents('/tmp/next_exec.pid', '');
  }
  catch(\Exception $pidEx) {
    printf("Could not create pid file: \"%s\"\n", $pidEx->getMessage());
    exit(1);
  }
}

function remove_pid_file() {
  $pid = '/tmp/next_exec.pid';
  if (file_exists($pid)) {
    try {
      unlink($pid);
    }
    catch(\Exception $pidEx) {
      printf("Could not delete pid file: \"%s\"\n", $pidEx->getMessage());
      exit(1);
    }
  }
}

///////////////////////////////////////////////////////////////////

if (file_exists('/tmp/next_exec.pid')) {
  printf("We are already running. I will terminate right now.\n");
  die();
}

create_pid_file();
$usersList = load_list_to_array($file_list);
$purgedList = $usersList;
if (count($usersList)===0) {
  printf("There are no users to be notified for now. The list is empty.\n");
  die();
}
printf("Created a list of %s users to be notified\n", count($usersList));

try {
  printf("Using %s username to log in\n", $username);
  $ig->login($username, $password);
  printf("Logged in as %s\n", $username);
} catch (\Exception $logEx) {
  printf("Could not logged in as user %s: \"%s\"\n",
    $username, $logEx->getMessage());
  remove_pid_file();
  exit(1);
}
foreach ($usersList as $user) {
  $u = prepare_username($user);
  if ($u === '') continue;
  try {
    sleep(mt_rand(10, 30));
    $user_id = $ig->people->getUserIdForName($u);
    printf("Resolved the id of %s (%s)\n", $u, $user_id);
  }
  catch(\Exception $userIdEx) {
    printf("The id of %s was not found: \"%s\"\n", $u,
      $userIdEx->getMessage());
    $purgedList = remove_user_from_list($purgedList, $u);
    continue;
  }
  try {
    $ig->direct->sendText([ 'users' => [ $user_id ] ], $msg);
    printf("Sent the message to %s successfully\n", $u);
    $purgedList = remove_user_from_list($purgedList, $u);
  }
  catch(\Exception $msgEx) {
    printf("Error sending notification to %s: \"%s\"\n", $u,
      $msgEx->getMessage());
    save_list($file_list, $purgedList);
    $next_exec_at = next_exec_hour();
    save_exec_hour($next_exec_at);
    remove_pid_file();
    die();
  }
  sleep(mt_rand(60, 180));
}

remove_pid_file();
