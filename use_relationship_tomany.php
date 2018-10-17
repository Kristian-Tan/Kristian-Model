<?php


require_once("use.php");

$merkFactory = new Merk("STATIC");
$merk = $merkFactory->find(2);
$arrayOfMobil = $merk->getRelations("mobils");

var_dump($arrayOfMobil);