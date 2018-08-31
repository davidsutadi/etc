<?php//READ CONFIG.INI$path = realpath(dirname(__FILE__));$configini = parse_ini_file($path . '/config.ini', true);//print_r($configini); die();// VARDEFINE ('ftp_server',$configini[ftp][ftp_server]);DEFINE ('ftp_user_name',$configini[ftp][ftp_user_name]);DEFINE ('ftp_user_pass',$configini[ftp][ftp_user_pass]);DEFINE ('ftp_bulki','./'.$configini[ftp][ftp_bulki]);DEFINE ('ftp_bulko','./'.$configini[ftp][ftp_bulko]);DEFINE ('pool_time_sec',$configini[sys][pool_time_sec]);DEFINE ('dir_save_local',$path . $configini[sys][dir_save_local]);DEFINE ('log_file',$path . $configini[sys][log_file]);DEFINE ('counter_file',$path . $configini[sys][counter_file]);set_time_limit(0);error_reporting(0);date_default_timezone_set("Asia/Bangkok");echo str_repeat("\n", 300);echo "### SIMULATOR CMS BULK STARTED ###\n";// FUNCTIONfunction listfile($dir) {  $conn_id = ftp_connect(ftp_server) or die("Couldn't connect to FTP");   if (@ftp_login($conn_id, ftp_user_name, ftp_user_pass)) {    ftp_pasv($conn_id, true);    $contents = ftp_nlist($conn_id, $dir);    ftp_close($conn_id);    return $contents;  } else {    echo "Couldn't login FTP"; die();  }}function getfile($server_file) {  $conn_id = ftp_connect(ftp_server);  $login_result = ftp_login($conn_id, ftp_user_name, ftp_user_pass);  ftp_pasv($conn_id, true);  $filename = substr($server_file,8);  $local_file = dir_save_local . $filename;  do {    $res = ftp_size($conn_id, $server_file); usleep(100000);  } while($res != ftp_size($conn_id, $server_file)); //echo $conn_id . $local_file . $server_file; die();  if (ftp_get($conn_id, $local_file, $server_file, FTP_BINARY)) {    logs("ftp_get($server_file) >> OK");  } else {    logs("ftp_get($server_file) >> FAILED");	return false;  }  return $local_file;}function putfile($server_file,$dir) {  $conn_id = ftp_connect(ftp_server);  $filename = substr($server_file,8);  $local_file = dir_save_local . $filename;  $server_file = ftp_bulko . "/$dir/" . $filename;  $login_result = ftp_login($conn_id, ftp_user_name, ftp_user_pass);  ftp_pasv($conn_id, true);  if (ftp_put($conn_id, $server_file, $local_file, FTP_ASCII)) {	ftp_chmod($conn_id, 0777, $server_file);    logs("ftp_put($server_file) >> OK");  } else {    logs("ftp_put($server_file) >> FAILED");  }}function rnmfile($server_file,$new) {  $conn_id = ftp_connect(ftp_server);  $login_result = ftp_login($conn_id, ftp_user_name, ftp_user_pass);  ftp_pasv($conn_id, true);  $new = substr($server_file,0,8) . "old/" .$new . substr($server_file,9);  if (ftp_rename($conn_id, $server_file, $new)) {    logs("ftp_rename($server_file, $new) >> OK");  } else {    ftp_delete($conn_id, $server_file);    logs("ftp_rename($server_file, $new) >> FAILED");  }}function logs($data) {  file_put_contents(log_file, date("Y-m-d H:i:s > ") . $data . "\n", FILE_APPEND);}// MAINwhile(true) {  $files = listfile(ftp_bulki); //var_dump ($files); die();  foreach ($files as $file) {    $prefix = substr($file,8,1);    switch ($prefix) {      case 'I': //Prefix I        if (getfile($file) != false) {			rnmfile($file,'J');			putfile($file,'INS');		}      break;      case 'R': //Prefix R        $local_file = getfile($file);		if ($local_file != false) {			$handle = fopen($local_file, "r");			if ($handle) {			  $cardno = fgets(fopen(counter_file, 'r'));			  while (($line = fgets($handle)) !== false) { //echo substr($line,858,16); die();				if (substr($line,858,16) == '0000000000000000') { 				  $card_prod = strtoupper(substr($line,31,6)); //echo $card_prod; die();				  if ($card_prod == 'VACRD1') {					$line = substr_replace($line,'60329890'.sprintf('%08d', $cardno),858,16);				  } else if ($card_prod == 'VACRD2') {					$line = substr_replace($line,'60329896'.sprintf('%08d', $cardno),858,16);				  } else if ($card_prod == 'VACRD3') {					$line = substr_replace($line,'60329892'.sprintf('%08d', $cardno),858,16);				  } else if ($card_prod == 'VACRD4') {					$line = substr_replace($line,'60329893'.sprintf('%08d', $cardno),858,16);				  } else if ($card_prod == 'VACRD5') {					$line = substr_replace($line,'60329894'.sprintf('%08d', $cardno),858,16);				  } else if ($card_prod == 'VACRD6') {					$line = substr_replace($line,'60329895'.sprintf('%08d', $cardno),858,16);				  } else if ($card_prod == 'CCRDW7') {					$line = substr_replace($line,'46170031'.sprintf('%08d', $cardno),858,16);				  } else if ($card_prod == 'CCRDW8') {					$line = substr_replace($line,'46170032'.sprintf('%08d', $cardno),858,16);				  }				  $cardno++;				}				file_put_contents($local_file . '.new', $line, FILE_APPEND);			  }			  fwrite(fopen(counter_file, 'w'), $cardno);			  fclose($handle);			  rename($local_file,$local_file.'.src');			  rename($local_file.'.new',$local_file);			} else {			  logs("fopen($local_file) >> FAILED");			}			rnmfile($file,'K');			putfile($file,'REG');		}      break;      case 'C': //Prefix C        if (getfile($file) != false) {			rnmfile($file,'L');			putfile($file,'ACK');		}      break;      case 'A': //Prefix A        if (getfile($file) != false) {			rnmfile($file,'M');			putfile($file,'ACT');		}      break;      case 'B': //Prefix B        if (getfile($file) != false) {			rnmfile($file,'N');			putfile($file,'BLO');		}      break;      case 'U': //Prefix U        if (getfile($file) != false) {			rnmfile($file,'O');			putfile($file,'UBL');		}      break;      case 'P': //Prefix P        if (getfile($file) != false) {			rnmfile($file,'Q');			putfile($file,'RIS');		}      break;      default :        //DEFAULT      //echo substr($file,8) . "\n";    }    $prefix_arr = array('I','R','C','A','B','U','P');    if (in_array($prefix, $prefix_arr))  echo date("Y-m-d H:i:s > ") . substr($file,8) . " processed!\n";  }  sleep(pool_time_sec);}?>