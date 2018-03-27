DistComp@home
==============

>A simple but powerful PHP library for distributed computing.  You can change your *foreach* loop script to *DistComp format* and run it by distributed computing.

>Using method:
>
>`Require("THE_SAVE_PATH/lib/distComp@home.lib.php");`
>
>`$ReturnValues = DistCompHome($Array, '$key=>$value', $script, '$ReturnValues_inScript', $Vars_inscripts, $np);`

>A normal foreach loop needs a hash array as keys and values and it will repeat the codes in order.  DistComp@home is able to divide the repeat works into several parts and run it simultaneously. In DistComp@home, we still need an array, keys, values, script. Be careful the variables in the script need to be defined as an array.  In addition, you can use `$np` to divide your works into the number of pieces you want.

>See complete expamples in `/demo`. You can compare the difference between `demo_no_distComp@home.php` and `demo_distComp@home.php`.

