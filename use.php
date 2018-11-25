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
    protected $_table_fields = array(
        "idmobil", "idmerk", "tipe", "panjang", "lebar", "tinggi",
        "jarak_sumbu_roda", "radius_putar", "harga_min", "harga_max",
        "kapasitas_mesin", "kapasitas_tangki", "ukuran_velg", "ukuran_roda"
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
    protected $_table_fields = array(
        "idmerk", "nama"
    );
}


$factory = new Mobil("STATIC");
$factoryMerk = new Merk("STATIC");

// RETRIEVE
//var_dump($factory->all());
//var_dump($factory->find(2));
//var_dump($factory->where("tipe", "like", "%1%"));
//var_dump($factory->where("tipe", "=", "c-31-edit"));
//var_dump($factory->where("tipe", "like", "%"));
/*
var_dump($factory->where(
    array("tipe", "idmobil"),
    array("=", "<>"),
    array("c-31-edit", "20")
));
//*/

/*
// CREATE
$mobil = new Mobil();
$mobil->set("idmerk", "1");
$mobil->set("tipe", "BARU SANITIZED BINDED");
$mobil->set("panjang", "33333");
$mobil->set("lebar", "444444");
$mobil->set("tinggi", "5555555");
$mobil->save();
var_dump($mobil);
//*/

/*
// UPDATE
$mobil = $factory->find(26);
$mobil->set("tipe", " ' OR 1=1 OR ' ");
$mobil->save();
var_dump($mobil);
//*/

/*
// DELETE
$mobil = $factory->find(26);
$mobil->delete();
//*/

/*
// RELATIONSHIP
$mobil = $factory->find(3);
var_dump($mobil);
var_dump($mobil->getRelation("merk")->get("nama"));
//*/

/*
// RELATIONSHIP
$merk = $factoryMerk->find(2);
var_dump($merk);
var_dump($merk->getRelations("mobils"));
//*/




//var_dump($factory->getTableFields());
//$mobil = new Mobil();
//var_dump($mobil);