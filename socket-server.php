<?php
//function
date_default_timezone_set("Asia/Bangkok");
$path = realpath(dirname(__FILE__));
DEFINE ('log_file',$path . '/log.txt');
function logs($data) {
  file_put_contents(log_file, date("Y-m-d H:i:s")." [SERVER] > " . trim($data) . "\n", FILE_APPEND);
}
//end function
error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush();
echo str_repeat("\n", 300);
echo "### SOCKET SERVER STARTED ###\n";
$address = '10.231.2.247';
$port = 15001;
if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
  $log = "socket_create() failed reason: " . socket_strerror(socket_last_error());
  logs($log); echo $log;
}
if (socket_bind($sock, $address, $port) === false) {
  $log = "socket_bind() failed reason: " . socket_strerror(socket_last_error($sock));
  logs($log); echo $log;
}
if (socket_listen($sock, 5) === false) {
  $log = "socket_listen() failed reason: " . socket_strerror(socket_last_error($sock));
  logs($log); echo $log;
}

//Set Receive Timeout
$timeout = array('sec'=>1,'usec'=>0);
socket_set_option($sock,SOL_SOCKET,SO_RCVTIMEO,$timeout);

$clients = array();
do {
  $read = array();
  $read[] = $sock;
  $read = array_merge($read,$clients);
  $write = NULL;
  $except = NULL;
  $tv_sec = 5;
  if(socket_select($read,$write,$except,$tv_sec) < 1){
    continue;
  }
  // Handle new Connections
  if (in_array($sock, $read)) {
    if (($msgsock = socket_accept($sock)) === false) {
      $log = "socket_accept() failed reason: " . socket_strerror(socket_last_error($sock));
	  logs($log); echo $log;
      break;
    }
    $clients[] = $msgsock;
    $key = array_keys($clients, $msgsock);
    //$msg = "Hello client : {$key[0]}\n";
    //socket_write($msgsock, $msg, strlen($msg));
	$log = "Client {$key[0]}: CONNECTED\n";
	logs($log); echo $log;
  }
  // Handle Input
  foreach ($clients as $key => $client) { // for each client        
    if (in_array($client, $read)) {
      if (false === (@$buf = socket_read($client, 2, PHP_BINARY_READ))) { // PHP_BINARY_READ // PHP_NORMAL_READ
        $log = "Client {$key}: socket_read() failed reason: " . socket_strerror(socket_last_error($client));
        logs($log); echo $log;
        unset($clients[$key]);
        socket_close($client);
        break 1;
      }
          
      $len = str_pad(decbin(ord(substr($buf,0,1))),8,0,STR_PAD_LEFT) . str_pad(decbin(ord(substr($buf,-1))),8,0,STR_PAD_LEFT);
      $len = bindec($len);
      @$buf = socket_read($client, $len, PHP_BINARY_READ);
      
      if (!$buf = trim($buf)) {
        continue;
      }
      if ($buf == 'quit') {
        unset($clients[$key]);
        socket_close($client);
        break;
      }
      if ($buf == 'shutdown') {
        socket_close($client);
        break 2;
      }
      $log = "Client {$key}: '{$buf}'\n";
      logs($log); echo $log;
      
      $isolen = str_pad(decbin(strlen($buf)), 16, "0", STR_PAD_LEFT);
      $buf = chr(bindec(substr($isolen,0,8))) . chr(bindec(substr($isolen,-8))) . $buf . "\0\n";
      socket_write($client, $buf, strlen($buf));
    }
  }        
} while (true);
socket_close($sock);
?>