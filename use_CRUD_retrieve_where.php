<?php


require_once("use.php");

$mobilArr = $factory->where("idmerk", "=", 1);
var_dump($mobilArr);


/*
also possible:

$mobilArr = $factory->where(
    array("idmerk", "tipe"),
    array("=", "<>"),
    array(1, "NewType")
);
// SELECT ... WHERE idmerk=1 AND tipe<>'NewType'

$mobilArr = $factory->where("idmerk", "like", "1_");
// SELECT ... WHERE idmerk LIKE "1_"

$mobilArr = $factory->where("idmerk", null, 1);
// SELECT ... WHERE idmerk=1

$mobilArr = $factory->rawQuery("
    SELECT m.*
    FROM
        mobil mbl
        INNER JOIN merk mrk ON mbl.idmerk=mrk.idmerk
    WHERE m.tipe LIKE 'MPV %' OR mrk.name LIKE 'H%'
    ORDER BY m.lebar DESC
    ;
");

*/