<?php
$doublesize ="\x1d\x21\x22";
$normalsize ="\x1d\x21\x00";
 $centertext ="\x1b\x61\x01";
 $leftjustification="\x1b\x61\x00";
$texttoprint = "\x1B\x61\x48XXXXXRECIPT TEXT$leftjustification \n NEXT LINE \n $doublesize SIZE 2 g HERE $normalsize \nand normal size now again";
$texttoprint = stripslashes($texttoprint);

//$fp = fsockopen("192.168.1.87", 9100, $errno, $errstr, 10);
//$fp = fsockopen("220.233.160.54",9100, $errno, $errstr, 10);
fclose($fp);
//$fp = fsockopen("posprinter1.entrydns.org",911, $errno, $errstr, 10);
//$fp = fsockopen("115.70.72.250",912, $errno, $errstr, 10); //lan
$fp = fsockopen("115.70.72.250",87, $errno, $errstr, 10); //wifi
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