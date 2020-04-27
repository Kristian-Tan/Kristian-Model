<?php

class PurchaseOrderStatus extends KristianModel
{
    protected $_this_class_name = "PurchaseOrderStatus";
    protected $_primary_key = "id";
    protected $_conn_varname = "conn";
    protected $_table_name = "purchase_order_status";
    protected $_is_incrementing = false;

    protected $_relations = array(
        "purchase_orders" => array("PurchaseOrder", "status_id")
    );
}
