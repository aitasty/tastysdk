<?php  
require_once __DIR__ . '/vendor/autoload.php';  

use Aitasty\Core;
use AitastyTest\Core as TestCore;


$c = new Core;
$c1 = $c->test();
var_dump( $c1 );
echo "<br />";
$d = new TestCore;
$d1 = $d->test();
var_dump( $d1 );
