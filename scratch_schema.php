<?php
require __DIR__ . '/vendor/autoload.php';
 = require_once __DIR__ . '/bootstrap/app.php';
 = ->make(Illuminate\Contracts\Console\Kernel::class);
->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

 = DB::select('SHOW TABLES');
foreach ( as ) {
     = array_values((array))[0];
    echo 'TABLE: ' .  . PHP_EOL;
     = DB::select('SHOW COLUMNS FROM ' . );
    foreach ( as ) {
        echo '  ' . ->Field . ' (' . ->Type . ')' . PHP_EOL;
    }
}
