<?php

class Supplier extends KristianModel
{
    protected $_this_class_name = "Supplier";
    protected $_primary_key = "id";
    protected $_conn_varname = "conn";
    protected $_table_name = "suppliers";
    protected $_is_incrementing = true;

    protected $_relations = array(
        "purchase_orders" => array("PurchaseOrder", "supplier_id")
    );
}
