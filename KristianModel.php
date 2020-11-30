<?php
define("INFO_PRIMARY_KEYS", "PrimaryKeys");
define("INFO_COLUMNS", "Columns");
define("INFO_TABLE_NAME", "TableName");
define("INFO_RELATION", "Relation");
define("INFO_RELATIONS", "Relations");
define("INFO_TIMESTAMP_CREATED", "TimestampCreatedAt");
define("INFO_TIMESTAMP_UPDATED", "TimestampUpdatedAt");

function iimysqli_stmt_get_result($stmt)
{
    $metadata = $stmt->result_metadata();
    $ret = array();
    //if (!$ret) return NULL;

    $ret["nCols"] = $metadata->field_count;
    $ret["stmt"] = $stmt;
    $ret["arrColsName"] = array();

    $metadataField = $metadata->fetch_field();
    while($metadataField != null && $metadataField != false)
    {
        $ret["arrColsName"][] = $metadataField->name;
        $metadataField = $metadata->fetch_field();
    }

    $metadata->free_result();
    return $ret;
}

function iimysqli_result_fetch_array(&$result)
{
    $ret = array();
    $code = "return \$result['stmt']->bind_result( ";

    $codeArr = array();
    foreach ($result["arrColsName"] as $key => $colName)
    {
        $ret[$colName] = null;
        $codeArr[] = "\$ret['" .$colName ."']";
    }
    $code .= implode(", ", $codeArr);

    $code .= ");";
    //var_dump($code);
    if (!eval($code)) { return null; };
    if (!$result["stmt"]->fetch())
    {
        $result["stmt"]->free_result();
        $result["stmt"]->close();
        return null;
    };
    return $ret;
}


