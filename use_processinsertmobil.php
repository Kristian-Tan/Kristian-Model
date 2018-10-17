<?php

require_once("KristianModel_primitif.php");

$connLocal = new mysqli("localhost", "root", "123", "project_akhir_pweb");


class Mobil extends KristianModel
{
    protected $_this_class_name = "Mobil";
    protected $_primary_key = "idmobil";
    protected $_conn_varname = "connLocal";
    protected $_table_name = "mobil";
    protected $_relation = array(
        "merk" => array("Merk", "idmerk")
    );
}

class Merk extends KristianModel
{
    protected $_this_class_name = "Merk";
    protected $_primary_key = "idmerk";
    protected $_conn_varname = "connLocal";
    protected $_table_name = "merk";
    protected $_relations = array(
        "mobils" => array("Mobil", "idmerk")
    );
}

$factory = new Mobil();
//$mobil = $factory->createFromArray($_POST, array("idmobil", "idmerk", "tipe"));
$mobil = $factory->createFromArray($_POST, array("idmerk", "tipe"));
$result = $mobil->save();


if($result == true)
{
    echo "sukses menyimpan mobil!";
}



