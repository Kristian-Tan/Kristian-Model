<?php


require_once("use.php");

$mobil = $factory->find(2);
$merk = $mobil->getRelation("merk");

var_dump($merk);