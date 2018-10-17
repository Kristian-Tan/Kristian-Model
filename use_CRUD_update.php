<?php


require_once("use.php");

$mobil = $factory->find(2);
$tipeLama = $mobil->get("tipe");
$mobil->set("tipe", $tipeLama . "-edit");
$mobil->save();

var_dump($mobil);

$confirmMobil = $factory->find($mobil->get("idmobil"));
var_dump($confirmMobil);