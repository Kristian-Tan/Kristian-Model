<?php

require_once("vendor/autoload.php");
require_once("KristianModel.php");
foreach (glob("tests/models/*") as $modelFileName)
{
    require_once($modelFileName);
}

use PHPUnit\Framework\TestCase;

class SelectTest extends TestCase
{
    protected static $connHost = "localhost";
    protected static $connUser = "root";
    protected static $connPass = "123";
    protected static $conn;
    protected static $dbName = "test_kristian_orm";

    protected static $is_setup_database = false;
    protected static $is_teardown_database = false;

    public static function setUpBeforeClass(): void
    {
        if(!empty(getenv("MYSQL_HOST"))) SelectTest::$connHost = getenv("MYSQL_HOST");
        if(!empty(getenv("MYSQL_USER"))) SelectTest::$connUser = getenv("MYSQL_USER");
        if(!empty(getenv("MYSQL_PASS"))) SelectTest::$connPass = getenv("MYSQL_PASS");

        if(!empty(getenv("SELECT_TEST_dbname"))) SelectTest::$dbName = getenv("SELECT_TEST_dbname");

        if(!empty(getenv("SELECT_TEST_is_setup_database")))
        {
            $value = getenv("SELECT_TEST_is_setup_database");
            if(empty($value) || $value == "false" || $value == "off" || $value == "0" || $value == false)
                SelectTest::$is_setup_database = false;
            else
                SelectTest::$is_setup_database = true;
        }
        if(!empty(getenv("SELECT_TEST_is_teardown_database")))
        {
            $value = getenv("SELECT_TEST_is_teardown_database");
            if(empty($value) || $value == "false" || $value == "off" || $value == "0" || $value == false)
                SelectTest::$is_teardown_database = false;
            else
                SelectTest::$is_teardown_database = true;
        }

        //throw new \Exception("Error Processing Request", 1);


        if(SelectTest::$is_setup_database)
        {
            fwrite(STDOUT, "START Creating database \n");
            // define connection
            $connSetup = new mysqli(SelectTest::$connHost, SelectTest::$connUser, SelectTest::$connPass);
            //SelectTest::$dbName = "test_kristian_orm_" . random_int(1, 999);
            //SelectTest::$dbName = "test_kristian_orm";
            // create db (drop first if already exist)
            $dbExists = $connSetup->select_db(SelectTest::$dbName);
            if($dbExists) $connSetup->query("DROP DATABASE " . SelectTest::$dbName);
            $connSetup->query("CREATE DATABASE " . SelectTest::$dbName);
            $connSetup->select_db(SelectTest::$dbName);
            // import database
            $sqlStructure = file_get_contents("tests/northwind/northwind_structure_nodb.sql");
            $sqlData = file_get_contents("tests/northwind/northwind_data_nodb.sql");
            $sql =
                $sqlStructure . "\r\n\r\n" .
                "SET foreign_key_checks = 0;"."\r\n\r\n".
                $sqlData."\r\n\r\n".
                "SET foreign_key_checks = 1;"
            ;
            $sqlCommandSuccess = $connSetup->multi_query($sql);
            while ($connSetup->next_result()) { }
            $connSetup->close();
            fwrite(STDOUT, "END Creating database \n");
        }

        // set new connection to be used when testing
        SelectTest::$conn = new mysqli(SelectTest::$connHost, SelectTest::$connUser, SelectTest::$connPass, SelectTest::$dbName);
        $GLOBALS["conn"] = SelectTest::$conn;


    }
    public static function tearDownAfterClass(): void
    {
        if(SelectTest::$is_teardown_database)
        {
            fwrite(STDOUT, "Dropping database \n");

            $connTeardown = new mysqli(SelectTest::$connHost, SelectTest::$connUser, SelectTest::$connPass, SelectTest::$dbName);
            $sqlCommandSuccess = $connTeardown->query("DROP DATABASE " . SelectTest::$dbName);
            $connTeardown->close();
        }
    }

    // simple auxiliary tests
    public function testGetAndSet()
    {
        $supplier = new Supplier();
        $supplier->set("first_name", "NewValueHere");
        $this->assertEquals("NewValueHere", $supplier->get("first_name"));
        $this->assertEquals(array("id", "company", "last_name", "first_name", "email_address", "job_title", "business_phone", "home_phone", "mobile_phone", "fax_number", "address", "city", "state_province", "zip_postal_code", "country_region", "web_page", "notes", "attachments"), array_keys($supplier->get()));
    }

    public function testGetTableFields()
    {
        $factoryPOS = new PurchaseOrderStatus("STATIC");
        $fields = $factoryPOS->getTableFields();
        $this->assertEquals(array("id", "status"), $fields);
    }

