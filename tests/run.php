<?php
require dirname(__DIR__) . '/vendor/autoload.php';
use App\Eligibility;
use App\Security;
$config = require dirname(__DIR__) . '/config/app.php';
$tests = [];
$tests['age calculation before birthday'] = Eligibility::ageOn('2014-10-09','2026-10-08') === 11;
$tests['nasal eligible elementary child'] = Eligibility::validate(['birth_date'=>'2018-04-01','appointment_date'=>'2026-10-08','appointment_time'=>'17:00','vaccine_method'=>'nasal','dose_no'=>1],$config) === [];
$tests['nasal rejects under two'] = count(Eligibility::validate(['birth_date'=>'2025-01-01','appointment_date'=>'2026-10-08','appointment_time'=>'17:00','vaccine_method'=>'nasal','dose_no'=>1],$config)) > 0;
$tests['adult weekday is accepted'] = Eligibility::validate(['birth_date'=>'1980-01-01','appointment_date'=>'2026-10-01','appointment_time'=>'09:00','vaccine_method'=>'injection','dose_no'=>1],$config) === [];
$tests['adult weekend is rejected'] = count(Eligibility::validate(['birth_date'=>'1980-01-01','appointment_date'=>'2026-10-03','appointment_time'=>'09:00','vaccine_method'=>'injection','dose_no'=>1],$config)) > 0;
$tests['excel formula is neutralized'] = Security::excelSafe('=1+1') === "'=1+1";
foreach($tests as $name=>$ok) echo ($ok?'PASS':'FAIL')." {$name}\n";
exit(in_array(false,$tests,true)?1:0);
