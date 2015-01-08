<?php
/*
date_default_timezone_set('US/Eastern');
date_default_timezone_set('Asia/Calcutta');

$script_tz = date_default_timezone_get();

if (strcmp($script_tz, ini_get('date.timezone'))){
    echo 'Script timezone differs from ini-set timezone.';
} else {
    echo 'Script timezone and ini-set timezone match.';
}
*/

echo "FOR PIZZABOY<br>";
echo date("Y-m-d h:i:s A",time());
echo "<br>";
echo date("M j, Y H:i:s O",time())."\n";

//echo phpinfo();
?>