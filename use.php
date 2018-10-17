<?php

require_once("KristianModel.php");

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


$factory = new Mobil("STATIC");

// RETRIEVE
//var_dump($factory->all());
//var_dump($factory->find(2));
//var_dump($factory->where("tipe", "like", "%1%"));
//var_dump($factory->where("tipe", "=", "c-31-edit"));
//var_dump($factory->where("tipe", "like", "%"));

/*
// CREATE
$mobil = new Mobil();
$mobil->set("idmerk", "1");
$mobil->set("tipe", "2");
$mobil->set("panjang", "3");
$mobil->set("lebar", "4");
$mobil->set("tinggi", "5");
$mobil->save();
*/

/*
// UPDATE
$mobil = Mobil::find(3);
$mobil->set("tipe", "c-31-edit");
$mobil->save();
*/

/*
// DELETE
$mobil = Mobil::find(22);
$mobil->delete();
*/

/*
// RELATIONSHIP
$mobil = Mobil::find(3);
var_dump($mobil);
var_dump($mobil->getRelation("merk"));
*/

/*
// RELATIONSHIP
$merk = Merk::find(2);
var_dump($merk);
var_dump($merk->getRelations("mobils"));
*/




//var_dump($factory->getTableFields());
//$mobil = new Mobil();
//var_dump($mobil);