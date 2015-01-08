<?php

$texttoprint = "XXXXXcxvxvcxRECIPT TEXT \n NEXT LINE \n MORE STUFF";
$texttoprint = stripslashes($texttoprint);

//$fp = fsockopen("192.168.1.87", 9100, $errno, $errstr, 10);
//$fp = fsockopen("220.233.160.54",9100, $errno, $errstr, 10);
fclose($fp);
//$fp = fsockopen("posprinter1.entrydns.org",911, $errno, $errstr, 10);
$fp = fsockopen("58.96.42.35",912, $errno, $errstr, 10);

if (!$fp) {
 	echo "$errstr ($errno)<br />\n";die;
} else {
	echo"Ssocket open OKK ";

	//fwrite($fp, "\033\100");
	//fwrite($fp, "\033\112\001\025\250");
	//$out=chr(27);
	
	//$out = $texttoprint . "\r\n";
	$out.= "\x1b". "p".chr(0).chr(50).chr(49);
	
	fwrite($fp, $out);
	//fwrite($fp, "\012\012\012\012\012\012\012\012\012\033\151\010\004\001");
	fclose($fp);
}
echo"<br>THE END";
?>