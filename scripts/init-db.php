<?php

require dirname(__DIR__) . '/vendor/autoload.php';
App\Database::connection();
echo "Database initialized.\n";
