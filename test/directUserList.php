<?php
set_time_limit(0);
require __DIR__ . '/../vendor/autoload.php';

define('PROCESS_NAME', 'directUserList');
define('PID_FILE', '/tmp/next_exec.pid');
define('NEXT_EXEC_HOUR_FILE', '/tmp/next_exec_hour.txt');

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
    printf("%s Impossible to load users list: \"%s\"\n",
      time_str(), $listEx->getMessage());
    remove_pid_file();
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
    printf("%s Could not save already notified users list: \"%s\"\n",
      time_str(), $exportEx->getMessage());
  }
}

function get_next_exec_hour($next_exec_hour_file) {
  if (file_exists($next_exec_hour_file)) {
    $exec_hour = (int) trim(file_get_contents($next_exec_hour_file));
  }
  else {
    $exec_hour = (int) date('G');
  }
  return $exec_hour;
}

function next_exec_hour() {
  $h = (int) date('G') + mt_rand(1, 3);
  if ($h < 10) $h = '0' . $h;
  if ($h > 24) return $h - 24;
  return $h;
}

function save_exec_hour($dest_file, $hour) {
  try {
    $fp = fopen($dest_file, 'w');
    fwrite($fp, $hour);
    fclose($fp);
    printf("%s Saved the next execution hour: at %s\n", time_str(), $hour);
  }
  catch (\Exception $saveExecHour) {
    printf("%s Could not save the next exec hour: \"%s\"\n",
      time_str(), $saveExecHour->getMessage());
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
    file_put_contents(PID_FILE, '');
  }
  catch(\Exception $pidEx) {
    printf("%s Could not create pid file: \"%s\"\n",
      time_str(), $pidEx->getMessage());
    exit(1);
  }
}

function remove_pid_file() {
  $pid = PID_FILE;
  if (file_exists($pid)) {
    try {
      unlink($pid);
    }
    catch(\Exception $pidEx) {
      printf("%s Could not delete pid file: \"%s\"\n",
        time_str(), $pidEx->getMessage());
      exit(1);
    }
  }
}

function is_running() {
  return file_exists(PID_FILE);
}

function time_str($pname = true) {
  $d = date('j');
  return sprintf("%s %s %s", date('M'),
    strlen($d) === 2 ? $d : ' ' . $d,
    date('G:i:s') . $pname ? date('G:i:s') . ' ' . PROCESS_NAME : '');
}

///////////////////////////////////////////////////////////////////

if (is_running()) {
  printf("%s We are already running. I will terminate right now.\n",
    time_str());
  die();
}

if ((int) date('G') !== get_next_exec_hour(NEXT_EXEC_HOUR_FILE)) {
  printf("%s Not the corresponding execution hour. Terminating...\n", time_str());
  die();
}

printf("%s Everything goes fine. We will start...\n", time_str());

create_pid_file();
$usersList = load_list_to_array($file_list);
$purgedList = $usersList;
if (count($usersList)===0) {
  printf("%s The user list is empty, so we are done.\n", time_str());
  remove_pid_file();
  die();
}
printf("%s Created a list of %s users to be notified\n",
  time_str(), count($usersList));

try {
  printf("%s Using %s username to log in\n", time_str(), $username);
  $ig->login($username, $password);
  printf("%s Logged in as %s\n", time_str(), $username);
} catch (\Exception $logEx) {
  printf("%s Could not logged in as user %s: \"%s\"\n",
    time_str(), $username, $logEx->getMessage());
  remove_pid_file();
  exit(1);
}
foreach ($usersList as $user) {
  $u = prepare_username($user);
  if ($u === '') continue;
  try {
    sleep(mt_rand(10, 30));
    $user_id = $ig->people->getUserIdForName($u);
    printf("%s Resolved the id of %s (%s)\n", time_str(), $u, $user_id);
  }
  catch(\Exception $userIdEx) {
    printf("%s The id of %s was not found: \"%s\"\n", time_str(),
      $u, $userIdEx->getMessage());
    $purgedList = remove_user_from_list($purgedList, $u);
    continue;
  }
  try {
    $ig->direct->sendText([ 'users' => [ $user_id ] ], $msg);
    printf("%s Sent the message to %s successfully\n", time_str(), $u);
    $purgedList = remove_user_from_list($purgedList, $u);
  }
  catch(\Exception $msgEx) {
    printf("%s Error sending notification to %s: \"%s\"\n", 
      time_str(), $u, $msgEx->getMessage());
    save_list($file_list, $purgedList);
    $next_exec_at = next_exec_hour();
    save_exec_hour(NEXT_EXEC_HOUR_FILE, $next_exec_at);
    remove_pid_file();
    die();
  }
  sleep(mt_rand(60, 180));
}

save_list($file_list, $purgedList);
printf("%s Terminated\n", time_str());
remove_pid_file();
