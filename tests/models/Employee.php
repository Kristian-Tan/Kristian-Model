<?php

class Employee extends KristianModel
{
    protected $_this_class_name = "Employee";
    protected $_primary_key = "id";
    protected $_conn_varname = "conn";
    protected $_table_name = "employees";
    protected $_is_incrementing = true;

    protected $_relations = array(
        "purchase_orders" => array("PurchaseOrder", "created_by")
    );
}
