# Kristian's lightweight ORM

Usage: 

```php
<?php

## // --- loading class ---
require_once("KristianModel.php");
```




## // --- defining model ---
```php
<?php

class Mobil extends KristianModel
{
    protected $_this_class_name = "Mobil";
	// type: string
	// value must equal to class name of this model
	
    protected $_primary_key = "idmobil";
	// type: array of string / string
	// value is the primary key column on mysql table
	
    protected $_conn_varname = "connLocal";
	// type: string
	// value is the variable name of mysqli connection object that this model is stored in
	// example: in this example we have conn.php = <?php $connLocal = new mysqli("localhost", "root", "123", "project_akhir_pweb"); ?>
	
    protected $_table_name = "mobil";
	// type: string
	// value is the table name on mysql database
}
class Merk extends KristianModel
{
    protected $_this_class_name = "Merk";
    protected $_primary_key = "idmerk";
    protected $_conn_varname = "connLocal";
    protected $_table_name = "merk";
}
```




## // --- CRUD operations (create, retrieve, update, delete) ---

### // model factory
```php
<?php

// model factory is like a static class for that object (being made into a model factory object instead to support lower version of php)

// creating model factory
$mobilFactory = new Mobil("STATIC");
$merkFactory = new Merk("STATIC");
```


### // retrieve operation

```php
<?php

// retrieve many (all objects)
$mobilArr = $mobilFactory->all();
foreach($mobilArr as $mobil) // $arrayOfMobil is an array of Mobil objects
{
	echo $mobil->get("tipe");
}

// retrieve one based on it's id
$mobil = $factory->find(2);
echo $mobil->get("tipe"); // $mobil is an object of class Mobil

// retrieve many based on query
$mobilArr = $mobilFactory->where("idmerk", "=", 1);
foreach($mobilArr as $mobil) // $arrayOfMobil is an array of Mobil objects
{
	echo $mobil->get("tipe");
}

// other usage of where() method:
$mobilArr = $factory->where(
    array("idmerk", "tipe"),
    array("=", "<>"),
    array(1, "NewType")
);
$mobilArr = $factory->where("idmerk", "like", "1_");
$mobilArr = $factory->where("idmerk", null, 1);
$mobilArr = $factory->rawQuery("
    SELECT m.*
    FROM
        mobil mbl
        INNER JOIN merk mrk ON mbl.idmerk=mrk.idmerk
    WHERE m.tipe LIKE 'MPV %' OR mrk.name LIKE 'Hond%'
    ORDER BY m.lebar DESC
    ;
");
```


### // create operation
```php
<?php

$mobil = new Mobil();
$mobil->set("tipe", "GLX-312");
$mobil->set("idmerk", 1);
$mobil->save();
```



### // update operation
```php
<?php

$mobil = $mobilFactory->find(2);
$mobil->set("tipe", "All New GLX-312");
$mobil->save();
```



### // delete operation
```php
<?php

$mobil = $mobilFactory->find(2);
$mobil->delete();
```





## // --- relationship ---
// relationship can only be one-to-many or many-to-one (do not support many-to-many relationship)
```php
<?php

class Mobil extends KristianModel
{
	// ...
    protected $_relation = array(
        "merk" => array("Merk", "idmerk")
    );
	// type: array, format of content (can be multiple): ["relation1_name" => "relation1_class", "relation1_foreignKey"]
	// type: array, format of content (can be multiple): ["relation1_name" => "relation1_class", ["relation1_foreignKey1", "relation1_foreignKey2"]]
}
class Merk extends KristianModel
{
	// ...
    protected $_relations = array(
        "mobils" => array("Mobil", "idmerk")
    );
}
```




### // getting object of its relationship
```php
<?php

// many to one
$mobil = $mobilFactory->find(2);
$merk = $mobil->getRelation("merk");
echo $merk->get("nama"); // $merk is an object of class Merk

// one to many
$merk = $merkFactory->find(2);
$arrayOfMobil = $merk->getRelations("mobils");
foreach($arrayOfMobil as $mobil) // $arrayOfMobil is an array of Mobil objects
{
	echo $mobil->get("tipe");
}
```

### // setting up many-to-many relationship
// to support many-to-many relationship, create a new helper model class for that relationship table's tbl1_has_tbl2
// example: one product can be bought in many orders, one orders may contain many products (assume db = smallnorthwind) 
//     ==>> then create OrderDetail model!

```php
<?php

class Order extends KristianModel
{
    // ...
    protected $_relations = array(
        "order_details" => array("OrderDetail", "OrderID")
    );
}
class OrderDetail extends KristianModel
{
    // ...
    protected $_relation = array(
        "order" => array("Order", "OrderID"),
        "product" => array("Product", "ProductID"),
    );
}
class Product extends KristianModel
{
    // ...
    protected $_relations = array(
        "order_details" => array("OrderDetail", "ProductID")
    );
}
```









## // smallnorthwind example
```php
<?php

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

var_dump($productFactory->all()); // get array of all products
var_dump($categoryFactory->all()); // get array of all categories
var_dump($orderFactory->all()); // get array of all orders
var_dump($orderDetailFactory->all()); // get array of all order details

$product1 = $productFactory->find(1); // get specific product

//var_dump($product1); // see (object Product) member of product1
//var_dump($product1->get("ProductName")); // see (string) name of product1
//var_dump($product1->getRelation("category")); // see (object Category) of product1
//var_dump($product1->getRelations("order_details")); // see (array of object OrderDetail) of product1
```