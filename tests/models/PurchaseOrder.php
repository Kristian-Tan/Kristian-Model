<?php

class PurchaseOrder extends KristianModel
{
    protected $_this_class_name = "PurchaseOrder";
    protected $_primary_key = "id";
    protected $_conn_varname = "conn";
    protected $_table_name = "purchase_orders";
    protected $_is_incrementing = true;

    protected $_relation = array(
        "supplier" => array("Supplier", "supplier_id"),
        "employee" => array("Employee", "created_by"),
        "status" => array("PurchaseOrderStatus", "status_id")
    );

    protected $_relations = array(
        "details" => array("PurchaseOrderDetail", "purchase_order_id")
    );
}