function bindPreparedStatement($argConn, $argSqlStringWithQuestionMarks, $argParamsToBeBind) // return stmt?
{
    //var_dump($argSqlStringWithQuestionMarks); var_dump($argParamsToBeBind); echo $argParamsToBeBind[0];
    $stmt = $argConn->prepare($argSqlStringWithQuestionMarks);
    if($stmt == false || $stmt == null)
    {
        $this->error = $stmt->error;
        return false;
    }
    $stringParamType = "";
    for ($i=0; $i < count($argParamsToBeBind); $i++)
    {
        if(is_bool($argParamsToBeBind[$i]))
        {
            if($argParamsToBeBind[$i] == true)
            {
                $argParamsToBeBind[$i] = "1";
            }
            else
            {
                $argParamsToBeBind[$i] = "0";
            }
        }
    }
    foreach ($argParamsToBeBind as $key => $paramToBeBound)
    {
        /*
        if( is_numeric($paramToBeBound) && !is_int($paramToBeBound) )
        {
            $stringParamType .= "d"; // double
        }
        else if( is_numeric($paramToBeBound) && is_int($paramToBeBound) )
        {
            $stringParamType .= "i"; // int
        }
        else if( is_string($paramToBeBound) )
        {
            $stringParamType .= "s"; // string
        }
        else
        {
            $stringParamType .= "b"; // blob
        }
        */
        $stringParamType .= "s";
    }
    //var_dump($stringParamType); var_dump($argParamsToBeBind); var_dump($argSqlStringWithQuestionMarks);

    if(count($argParamsToBeBind) == 1)
    {
        $hasilBindParam = $stmt->bind_param($stringParamType, $argParamsToBeBind[0]);
    }
    else if(count($argParamsToBeBind) > 1)
    {
        //$hasilBindParam = $stmt->bind_param($stringParamType, $argParamsToBeBind);
        //var_dump($hasilBindParam);

        //$code = "\$hasilBindParam = \$stmt->bind_param(\$stringParamType, ";
        $code = "return \$stmt->bind_param(\$stringParamType, ";
        for ($i = 0; $i < count($argParamsToBeBind); $i++)
        {
            if($i > 0)
            {
                $code .= ", ";
            }
            $code .= "\$argParamsToBeBind[" . $i . "]";
        }
        $code .= "); ";
        //var_dump($code);
        if (!eval($code)) throw new Exception("Error Binding Param", 1);

    }
    return $stmt;
}

















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
    protected $_timestamp_created_at = null; // string or null, berisi nama column DATETIME/TIMESTAMP yg menyimpan kapan record dibuat
    protected $_timestamp_updated_at = null; // string or null, berisi nama column DATETIME/TIMESTAMP yg menyimpan kapan record terakhir diubah
    protected $_table_fields = null; // array of string or null, berisi nama seluruh column di db (jika null maka akan di-query sendiri oleh program)

    // should not be overridden
    protected $_data = array(); // an associative array / dictionary, contains column in db
    protected $_data_old = array(); // an associative array / dictionary, contains column in db BEFORE EDITED
    protected $_is_inserted;// = false;
    protected $_conn = null; // db connection (dari new mysqli($host, $user, $pass, $dbname))

    public $error = "";



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
    public function get($argColumnName = null) // tested
    {
        if( is_null($argColumnName) )
        {
            return $this->_data;
        }
        else
        {
            return $this->_data[$argColumnName];
        }
    }
    private function getOldData($argColumnName = null)
    {
        if( is_null($argColumnName) )
        {
            return $this->_data_old;
        }
        else
        {
            return $this->_data_old[$argColumnName];
        }
    }
    // void, not overridable
    public function set($argColumnName, $argValue) // tested
    {
        $this->_data[$argColumnName] = $argValue;
    }


    // FACTORY / STATIC METHODS
    public function getInfo($argInfoType = null)
    {
        $info = array();
        if( !is_array($this->_primary_key) )
        {
            $info[INFO_PRIMARY_KEYS] = array($this->_primary_key);
        }
        else
        {
            $info[INFO_PRIMARY_KEYS] = $this->_primary_key;
        }
        $info[INFO_COLUMNS] = $this->getTableFields();
        $info[INFO_TABLE_NAME] = $this->_table_name;
        $info[INFO_RELATION] = $this->_relation;
        $info[INFO_RELATIONS] = $this->_relations;
        $info[INFO_TIMESTAMP_CREATED] = $this->_timestamp_created_at;
        $info[INFO_TIMESTAMP_UPDATED] = $this->_timestamp_updated_at;

        if( is_null($argInfoType) )
        {
            return $info;
        }
        else

        {
            return $info[$argInfoType];
        }
    }

    // FACTORY / STATIC METHODS
    private function getMany($sql, $arrParamsToBeBound) // <FACTORY> <STATIC>
    {
        $return = array();
        $stmt = bindPreparedStatement($this->_conn, $sql, $arrParamsToBeBound); //var_dump($stmt); var_dump($sql); var_dump($arrParamsToBeBound);

        $executeResult = $stmt->execute(); //var_dump($executeResult);
        $stmt->store_result();
        if($executeResult)
        {
            $res = iimysqli_stmt_get_result($stmt); //var_dump($res);
            $assocArray = iimysqli_result_fetch_array($res); //var_dump($assocArray);
            while( !is_null($assocArray) )
            {
                $item = new $this->_this_class_name();
                $item->_data = $assocArray; // copy the value of it's select * statement into data
                $item->_data_old = $assocArray;
                $item->_is_inserted = true;
                $return[] = $item;
                //
                $assocArray = iimysqli_result_fetch_array($res);
            }
            return $return;
        }
        else
        {
            return null;
        }

    }

    public function all($rawOrderBy=null,$limit=null,$offset=null,$columnsToBeSelected=null) // <FACTORY> <STATIC> // tested
    {
        if(is_array($columnsToBeSelected)) $columnsToBeSelected = implode(",", $columnsToBeSelected);
        else $columnsToBeSelected = "*";

        $sql = "SELECT $columnsToBeSelected FROM " . $this->_table_name;
        if( !is_null($rawOrderBy) )
        {
            $sql .= " ORDER BY " . $rawOrderBy . " ";
        }
        if(is_numeric($limit))
        {
            if(is_numeric($offset))
            {
                $sql .= " LIMIT " . $offset . " , " . $limit . " ; ";
            }
            else
            {
                $sql .= " LIMIT " . $limit . " ";
            }
        }
        return $this->getMany($sql, array());
    }

    public function find($id) // <FACTORY> <STATIC> // tested
    // argument can be id (PK) or array if the object has multiple primary keys
    {
        $results = $this->where($this->_primary_key, null, $id); //var_dump($results);
        if( is_null($results) || count($results) == 0)
        {
            return null;
        }
        else
        {
            return $results[0];
        }
    }

    // $argFilters bisa berisi: ["column", ">=", "value"]
    // $argFilters bisa berisi: ["column", "value"]  // dianggap operator "="
    // $argFilters bisa berisi: array( ["column1", ">=", "value1"], ["column2", "=", "value2"], ["column3", "<>", "value3"] )
    // $argFilters bisa berisi: ["column", "IN", ["value1","value2","value3"] ]
    public function where_i($argFilters, $rawOrderBy=null,$limit=null,$offset=null) // tested
    {
        if( !is_array($argFilters) ) // ex: $argFilters = "WHERE col=1"
        {
            throw new Exception("KristianModel error: \$argFilters harus berisi array! lihat manual / README.md", 1);
        }
        if( !is_array($argFilters[0]) )
        {
            if( count($argFilters) == 2 ) // ex: ["column", "value"]
            {
                return $this->where($argFilters[0], "=", $argFilters[1], $rawOrderBy,$limit,$offset);
            }
            else // ex: ["column", "<>", "value"]
            {
                return $this->where($argFilters[0], $argFilters[1], $argFilters[2], $rawOrderBy,$limit,$offset);
            }
        }
        else
        {
            $aColumnName = array();
            $aOperator = array();
            $aValue = array();

            foreach ($argFilters as $key => $value)
            {
                $aColumnName[] = $value[0];
                if( count($value) == 2 )
                {
                    $aOperator[] = "=";
                    $aValue[] = $value[1];
                }
                else
                {
                    $aOperator[] = $value[1];
                    $aValue[] = $value[2];
                }
            }
            return $this->where($aColumnName, $aOperator, $aValue, $rawOrderBy,$limit,$offset);
        }
    }

    public function where($argColumnName, $argOperator = null, $argValue, $rawOrderBy=null,$limit=null,$offset=null, $columnsToBeSelected=null) // <FACTORY> <STATIC> // tested
    // return array of this object
    {
        if(is_array($columnsToBeSelected)) $columnsToBeSelected = implode(",", $columnsToBeSelected);
        else $columnsToBeSelected = "*";

        // jika operator == null maka diisi "=" semua
        if( is_null($argOperator) )
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

        $sqlWithQuestion = "SELECT $columnsToBeSelected FROM " . $this->_table_name . " WHERE "; // TODO
        $arrAllParams = array(); // TODO

        if( !is_array($argColumnName) && !is_array($argOperator) )
        {
            $argColumnName = array( $argColumnName );
            $argOperator = array( $argOperator );
            $argValue = array( $argValue );
        }

        for ($i = 0; $i < count($argColumnName); $i++)
        {
            $currentKey = $argColumnName[$i];
            $currentOpr = $argOperator[$i];
            $currentVal = $argValue[$i];

            if( ($currentOpr == "IN" || $currentOpr == "NOT IN") && is_array($currentVal) )
            {
                $sqlWithQuestion .= $currentKey . " " . $currentOpr . " (";
                $arrQuestionMarksTemp = array();
                foreach ($currentVal as $key => $currentValItem)
                {
                    if(is_null($currentValItem))
                    {
                        $arrQuestionMarksTemp[] = "NULL";
                    }
                    else
                    {
                        $arrQuestionMarksTemp[] = "?";
                        $arrAllParams[] = $currentValItem;
                    }
                }
                $sqlWithQuestion .= implode(", ", $arrQuestionMarksTemp) . ") AND ";
            }
            else if( !is_null($currentVal) )
            {
                $sqlWithQuestion .= $currentKey . " " . $currentOpr . " ? AND ";
                $arrAllParams[] = $currentVal;
            }
            else
            {
                if($currentOpr == "=") $currentOpr = "IS";
                $sqlWithQuestion .= $currentKey . " " . $currentOpr . " NULL AND ";
            }
        }
        $sqlWithQuestion .= " 1=1 ";

        if( !is_null($rawOrderBy) )
        {
            $sqlWithQuestion .= " ORDER BY " . $rawOrderBy . " ";
        }
        if(is_numeric($limit))
        {
            if(is_numeric($offset))
            {
                $sqlWithQuestion .= " LIMIT " . $offset . " , " . $limit . " ";
            }
            else
            {
                $sqlWithQuestion .= " LIMIT " . $limit . " ";
            }
        } //var_dump($sqlWithQuestion);

        // execute query
        $results = $this->getMany($sqlWithQuestion, $arrAllParams); //var_dump($sqlWithQuestion); var_dump($arrAllParams);
        if( is_null($results) || count($results) == 0)
        {
            return null;
        }
        else
        {
            return $results;
        }

    }

    public function rawQuery($rawSql) // <FACTORY> <STATIC> // tested
    {
        return $this->getMany($rawSql, array());
    }

    public function rawNonQuery($rawSql) // <FACTORY> <STATIC> // tested
    {
        $stmt = bindPreparedStatement($this->_conn, $rawSql, array());
        if(!empty($this->_conn->error)) $this->error = $this->_conn->error;
        if(!empty($stmt->error)) $this->error = $stmt->error;
        return $stmt->execute();
    }

    public function getTableFields() // <STATIC> // tested
    {
        if( is_null($this->_table_fields) || !is_array($this->_table_fields) )
        {
            $return = array();
            //$rs = $this->_conn->query("SHOW COLUMNS FROM " . $this->_table_name);

            $sql = "SHOW COLUMNS FROM " . $this->_table_name;
            $stmt = bindPreparedStatement($this->_conn, $sql, array());
            $rs = $stmt->execute();
            $stmt->store_result();
            if ($rs)
            {
                $res = iimysqli_stmt_get_result($stmt);
                $assocArray = iimysqli_result_fetch_array($res); //var_dump($assocArray);
                while( !is_null($assocArray) )
                {
                    $return[] = $assocArray["Field"];
                    $assocArray = iimysqli_result_fetch_array($res);
                }
                $this->_table_fields = $return;
                return $return;
            }
            else
            {
                return null;
            }
        }
        else
        {
            return $this->_table_fields;
        }
    }

    public function createFromArray($arrayTarget, $argArrayKeys = null) // <FACTORY> <STATIC> // tested
    {
        // membantu ketika ambil data dari POST/GET variables
        $result = new $this->_this_class_name();
        if( is_null($argArrayKeys) )
        {
            $tableFields = $this->getTableFields();
            $result->setDataFromArray($arrayTarget, null, $tableFields);
        }
        else
        {
            $result->setDataFromArray($arrayTarget, $argArrayKeys, null);
        }
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
        //
        // PERBEDAAN DENGAN createFromArray:
        // tidak perlu specify key saja yang dimasukkan, nanti akan di-infer sendiri dari kolom di database
    }
    public function setDataFromArray($arrayTarget, $argArrayKeys = null, $argTableFields = null) // NOT FACTORY / STATIC, BUT RELATED TO createFromArray // tested
    {
        if( !is_null($argArrayKeys) && is_null($argTableFields) )
        {
            foreach ($argArrayKeys as $key => $value)
            {
                $this->_data[$value] = $arrayTarget[$value];
            }
        }
        else if( is_null($argArrayKeys) && !is_null($argTableFields) )
        {
            foreach ($arrayTarget as $key => $value)
            {
                if( in_array($key, $argTableFields) )
                {
                    $this->_data[$key] = $value;
                }
            }
        }
        else
        {
            throw new Exception("Incomplete arguments for method KristianModel.setDataFromArray", 1);
        }
    }



    // DATABASE OPERATION METHODS
    public function save() // tested
    {
        // jika data belum ada di db maka insert, jika sudah ada maka update
        $sql = "";
        if($this->_is_inserted == true)
        {
            // update
            // set timestamp column
            if( !is_null($this->_timestamp_updated_at) && is_string($this->_timestamp_updated_at))
            {
                $this->set($this->_timestamp_updated_at, date("Y-m-d H:i:s"));
            }

            // new
            $arrSetClauseColName = array(); // contoh isi: ['col1', 'col2', 'col3'];
            $arrSetClauseQuestionMarks = array(); // contoh isi: ['?', '?', 'NULL']; // tujuannya mengakomodasi jika ada yang null, nanti ini akan di implode
            $arrSetClauseValue = array(); // contoh isi: ['val1', 'val2']; // yang null tidak ikut dimasukkan agar masukkan ke prepared statement lebih mudah
            $arrWhereClauseColName = array(); // contoh isi: ['colPK1', 'colPK2', 'colPK3']
            $arrWhereClauseQuestionMarks = array(); // contoh isi: ['?', '?', '?']
            $arrWhereClauseValue = array(); // contoh isi: ['valPK1', 'valPK2', 'valPK3']

            foreach ($this->_data as $key => $value)
            {
                $arrSetClauseColName[] = $key;
                if( !is_null($value) )
                {
                    $arrSetClauseQuestionMarks[] = "?";
                    $arrSetClauseValue[] = $value;
                }
                else
                {
                    $arrSetClauseQuestionMarks[] = "NULL";
                }
            }

            if(is_array($this->_primary_key))
            {
                foreach ($this->_primary_key as $key => $value)
                {
                    $arrWhereClauseColName[] = $value;
                    if( !is_null($this->getOldData($value)) )
                    {
                        $arrWhereClauseQuestionMarks[] = "?";
                        $arrWhereClauseValue[] = $this->getOldData($value);
                    }
                    else
                    {
                        $arrWhereClauseQuestionMarks[] = "NULL";
                    }
                }
            }
            else
            {
                $arrWhereClauseColName[] = $this->_primary_key;
                $arrWhereClauseQuestionMarks[] = "?";
                $arrWhereClauseValue[] = $this->getOldData($this->_primary_key);
            }

            $arrPreparedSetClause = array();
            for ($i = 0; $i < count($arrSetClauseColName); $i++)
            {
                $arrPreparedSetClause[] = $arrSetClauseColName[$i] . " = " . $arrSetClauseQuestionMarks[$i];
            }

            $arrPreparedWhereClause = array();
            for ($i = 0; $i < count($arrWhereClauseColName); $i++)
            {
                $arrPreparedWhereClause[] = $arrWhereClauseColName[$i] . " = " . $arrWhereClauseQuestionMarks[$i];
            }

            $arrAllParams = array();
            foreach ($arrSetClauseValue as $key => $value)
            {
                $arrAllParams[] = $value;
            }
            foreach ($arrWhereClauseValue as $key => $value)
            {
                $arrAllParams[] = $value;
            }

            $sqlWithQuestion = "UPDATE " . $this->_table_name . " SET " . implode(" , ", $arrPreparedSetClause) . " WHERE " . implode(" AND ", $arrPreparedWhereClause);
            $stmt = bindPreparedStatement($this->_conn, $sqlWithQuestion, $arrAllParams);

            if(is_null($stmt) || $stmt == false)
            {
                $this->error = $stmt->error;
                return false;
            }
            $resultExecute = $stmt->execute();
            $stmt->store_result();
            if($resultExecute)
            {
                $this->_data_old = $this->_data;
            }
            if(!empty($this->_conn->error)) $this->error = $this->_conn->error;
            if(!empty($stmt->error)) $this->error = $stmt->error;
            return $resultExecute;

        }
        else
        {
            // insert
            // set timestamp column
            if(!is_null($this->_timestamp_created_at) && is_string($this->_timestamp_created_at))
            {
                $this->set($this->_timestamp_created_at, date("Y-m-d H:i:s"));
            }

            // new
            $arrInsertClauseColName = array(); // contoh isi: ['col1', 'col2', 'col3'];
            $arrInsertClauseQuestionMarks = array(); // contoh isi: ['?', '?', 'NULL']; // tujuannya mengakomodasi jika ada yang null, nanti ini akan di implode
            $arrInsertClauseValue = array(); // contoh isi: ['val1', 'val2']; // yang null tidak ikut dimasukkan agar masukkan ke prepared statement lebih mudah

            foreach ($this->_data as $key => $value)
            {
                if(
                    $this->_is_incrementing &&
                    $key == $this->_primary_key &&
                    !is_array($this->_primary_key) &&
                    empty($this->_data[$this->_primary_key])
                )
                {
                    // do not add it to query
                }
                else
                {
                    $arrInsertClauseColName[] = $key;
                    if(!is_null($value))
                    {
                        $arrInsertClauseQuestionMarks[] = "?";
                        $arrInsertClauseValue[] = $value;
                    }
                    else
                    {
                        $arrInsertClauseQuestionMarks[] = "NULL";
                    }
                }

            }

            $sqlWithQuestion = "INSERT INTO " . $this->_table_name . " (" . implode(" , ", $arrInsertClauseColName) . ") VALUES (" . implode(" , ", $arrInsertClauseQuestionMarks) . ") ";
            $arrInsertClauseValue;
            $stmt = bindPreparedStatement($this->_conn, $sqlWithQuestion, $arrInsertClauseValue);
            if(is_null($stmt) || $stmt == false)
            {
                $this->error = $stmt->error;
                return false;
            }
            $executeResult = $stmt->execute();
            $stmt->store_result();
            if($executeResult)
            {
                $this->_is_inserted = true;
                // set PK jika AUTO_INCREMENT
                if(!is_array($this->_primary_key) && $this->_is_incrementing == true)
                {
                    $this->set($this->_primary_key, $stmt->insert_id);
                }
                $this->_data_old = $this->_data;
            }
            else $this->error = $stmt->error;
            return $executeResult;
        }
    }
    public function delete() // tested
    {
        // delete dari db
        $sql = "";

        // new
        $arrWhereClauseColName = array();
        $arrWhereClauseQuestionMarks = array();
        $arrWhereClauseValue = array();

        if(is_array($this->_primary_key))
        {
            foreach ($this->_primary_key as $key => $value)
            {
                $arrWhereClauseColName[] = $value;
                if(!is_null($this->get($value)))
                {
                    $arrWhereClauseQuestionMarks[] = "?";
                    $arrWhereClauseValue[] = $this->get($value);
                }
                else
                {
                    $arrWhereClauseQuestionMarks[] = "NULL";
                }
            }
        }
        else
        {
            $arrWhereClauseColName[] = $this->_primary_key;
            $arrWhereClauseQuestionMarks[] = "?";
            $arrWhereClauseValue[] = $this->get($this->_primary_key);
        }

        $arrPreparedWhereClause = array();
        for ($i = 0; $i < count($arrWhereClauseColName); $i++)
        {
            $arrPreparedWhereClause[] = $arrWhereClauseColName[$i] . " = " . $arrWhereClauseQuestionMarks[$i];
        }
        $sqlWithQuestion = "DELETE FROM " . $this->_table_name . " WHERE " . implode(" AND ", $arrPreparedWhereClause);
        $stmt = bindPreparedStatement($this->_conn, $sqlWithQuestion, $arrWhereClauseValue);

        if(is_null($stmt) || $stmt == false)
        {
            $this->error = $stmt->error;
            return false;
        }
        $executeResult = $stmt->execute();
        $stmt->store_result();
        if(!empty($this->_conn->error)) $this->error = $this->_conn->error;
        if(!empty($stmt->error)) $this->error = $stmt->error;
        return $executeResult;
    }



    // RELATIONSHIPS METHOD
    public function getRelation($argRelationName) // tested
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
    public function getRelations($argRelationsName) // tested
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

