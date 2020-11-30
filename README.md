# Kristian's lightweight ORM

Kristian's self made ORM, inspired by Laravel's Eloquent ORM.
Why? because Laravel eats up inodes so it can't be used in free hosting which limit inodes to 2000 only, also because Eloquent can't have multiple models that have relations but in different connections

##### Feature
- Easily support Admin CRUD feature (create, retrieve, update, delete)
- Timestamp (optional, support custom column name)
- One-to-many relationship (support custom foreign key column name) and it's reverse
- Support table with composite key (not even Eloquent support it out of the box)
- Support one primary key with auto increment (cannot have a table with multiple auto increment key, or an auto increment key as composite)
- Custom primary key name
- Table null value
- mysqli bind_param (parameters not sanitized by escaping but by mysqli statement)
- Retrieve table column names can be shortened by defining it in model (won't need to run SHOW COLUMNS every time)
- Support using multiple different mysqli connection (ex: table 'cars' is in connection $conn1, table 'brands' is in connection $conn2, but those two models can still have relationship)
- Only one method to insert and to update (called save())
- Set data from array / create object from array (ex: $mobil = $factory->createFromArray($_POST))
- LIMIT clause
- ORDER BY clause
- IN / NOT IN operator
- SELECT only a few columns (not all)

##### Not Implemented Yet (Might be Supported in the Future)
- Mass update / mass delete / mass insert
- Aggregation function (like SUM, COUNT, AVG)
- Searching with operator other than AND (currently where() method only support 'AND')

##### Will not be implemented
- Join query (workaround: use rawQuery method) or any query builder
- Many-to-many relationship with pivot table (workaround: make the pivot table it's own class)
- Soft delete (workaround: just set the property of is_deleted to 1, and when searching just include use where 'is_deleted <> 1')
- Method chaining (gimmick)
- Using static to perform factory operation (not supported in order to increase the number of php version this ORM support)



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

// more programmer friendly way to call where() method for multiple parameters:
$mobilArr = $factory->where([
    ["tipe", "<>", "NewType"],   // column, operator, value
    ["idmerk", "1"],   // operator set to "="
	["tipe", "NOT IN", ["Tipe1","Tipe2","Tipe3"] ]   // supply array of strings for IN operator
]);
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
