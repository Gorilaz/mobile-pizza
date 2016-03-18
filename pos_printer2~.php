<?php

//$printer = "192.168.1.200";

// Open connection to the thermal printer
//$fp = fopen($printer, "w");
$TXT_NORMAL      = '\x1b\x21\x00'; # Normal text
$TXT_2HEIGHT     = '\x1b\x21\x10';# Double height text
$TXT_2WIDTH      = '\x1b\x21\x20'; # Double width text
$TXT_UNDERL_OFF  = '\x1b\x2d\x00'; # Underline font OFF
$TXT_UNDERL_ON   = '\x1b\x2d\x01'; # Underline font 1-dot ON
$TXT_UNDERL2_ON  = '\x1b\x2d\x02'; # Underline font 2-dot ON
$TXT_BOLD_OFF    = '\x1b\x45\x00'; # Bold font OFF
$TXT_BOLD_ON     = '\x1b\x45\x01'; # Bold font ON
$TXT_FONT_A      = '\x1b\x4d\x00'; # Font type A
$TXT_FONT_B      = '\x1b\x4d\x01';# Font type B
$TXT_ALIGN_LT    = '\x1b\x61\x00'; # Left justification
$TXT_ALIGN_CT    = '\x1b\x61\x01'; # Centering
$TXT_ALIGN_RT    = '\x1b\x61\x02'; # Right justification
$DRW_KICK_OUT    =  '\x10\x14\x1\x1\x5'; # Drawer kickout
//$fp=fsockopen("192.168.1.200", 9100, $errno, $errstr, 10);
$fp=fsockopen("115.70.72.250", 912, $errno, $errstr, 10);
//$fp=fsockopen("115.70.66.106", 9100, $errno, $errstr, 10);

if (!$fp){
  die('no connection');

}
$data ="\x1b\x40"; //clear buffer
$data.="\x1b\x3f\x0a\x00"; //reset printer hardware


//$data .= "$TXT_ALIGN_CT";
$data= "\x1b\x21\x10\x1b\x21\x20\x1b\x2d\x02\x1b\x45\x01\x1b\x61\x01ORDER_NO_123 - REMOTE\x0a \x1b\x21\x00 \x1b\x61\x00";
//$data .="\x1b\x40"; //clear buffer
$data .= "\x1b\x45\x01PRINT\x1b\x45\x00' THIS \x0a ";
//$data .= "\x10\x14\x1\x01\x05";

//$data .= "\x1b\x70\x48\x10\x99";

//$data = "\012\012\012\012\012\012\012\012\012\033\151\010\004\001";
//$data.="\x1b\x21\x10 ANOTHER LINE"; //\x1b\x21\x10=double height

// Cut Paper
//$data .= "\x00\x1Bi\x00";
//$data .= "\x1d\x56\x01"; //full cut
//$data.="\012\012\012\033\151\010\004\001";

if (!fwrite($fp,$data)){
  die('writing failed');
}
fclose($fp);
/*
$texttoprint = "RECIPT TEXT \n NEXT LINE \n MORE STUFF";
$texttoprint = stripslashes($texttoprint);
echo"BBB---";
$fp = fsockopen("192.168.1.200", 9100, $errno, $errstr, 10);
//$fp = fsockopen("220.233.160.54",9100, $errno, $errstr, 10);
//$fp = fsockopen("posprinter1.entrydns.org",9100, $errno, $errstr, 10);
if (!$fp) {
echo "$errstr ($errno)<br />\n";die;
} else {
echo"i am here";

fwrite($fp, "\033\100");
$out = $texttoprint . "\r\n";
fwrite($fp, $out);
fwrite($fp, "\012\012\012\012\012\012\012\012\012\033\151\010\004\001");
fclose($fp);
}
echo"i am here";
*/
?>