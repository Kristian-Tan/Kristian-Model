<?php

require_once("KristianModel.php");

$connLocal = new mysqli("localhost", "root", "123", "smallnorthwind");

class Product extends KristianModel
{
    protected $_this_class_name = "Product";
    protected $_primary_key = "ProductID";
    protected $_conn_varname = "connLocal";
    protected $_table_name = "products";
    protected $_relation = array(
        "category" => array("Category", "CategoryID")
    );
    protected $_relations = array(
        "order_details" => array("OrderDetail", "ProductID")
    );
}

class Category extends KristianModel
{
    protected $_this_class_name = "Category";
    protected $_primary_key = "CategoryID";
    protected $_conn_varname = "connLocal";
    protected $_table_name = "categories";
    protected $_relations = array(
        "products" => array("Product", "CategoryID")
    );
}

class Order extends KristianModel
{
    protected $_this_class_name = "Order";
    protected $_primary_key = "OrderID";
    protected $_conn_varname = "connLocal";
    protected $_table_name = "orders";
    protected $_relations = array(
        "order_details" => array("OrderDetail", "OrderID")
    );
}

class OrderDetail extends KristianModel
{
    protected $_this_class_name = "OrderDetail";
    protected $_primary_key = array("OrderID", "ProductID");
    protected $_conn_varname = "connLocal";
    protected $_table_name = "orderdetails";
    protected $_relation = array(
        "product" => array("Product", "ProductID"),
        "order" => array("Order", "OrderID"),
    );
}


$productFactory = new Product("STATIC");
$categoryFactory = new Category("STATIC");
$orderFactory = new Order("STATIC");
$orderDetailFactory = new OrderDetail("STATIC");



//var_dump($productFactory->all());
//var_dump($categoryFactory->all());
//var_dump($orderFactory->all());
//var_dump($orderDetailFactory->all());

$product1 = $productFactory->find(1);

//var_dump($product1);
//var_dump($product1->get("ProductName"));
//var_dump($product1->getRelation("category"));
//var_dump($product1->getRelations("order_details"));