    public function testCreateFromArray()
    {
        $factorySupplier = new Supplier("STATIC");
        $arr = array("company"=>"NewValueHere", "last_name"=>"NewValueLast", "first_name"=>"NewValueFirst");
        $arrKeys = array("company", "last_name", "first_name");

        $supplier1 = $factorySupplier->createFromArray($arr);
        $this->assertEquals("NewValueHere", $supplier1->get("company"));
        $this->assertEquals("NewValueLast", $supplier1->get("last_name"));
        $this->assertEquals("NewValueFirst", $supplier1->get("first_name"));

        $supplier2 = $factorySupplier->createFromArray($arr, $arrKeys);
        $this->assertEquals("NewValueHere", $supplier2->get("company"));
        $this->assertEquals("NewValueLast", $supplier2->get("last_name"));
        $this->assertEquals("NewValueFirst", $supplier2->get("first_name"));

        $supplier3 = new Supplier();
        $supplier3->setDataFromArray($arr, $arrKeys, null);
        $this->assertEquals("NewValueHere", $supplier3->get("company"));
        $this->assertEquals("NewValueLast", $supplier3->get("last_name"));
        $this->assertEquals("NewValueFirst", $supplier3->get("first_name"));

        $supplier4 = new Supplier();
        $supplier4->setDataFromArray($arr, null, $arrKeys);
        $this->assertEquals("NewValueHere", $supplier4->get("company"));
        $this->assertEquals("NewValueLast", $supplier4->get("last_name"));
        $this->assertEquals("NewValueFirst", $supplier4->get("first_name"));
    }

    // real SELECT test
    public function testSelectAll()
    {
        $factorySupplier = new Supplier("STATIC");
        $arrSupplier = $factorySupplier->all();
        //fwrite(STDOUT, $GLOBALS["conn"]->error . " \n");

        $this->assertEquals(10, count($arrSupplier));
    }

    public function testSelectAllOrderBy()
    {
        $factorySupplier = new Supplier("STATIC");
        $arrSupplier = $factorySupplier->all($rawOrderBy="first_name ASC");
        //var_dump($arrSupplier);
        $this->assertEquals("Amaya", $arrSupplier[0]->get("first_name"));
        $this->assertEquals("Madeleine", $arrSupplier[5]->get("first_name"));
        $this->assertEquals("Stuart", $arrSupplier[9]->get("first_name"));
        $arrSupplier = $factorySupplier->all($rawOrderBy="first_name DESC");
        //var_dump($arrSupplier);
        $this->assertEquals("Amaya", $arrSupplier[9]->get("first_name"));
        $this->assertEquals("Madeleine", $arrSupplier[4]->get("first_name"));
        $this->assertEquals("Stuart", $arrSupplier[0]->get("first_name"));
    }

    public function testSelectAllLimit()
    {
        $factorySupplier = new Supplier("STATIC");
        $arrSupplier = $factorySupplier->all($rawOrderBy=null, $limit=4);
        $this->assertEquals(4, count($arrSupplier));
    }

    public function testSelectAllOffset()
    {
        $factorySupplier = new Supplier("STATIC");
        $arrSupplier = $factorySupplier->all($rawOrderBy="first_name ASC", $limit=1, $offset=5);
        $this->assertEquals(1, count($arrSupplier));
        $this->assertEquals("Madeleine", $arrSupplier[0]->get("first_name"));
    }

    public function testSelectFind()
    {
        $factorySupplier = new Supplier("STATIC");
        $supplier = $factorySupplier->find(5);
        $this->assertEquals("Hernandez-Echevarria", $supplier->get("last_name"));

        $factoryEmployeePrivilege = new EmployeePrivilege("STATIC");
        $ep = $factoryEmployeePrivilege->find(array(2,2));
        $this->assertEquals(2, $ep->get("employee_id"));
        $this->assertEquals(2, $ep->get("privilege_id"));
    }

    public function testSelectWhere()
    {
        $factorySupplier = new Supplier("STATIC");

        $arrSupplier = $factorySupplier->where("id", "<", 5);
        $this->assertEquals(4, count($arrSupplier));


        $arrSupplier = $factorySupplier->where("id", null, 5);
        $this->assertEquals(1, count($arrSupplier));
        $this->assertEquals("Hernandez-Echevarria", $arrSupplier[0]->get("last_name"));

        $arrSupplier = $factorySupplier->where(
            array("id", "job_title", "last_name"),
            array("<", "=", "<>"),
            array(5, "Sales Manager", "Weiler")
        );
        $this->assertEquals(1, count($arrSupplier));
        $this->assertEquals("Andersen", $arrSupplier[0]->get("last_name"));

        $arrSupplier = $factorySupplier->where("job_title", "IN", array("Sales Manager", "Sales Representative"));
        $this->assertEquals(7, count($arrSupplier));

        $arrSupplier = $factorySupplier->where(
            array("job_title", "id"),
            array("IN", "<"),
            array( array("Sales Manager", "Sales Representative"), 5)
        );
        $this->assertEquals(3, count($arrSupplier));

        // TODO after this
        // uji orderby, limit, offset
        $arrSupplier = $factorySupplier->where(
            "job_title", "IN", array("Sales Manager", "Sales Representative"),
            $rawOrderBy="id DESC", $limit=2, $offset=3
        );
        $this->assertEquals(2, count($arrSupplier));
        $this->assertEquals("Amaya", $arrSupplier[0]->get("first_name"));
        $this->assertEquals("Madeleine", $arrSupplier[1]->get("first_name"));

    }

