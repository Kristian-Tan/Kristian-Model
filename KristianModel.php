<?php


class KristianModel
{
    // PROPERTIES / DATA MEMBERS
    // must be overridden
    protected $_this_class_name; // string, must be overriden in child, value=class name of child
    protected $_table_name; // nama tabel di db

    // overriding is optional
    protected $_primary_key = "id"; // bisa berisi string nama column (jika 1 primary key), bisa berisi array of string nama column (jika composite key)
    protected $_relation = array(); // jika primary key ada di aku
    protected $_relations = array(); // jika primary key ada di dia
    protected $_conn_varname = "conn"; // default: gunakan db connection dari global variable $conn
    protected $_is_incrementing = true; // boolean, true jika id di db adalah AUTO_INCREMENT

    // should not be overridden
    protected $_data = array(); // an associative array / dictionary, contains column in db
    protected $_is_inserted;// = false;
    protected $_conn = null; // db connection (dari new mysqli($host, $user, $pass, $dbname))



    // CONSTRUCTOR
    function __construct($argMode = "DYNAMIC")
    {
        // dapatkan db connection
        $conn_varname = $this->_conn_varname;
        global $$conn_varname;
        $this->_conn = $$conn_varname;

        if($argMode == "STATIC")
        {
            //
        }
        else if($argMode == "DYNAMIC")
        {
            $this->_is_inserted = false;

            // buat key dari $this->_data sesuai dengan kolom/field di database table
            $factory = new $this->_this_class_name("STATIC");
            $fields = $factory->getTableFields();
            foreach ($fields as $key => $value)
            {
                $this->_data[$value] = null;
            }
        }

    }



    // GETTER / SETTER METHODS
    // return Object, not overridable
    public function get($argColumnName)
    {
        return $this->_data[$argColumnName];
    }
    // void, not overridable
    public function set($argColumnName, $argValue)
    {
        $this->_data[$argColumnName] = $argValue;
    }



    // FACTORY / STATIC METHODS
    private function getMany($sql) // <FACTORY> <STATIC>
    {
        $return = array();
        if ($rs = $this->_conn->query($sql))
        {
            while ($row = $rs->fetch_assoc())
            {
                $item = new $this->_this_class_name();
                $item->_data = $row; // copy the value of it's select * statement into data
                $item->_is_inserted = true;
                $return[] = $item;
            }
            return $return;
        }
        else
        {
            return null;
        }
    }

    public function all() // <FACTORY> <STATIC>
    {
        $sql = "SELECT * FROM " . $this->_table_name . " ; ";
        return $this->getMany($sql);
    }

    public function find($id) // <FACTORY> <STATIC>
    // argument can be id (PK) or array if the object has multiple primary keys
    {
        $results = $this->where($this->_primary_key, null, $id);
        if($results == null || count($results) == 0)
        {
            return null;
        }
        else
        {
            return $results[0];
        }
    }

    public function where($argColumnName, $argOperator = null, $argValue) // <FACTORY> <STATIC>
    // return array of this object
    {
        // jika operator == null maka diisi "=" semua
        if($argOperator == null)
        {
            if(is_array($argColumnName))
            {
                $argOperator = array();
                foreach ($argColumnName as $key => $value)
                {
                    $argOperator[] = "=";
                }
            }
            else
            {
                $argOperator = "=";
            }
        }

        // build query
        $sql = "SELECT * FROM " . $this->_table_name . " WHERE ";

        if( is_array($argColumnName) && is_array($argOperator) && is_array($argValue) )
        {
            for ($i = 0; $i < count($this->_primary_key); $i++)
            {
                $currentKey = $argColumnName[$i];
                $currentOpr = $argOperator[$i];
                $currentVal = $argValue[$i];

                $sql .= $currentKey . " " . $currentOpr . " " . "'" . $this->_conn->real_escape_string($currentVal) . "' AND ";
            }
            $sql .= " 1=1 ";
        }
        else if( !is_array($argColumnName) && !is_array($argOperator) && !is_array($argValue) )
        {
            $sql .= $argColumnName . " " . $argOperator . " " . "'" . $this->_conn->real_escape_string($argValue) . "' ";
        }

        $sql .= " ; ";

        // execute query
        $results = $this->getMany($sql);
        if($results == null || count($results) == 0)
        {
            return null;
        }
        else
        {
            return $results;
        }

    }

    public function rawQuery($rawSql) // <FACTORY> <STATIC>
    {
        return $this->getMany($rawSql);
    }

    public function getTableFields() // <STATIC>
    {
        $return = array();
        if ($rs = $this->_conn->query("SHOW COLUMNS FROM " . $this->_table_name))
        {
            while ($row = $rs->fetch_assoc())
            {
                $return[] = $row["Field"];
            }
            return $return;
        }
        else
        {
            return null;
        }
    }

