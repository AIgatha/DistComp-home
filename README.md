distComp@home
==============

A simple but powerful PHP library for distributed computing.  You can change your *foreach* loop script to a regulation format and run it by distributed computing.

Using method:
`$ReturnValues = DistCompHome($Array, '$key=>$value', $script, '$ReturnValues_inScript', $Vars_inscripts, $np);`

A normal foreach loop needs a hash array as keys and values and it will repeat the codes in order.  distComp@home is able to divide the repeat works into several parts and run it simultaneously. In distComp@home, we still need an array, keys, values, script. Be careful the variables in the script need to be defined as a array.  In addition, you can use `$np` to divide your works into the number of pieces you want.

The details of using method in the `/demo`. You can compare `demo_no_distComp@home.php` and `demo_distComp@home.php`.

