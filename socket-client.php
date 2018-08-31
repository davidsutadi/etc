<?php
date_default_timezone_set("Asia/Bangkok");
$path = realpath(dirname(__FILE__));
DEFINE ('log_file',$path . '/log.txt');
error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush();

$service_port = 15001; //      15101                21501
$address = '10.231.2.247';// 10.243.129.195      10.204.4.133
$rrn = str_pad(rand(1,999999999),12,0,STR_PAD_LEFT);
$stan = rand(100000,999999);
$send = "ISO0160000100200F238860128A0901800000000160000041646170034000001224010000000000000052000000830032823{$stan}1028231808301808300510001111111111111374617003400000122=22052261006409810000{$rrn}S1AP0442        ATM VA                JAKARTA      DKIID360A5F4B115375D356F012M00BTES1+000013MDR3TES10000P1110000000008131150010048488168188010000000005090& 0000200090! Q200068 MDR2MDR2ANISA               1150010048488                          ?";
//echo $send; die();

echo str_repeat("\n", 300);
echo "### SOCKET CLIENT STARTED ###\n";

function logs($data) {
  file_put_contents(log_file, date("Y-m-d H:i:s")." [CLIENT] > " . trim($data) . "\n", FILE_APPEND);
}

echo "Socket Create...\n";
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if ($socket === false) {
  echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
} else {
  echo "Socket Create...OK\n";
}

echo "Attempting to connect to '$address' on port '$service_port'...\n";
$result = socket_connect($socket, $address, $service_port);
if ($result === false) {
  echo "socket_connect() failed.\nReason: ($result) " . socket_strerror(socket_last_error($socket)) . "\n";
} else {
  echo "Connect to '$address':'$service_port'...OK\n";
}

$isolen = str_pad(decbin(strlen($send)), 16, "0", STR_PAD_LEFT);
$send = chr(bindec(substr($isolen,0,8))) . chr(bindec(substr($isolen,-8))) . $send . "\0\n";
echo "Sending ISO request...\n";
socket_write($socket, $send , strlen($send));
echo "Sending ISO request...OK.\n";

/* not receive response */
//socket_close($socket); die();

$c = 0;
do{
  echo "Reading response ({$c}):\n"; $c++;
  if (false === (@$buf = socket_read($socket, 2, PHP_BINARY_READ))) { // PHP_BINARY_READ // PHP_NORMAL_READ
    $log = "socket_read() failed reason: " . socket_strerror(socket_last_error($socket));
    logs($log); echo $log;
    socket_close($client);
    break 1;
  }
  $len = str_pad(decbin(ord(substr($buf,0,1))),8,0,STR_PAD_LEFT) . str_pad(decbin(ord(substr($buf,-1))),8,0,STR_PAD_LEFT);
  $len = bindec($len);
  $buf = socket_read($socket, $len, PHP_BINARY_READ);
  logs($buf); echo $buf . "\n";
} while(true);
?>