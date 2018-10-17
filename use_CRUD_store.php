<?php


require_once("use.php");

$mobil = new Mobil();
$tipeLama = $mobil->set("tipe", "baru");
$tipeLama = $mobil->set("idmerk", 1);
$mobil->save();

var_dump($mobil);

$confirmMobil = $factory->find($mobil->get("idmobil"));
var_dump($confirmMobil);