    public function testSelectWhereImproved()
    {
        $factorySupplier = new Supplier("STATIC");

        // $argFilters bisa berisi: ["column", ">=", "value"]
        $arrSupplier = $factorySupplier->where_i(
            array("id", "<", 5)
        );
        $this->assertEquals(4, count($arrSupplier));

        // $argFilters bisa berisi: ["column", "value"]  // dianggap operator "="
        $arrSupplier = $factorySupplier->where_i(
            array("id", 5)
        );
        $this->assertEquals(1, count($arrSupplier));
        $this->assertEquals("Hernandez-Echevarria", $arrSupplier[0]->get("last_name"));

        // $argFilters bisa berisi: array( ["column1", ">=", "value1"], ["column2", "=", "value2"], ["column3", "<>", "value3"] )
        $arrSupplier = $factorySupplier->where_i(
            array(
                array("id", "<", 5),
                array("job_title", "=", "Sales Manager"),
                array("last_name", "<>", "Weiler")
            )
        );
        $this->assertEquals(1, count($arrSupplier));
        $this->assertEquals("Andersen", $arrSupplier[0]->get("last_name"));

        // $argFilters bisa berisi: ["column", "IN", ["value1","value2","value3"] ]
        $arrSupplier = $factorySupplier->where_i(
            array("job_title", "IN", array("Sales Manager", "Sales Representative"))
        );
        $this->assertEquals(7, count($arrSupplier));

        // $argFilters bisa berisi kombinasi ["column2", "=", "value2"] DAN ["column", "IN", ["value1","value2","value3"] ]
        $arrSupplier = $factorySupplier->where_i(
            array(
                array("job_title", "IN", array("Sales Manager", "Sales Representative")),
                array("id", "<", 5)
            )
        );
        $this->assertEquals(3, count($arrSupplier));

        // uji orderby, limit, offset
        $arrSupplier = $factorySupplier->where_i(
            array("job_title", "IN", array("Sales Manager", "Sales Representative")),
            $rawOrderBy="id DESC", $limit=2, $offset=3
        );
        $this->assertEquals(2, count($arrSupplier));
        $this->assertEquals("Amaya", $arrSupplier[0]->get("first_name"));
        $this->assertEquals("Madeleine", $arrSupplier[1]->get("first_name"));

    }

    public function testRawQuery()
    {
        $factorySupplier = new Supplier("STATIC");

        $arrSupplier = $factorySupplier->rawQuery("SELECT * FROM suppliers WHERE id < 5");
        $this->assertEquals(4, count($arrSupplier));

        $arrSupplier = $factorySupplier->rawQuery("SELECT * FROM suppliers WHERE id = 5");
        $this->assertEquals(1, count($arrSupplier));
        $this->assertEquals("Hernandez-Echevarria", $arrSupplier[0]->get("last_name"));

        $arrSupplier = $factorySupplier->rawQuery("SELECT * FROM suppliers WHERE
            id < 5 AND
            job_title = 'Sales Manager' AND
            last_name <> 'Weiler'
        ");
        $this->assertEquals(1, count($arrSupplier));
        $this->assertEquals("Andersen", $arrSupplier[0]->get("last_name"));

        $arrSupplier = $factorySupplier->rawQuery("SELECT * FROM suppliers WHERE job_title IN ('Sales Manager', 'Sales Representative') ");
        $this->assertEquals(7, count($arrSupplier));

        $arrSupplier = $factorySupplier->rawQuery("SELECT * FROM suppliers WHERE id < 5 AND job_title IN ('Sales Manager', 'Sales Representative')");
        $this->assertEquals(3, count($arrSupplier));

        // uji orderby, limit, offset
        $arrSupplier = $factorySupplier->rawQuery("SELECT * FROM suppliers WHERE job_title IN ('Sales Manager', 'Sales Representative') ORDER BY id DESC LIMIT 3, 2");
        $this->assertEquals(2, count($arrSupplier));
        $this->assertEquals("Amaya", $arrSupplier[0]->get("first_name"));
        $this->assertEquals("Madeleine", $arrSupplier[1]->get("first_name"));
    }

    public function testGetRelation()
    {
        $factoryPO = new PurchaseOrder("STATIC");
        $po1 = $factoryPO->find(100);
        $supplier = $po1->getRelation("supplier");

        $this->assertEquals("Cornelia", $supplier->get("first_name"));
        $this->assertEquals($po1->get("supplier_id"), $supplier->get("id"));
        $this->assertEquals(2, $supplier->get("id"));
    }

    public function testGetRelations()
    {
        $factorySupplier = new Supplier("STATIC");
        $supplier = $factorySupplier->find(2);

        $arrPOs = $supplier->getRelations("purchase_orders");

        $this->assertEquals(9, count($arrPOs));
        $this->assertEquals($supplier->get("id"), $arrPOs[0]->get("supplier_id"));
        $this->assertEquals(2, $arrPOs[0]->get("supplier_id"));
    }


}

