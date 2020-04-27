<?php

require_once("vendor/autoload.php");
require_once("KristianModel.php");
foreach (glob("tests/models/*") as $modelFileName)
{
    require_once($modelFileName);
}

use PHPUnit\Framework\TestCase;

class SaveTest extends TestCase
{
    protected static $connHost = "localhost";
    protected static $connUser = "root";
    protected static $connPass = "123";
    protected static $conn;
    protected static $dbNameSource = "northwind";
    protected static $dbName = "test_kristian_orm_save";

    protected static $is_setup_database_source = false;
    protected static $is_setup_database = true;
    protected static $is_teardown_database = false;

    public static function setUpBeforeClass(): void
    {
        if(SaveTest::$is_setup_database_source)
        {
            fwrite(STDOUT, "START Importing original database \n");
            // define connection
            $connSetup = new mysqli(SaveTest::$connHost, SaveTest::$connUser, SaveTest::$connPass);
            // create db (drop first if already exist)
            $dbExists = $connSetup->select_db(SaveTest::$dbNameSource);
            if($dbExists) $connSetup->query("DROP DATABASE " . SaveTest::$dbNameSource);
            $connSetup->query("CREATE DATABASE " . SaveTest::$dbNameSource);
            $connSetup->select_db(SaveTest::$dbNameSource);
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
            fwrite(STDOUT, "END Importing original database \n");
        }
        if(SaveTest::$is_setup_database)
        {
            fwrite(STDOUT, "START Setting up temporary testing database \n");
            // define connection
            $connSetup = new mysqli(SaveTest::$connHost, SaveTest::$connUser, SaveTest::$connPass);
            // create db (drop first if already exist)
            $dbExists = $connSetup->select_db(SaveTest::$dbName);
            if($dbExists) $connSetup->query("DROP DATABASE " . SaveTest::$dbName);
            $connSetup->query("CREATE DATABASE " . SaveTest::$dbName);

            // import database
            $connSetup->query("SET foreign_key_checks = 0");
            $arrTables = array("customers", "employee_privileges", "employees", "inventory_transaction_types", "inventory_transactions", "invoices", "order_details", "order_details_status", "orders", "orders_status", "orders_tax_status", "privileges", "products", "purchase_order_details", "purchase_order_status", "purchase_orders", "sales_reports", "shippers", "strings", "suppliers");
            foreach ($arrTables as $key => $table)
            {
                //$connSetup->query("CREATE TABLE ".SaveTest::$dbName.".".$table." AS SELECT * FROM ".SaveTest::$dbNameSource.".".$table);
                $connSetup->query("CREATE TABLE ".SaveTest::$dbName.".".$table." LIKE ".SaveTest::$dbNameSource.".".$table);
                $connSetup->query("INSERT ".SaveTest::$dbName.".".$table." SELECT * FROM ".SaveTest::$dbNameSource.".".$table);
            }
            $connSetup->query("SET foreign_key_checks = 1");
            fwrite(STDOUT, "END Setting up temporary testing database \n");
        }

        // set new connection to be used when testing
        SaveTest::$conn = new mysqli(SaveTest::$connHost, SaveTest::$connUser, SaveTest::$connPass, SaveTest::$dbName);
        $GLOBALS["conn"] = SaveTest::$conn;


    }
    public static function tearDownAfterClass(): void
    {
        if(SaveTest::$is_teardown_database)
        {
            fwrite(STDOUT, "Dropping database \n");

            $connTeardown = new mysqli(SaveTest::$connHost, SaveTest::$connUser, SaveTest::$connPass, SaveTest::$dbName);
            $sqlCommandSuccess = $connTeardown->query("DROP DATABASE " . SaveTest::$dbName);
            $connTeardown->close();
        }
    }

    public function testSaveUpdate()
    {
        $factorySupplier = new Supplier("STATIC");
        $supplier = $factorySupplier->find(2);

        $supplier->set("first_name", "NewValueHere");
        $supplier->save();


        $factorySupplier1 = new Supplier("STATIC");
        $supplier1 = $factorySupplier1->find(2);

        $this->assertEquals("NewValueHere", $supplier1->get("first_name"));
    }

    public function testRawQueryUpdate()
    {
        $factorySupplier = new Supplier("STATIC");
        $factorySupplier->rawNonQuery("UPDATE suppliers SET first_name = 'NewValueHere3' WHERE id=3 ");

        $factorySupplier1 = new Supplier("STATIC");
        $supplier1 = $factorySupplier1->find(3);

        $this->assertEquals("NewValueHere3", $supplier1->get("first_name"));
    }

