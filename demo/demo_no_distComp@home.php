<?php
$power   = 3;
$Numbers = Range(1, 1000);

foreach ($Numbers as $key=>$number)
{
  uSleep(0.001E6); //wait for a short time on purpose, pretending a complicated computation here
  $PoweredValues[$number] = Pow($number, $power);
} //foreach $Numbers

Print_r($PoweredValues);
?>