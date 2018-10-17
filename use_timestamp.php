<?php

require_once("KristianModel.php");

$connLocal = new mysqli("localhost", "root", "123", "project_akhir_pweb_orm");

class Mobil extends KristianModel
{
    protected $_this_class_name = "Mobil";
    protected $_primary_key = "idmobil";
    protected $_conn_varname = "connLocal";
    protected $_table_name = "mobil";
    protected $_relation = array(
        "merk" => array("Merk", "idmerk")
    );
    protected $_timestamp_created_at = "created_at";
    protected $_timestamp_updated_at = "updated_at";
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
    protected $_timestamp_created_at = "dibuat_pada";
    protected $_timestamp_updated_at = "diubah_pada";
}


$factory = new Mobil("STATIC");

// RETRIEVE
//var_dump($factory->all());
//var_dump($factory->find(2));
//var_dump($factory->where("tipe", "like", "%1%"));
//var_dump($factory->where("tipe", "=", "c-31-edit"));
//var_dump($factory->where("tipe", "like", "%"));

// CREATE
/*
$merk = new Merk();
$merk->set("nama", "Merkku1");
$merk->save();

$mobil = new Mobil();
$mobil->set("idmerk", $merk->get("idmerk"));
$mobil->set("tipe", "2");
$mobil->set("panjang", "3");
$mobil->set("lebar", "4");
$mobil->set("tinggi", "5");
$mobil->save();
*/

// UPDATE
$mobil = $factory->find(30);
$mobil->set("tipe", "c-31-edit");
$mobil->save();
var_dump($mobil->getRelation("merk"));

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