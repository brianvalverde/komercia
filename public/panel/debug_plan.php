<?php
session_start();
if(empty($_SESSION['uid'])){die('No sesión');}
require_once '/var/www/komercia/config/firebase.php';
$uid=$_SESSION['uid'];
$doc=firestoreRequest('GET',"comerciantes/{$uid}");
$f=$doc['fields']??[];
echo "UID: $uid\n";
echo "plan field: ".json_encode($f['plan']??'NO EXISTE')."\n";
echo "plan_activo: ".json_encode($f['plan_activo']??'NO EXISTE')."\n";
echo "Full fields keys: ".implode(', ',array_keys($f))."\n";
