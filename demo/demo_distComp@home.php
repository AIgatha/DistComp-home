<?php
Require("../lib/distComp@home.lib.php");

$power   = 3;
$Numbers = Range(1, 1000);

$script = '
  uSleep(0.001E6); //wait for a short time on purpose, pretending a complicated computation here
  $PoweredValues[$number] = Pow($number, $power);
';

$np             = 10;
$Vars['$power'] = $power;
$PoweredValues  = DistCompHome($Numbers, '$key=>$number', $script, '$PoweredValues', $Vars, $np);

Print_r($PoweredValues);
?>