
<?php


$utc='2021-09-28T15:06:34.185Z';
$unix= str_replace(array('T','Z'),' ',$utc);
echo $unix;
echo "\n";
echo strtotime($unix);
echo "\n";