    public function testSaveInsertAutoIncrement()
    {
        $supplier = new Supplier();
        $supplier->set("company", "NewValueCompany");
        $supplier->set("last_name", "NewValueLast");
        $supplier->set("first_name", "NewValueFirst");
        $supplier->set("email_address", "user@example.com");
        $supplier->set("job_title", "Boss");
        $supplier->set("business_phone", 88);
        $supplier->set("home_phone", 77);
        $supplier->set("mobile_phone", 66);
        $supplier->set("fax_number", 55);
        $supplier->save();
        $this->assertEquals(11, $supplier->get("id"));

        $factorySupplier1 = new Supplier("STATIC");
        $supplier1 = $factorySupplier1->find(11);

        $this->assertEquals("NewValueCompany", $supplier1->get("company"));
        $this->assertEquals("NewValueLast", $supplier1->get("last_name"));
        $this->assertEquals("NewValueFirst", $supplier1->get("first_name"));
        $this->assertEquals("user@example.com", $supplier1->get("email_address"));
        $this->assertEquals("Boss", $supplier1->get("job_title"));
        $this->assertEquals(88, $supplier1->get("business_phone"));
        $this->assertEquals(77, $supplier1->get("home_phone"));
        $this->assertEquals(null, $supplier1->get("city"));
    }

    public function testRawQueryInsertAutoIncrement()
    {
        $factorySupplier = new Supplier("STATIC");
        $factorySupplier->rawNonQuery("INSERT INTO suppliers (company, last_name, first_name) VALUES ('NewValueCompany4', 'NewValueLast4', 'NewValueFirst4') ");

        $factorySupplier1 = new Supplier("STATIC");
        $supplier1 = $factorySupplier1->find(12);

        $this->assertEquals(12, $supplier1->get("id"));
        $this->assertEquals("NewValueCompany4", $supplier1->get("company"));
        $this->assertEquals("NewValueLast4", $supplier1->get("last_name"));
        $this->assertEquals("NewValueFirst4", $supplier1->get("first_name"));
        $this->assertEquals(null, $supplier1->get("email_address"));
    }

    public function testSaveInsertManualPk()
    {
        $supplier = new Supplier();
        $supplier->set("id", 99);
        $supplier->set("company", "NewValueCompany");
        $supplier->set("last_name", "NewValueLast");
        $supplier->set("first_name", "NewValueFirst");
        $supplier->set("email_address", "user@example.com");
        $supplier->set("job_title", "Boss");
        $supplier->set("business_phone", 88);
        $supplier->set("home_phone", 77);
        $supplier->set("mobile_phone", 66);
        $supplier->set("fax_number", 55);
        $supplier->save();

        $factorySupplier1 = new Supplier("STATIC");
        $supplier1 = $factorySupplier1->find(99);

        $this->assertEquals("NewValueCompany", $supplier1->get("company"));
        $this->assertEquals("NewValueLast", $supplier1->get("last_name"));
        $this->assertEquals("NewValueFirst", $supplier1->get("first_name"));
        $this->assertEquals("user@example.com", $supplier1->get("email_address"));
        $this->assertEquals("Boss", $supplier1->get("job_title"));
        $this->assertEquals(88, $supplier1->get("business_phone"));
        $this->assertEquals(77, $supplier1->get("home_phone"));
        $this->assertEquals(null, $supplier1->get("city"));
    }

    public function testRawQueryInsertManualPk()
    {
        $factorySupplier = new Supplier("STATIC");
        $factorySupplier->rawNonQuery("INSERT INTO suppliers (id, company, last_name, first_name) VALUES (98, 'NewValueCompany5', 'NewValueLast5', 'NewValueFirst5') ");

        $factorySupplier1 = new Supplier("STATIC");
        $supplier1 = $factorySupplier1->find(98);

        $this->assertEquals(98, $supplier1->get("id"));
        $this->assertEquals("NewValueCompany5", $supplier1->get("company"));
        $this->assertEquals("NewValueLast5", $supplier1->get("last_name"));
        $this->assertEquals("NewValueFirst5", $supplier1->get("first_name"));
        $this->assertEquals(null, $supplier1->get("email_address"));
    }

    public function testDelete()
    {
        $factorySupplier = new Supplier("STATIC");
        $supplier = $factorySupplier->find(1);
        $this->assertNotEmpty($supplier);

        $supplier->delete();

        $factorySupplier1 = new Supplier("STATIC");
        $supplier1 = $factorySupplier1->find(1);

        $this->assertEmpty($supplier1);
    }

    public function testMultiDelete()
    {
        $factorySupplier = new Supplier("STATIC");
        $arrSupplier = $factorySupplier->where("id", ">", "20");
        $this->assertNotEmpty($supplier1);

        foreach ($arrSupplier as $key => $supplier)
        {
            $supplier->delete();
        }

        $factorySupplier1 = new Supplier("STATIC");
        $supplier1 = $factorySupplier1->where("id", ">", "20");

        $this->assertEmpty($supplier1);
    }

    public function testRawQueryDelete()
    {
        $factorySupplier = new Supplier("STATIC");
        $factorySupplier->rawNonQuery("DELETE FROM suppliers");

        $factorySupplier1 = new Supplier("STATIC");
        $supplier1 = $factorySupplier1->all();

        $this->assertEmpty($supplier1);
    }

}

