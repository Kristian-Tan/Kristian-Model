<?php

class PurchaseOrderDetail extends KristianModel
{
    protected $_this_class_name = "PurchaseOrderDetail";
    protected $_primary_key = "id";
    protected $_conn_varname = "conn";
    protected $_table_name = "purchase_order_details";
    protected $_is_incrementing = true;

    protected $_relation = array(
        "purchase_order" => array("PurchaseOrder", "purchase_order_id")
    );
}
