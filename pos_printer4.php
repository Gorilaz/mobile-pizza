<?php

$texttoprint = "DIRECTLY VIA ROUTER RECIPT TEXT \n NEXT LINE \n MORE STUFF";
$texttoprint = stripslashes($texttoprint);

//$fp = fsockopen("192.168.1.87", 9100, $errno, $errstr, 10);
//$fp = fsockopen("220.233.160.54",9100, $errno, $errstr, 10);
fclose($fp);
//$fp = fsockopen("posprinter1.entrydns.org",911, $errno, $errstr, 10);
$fp = fsockopen("115.70.72.250",9100, $errno, $errstr, 10); //directly via router

if (!$fp) {
 	echo "$errstr ($errno)<br />\n";die;
} else {
	echo"Ssocket open OK ";

	fwrite($fp, "\033\100");
	$out = $texttoprint . "\r\n";
	fwrite($fp, $out);
	fwrite($fp, "\012\012\012\012\012\012\012\012\012\033\151\010\004\001");
	fclose($fp);
}
echo"THE END";
?>