    public function createFromArray($arrayTarget, $argArrayKeys) // <FACTORY> <STATIC>
    {
        // membantu ketika ambil data dari POST/GET variables
        $result = new $this->_this_class_name();
        $result->setDataFromArray($arrayTarget, $argArrayKeys);
        return $result;

        // contoh penggunaan:
        // ada class: class Mobil extends KristianModel, db berikut: mobil (idmobil PK, idmerk FK, tipe)
        // ada form berikut:
        // <form action="process.php" method="POST">
        // BUAT MOBIL BARU (CREATE)
        // <input type="text" name="idmobil"><input type="text" name="idmerk"><input type="text" name="tipe">
        // </form>
        // pada process.php:
        // $factory = new Mobil("STATIC");
        // $mobil = $factory->createFromArray($_POST, array("idmobil", "idmerk", "tipe"));
        // $mobil->save();
    }
    public function setDataFromArray($arrayTarget, $argArrayKeys) // NOT FACTORY / STATIC, BUT RELATED TO createFromArray
    {
        foreach ($argArrayKeys as $key => $value)
        {
            $this->_data[$value] = $arrayTarget[$value];
        }
    }



    // DATABASE OPERATION METHODS
    public function save()
    {
        // jika data belum ada di db maka insert, jika sudah ada maka update
        $sql = "";
        if($this->_is_inserted == true)
        {
            // update
            $arrSetStatement = array();
            $arrWhereStatement = array();

            foreach ($this->_data as $key => $value)
            {
                $arrSetStatement[] = $key . " = '" . $value . "'";
            }

            if(is_array($this->_primary_key))
            {
                foreach ($this->_primary_key as $key => $value)
                {
                    $arrWhereStatement[] = $value . " = '" . $this->get($value) . "'";
                }
            }
            else
            {
                $arrWhereStatement[] = $this->_primary_key . " = '" . $this->get($this->_primary_key) . "'";
            }


            $sql .= "UPDATE " . $this->_table_name . " SET " . implode(" , ", $arrSetStatement) . " WHERE " . implode(" AND ", $arrWhereStatement) . " ; ";
            return $this->_conn->query($sql) == true;
        }
        else
        {
            // insert
            $arrColumnStatement = array();
            $arrValueStatement = array();

            foreach ($this->_data as $key => $value)
            {
                if($this->_is_incrementing && $key == $this->_primary_key)
                {
                    // do not add it to query
                }
                else
                {
                    $arrColumnStatement[] = $key;
                    $arrValueStatement[] = "'" . $value . "'";
                }

            }

            $sql .= "INSERT INTO " . $this->_table_name . " (" . implode(" , ", $arrColumnStatement) . ") VALUES (" . implode(" , ", $arrValueStatement) . ") ; ";
            $result = $this->_conn->query($sql);

            if(!is_array($this->_primary_key) && $this->_is_incrementing == true)
            {
                $this->set($this->_primary_key, $this->_conn->insert_id);
            }
            return $result;
        }
    }
    public function delete()
    {
        // delete dari db
        $sql = "";

        $arrWhereStatement = array();
        if(is_array($this->_primary_key))
        {
            foreach ($this->_primary_key as $key => $value)
            {
                $arrWhereStatement[] = $value . " = '" . $this->get($value) . "'";
            }
        }
        else
        {
            $arrWhereStatement[] = $this->_primary_key . " = '" . $this->get($this->_primary_key) . "'";
        }

        $sql .= "DELETE FROM " . $this->_table_name . " WHERE " . implode(" AND ", $arrWhereStatement) . " ; " ;

        return $this->_conn->query($sql) == true;
    }



    // RELATIONSHIPS METHOD
    public function getRelation($argRelationName)
    {
        //
        $classNameObject = $this->_relation[$argRelationName][0];
        $foreignKeyName = $this->_relation[$argRelationName][1]; // bisa singular bisa array
        $id = null;
        if(!is_array($foreignKeyName))
        {
            $id = $this->get($foreignKeyName);
        }
        else
        {
            $id = array();
            foreach ($foreignKeyName as $key => $value)
            {
                $id[] = $this->get($value);
            }
        }
        $factory = new $classNameObject("STATIC");
        $result = $factory->find($id);
        return $result;
    }
    public function getRelations($argRelationsName)
    {
        $classNameObject = $this->_relations[$argRelationsName][0];
        $foreignKeyName = $this->_relations[$argRelationsName][1]; // bisa singular bisa array
        $id = null;
        if(!is_array($this->_primary_key))
        {
            $id = $this->get($this->_primary_key);
        }
        else
        {
            $id = array();
            foreach ($this->_primary_key as $key => $value)
            {
                $id[] = $this->get($value);
            }
        }
        $factory = new $classNameObject("STATIC");
        $result = $factory->where($foreignKeyName, null, $id);
        return $result;
    }

}

