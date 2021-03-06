<?php

// addon
/*
class iimysqli_result
{
    public $stmt, $nCols, $arrColsName;
}

function iimysqli_stmt_get_result($stmt)
{
    $metadata = $stmt->result_metadata();
    $ret = new iimysqli_result();
    if (!$ret) return NULL;

    $ret->nCols = $metadata->field_count;
    $ret->stmt = $stmt;
    $ret->arrColsName = array();

    $metadataField = $metadata->fetch_field();
    while($metadataField != null && $metadataField != false)
    {
        $ret->arrColsName[] = $metadataField->name;
        $metadataField = $metadata->fetch_field();
    }

    $metadata->free_result();
    //$metadata->close();
    return $ret;
}

function iimysqli_result_fetch_array(&$result)
{
    $ret = array();
    $code = "return mysqli_stmt_bind_result(\$result->stmt ";

    foreach ($result->arrColsName as $key => $colName)
    {
        $ret[$colName] = null;
        $code .= ", \$ret['" .$colName ."']";
    }

    $code .= ");";
    if (!eval($code)) { return null; };
    if (!mysqli_stmt_fetch($result->stmt))
    {
        $result->stmt->free_result();
        $result->stmt->close();
        return null;
    };
    return $ret;
}
*/

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
    if($stmt == false || $stmt == null) return false;
    $stringParamType = "";
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
    public function get($argColumnName = null)
    {
        if($argColumnName == null)
        {
            return $this->_data;
        }
        else
        {
            return $this->_data[$argColumnName];
        }
    }
    // void, not overridable
    public function set($argColumnName, $argValue)
    {
        $this->_data[$argColumnName] = $argValue;
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
            while($assocArray != null)
            {
                $item = new $this->_this_class_name();
                $item->_data = $assocArray; // copy the value of it's select * statement into data
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

        /*
        $rs = $this->_conn->query($sql);
        if ($rs)
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
        */
    }

    public function all($rawOrderBy=null,$limit=null,$offset=null) // <FACTORY> <STATIC>
    {
        $sql = "SELECT * FROM " . $this->_table_name;
        if($rawOrderBy != null)
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

    public function find($id) // <FACTORY> <STATIC>
    // argument can be id (PK) or array if the object has multiple primary keys
    {
        $results = $this->where($this->_primary_key, null, $id); //var_dump($results);
        if($results == null || count($results) == 0)
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
    public function where_i($argFilters, $rawOrderBy=null,$limit=null,$offset=null)
    {
        if( !is_array($argFilters) )
        {
            throw new Exception("KristianModel error: $argFilters harus berisi array! lihat manual / README.md", 1);
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

    public function where($argColumnName, $argOperator = null, $argValue, $rawOrderBy=null,$limit=null,$offset=null) // <FACTORY> <STATIC>
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
        /*
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
        */

        $sqlWithQuestion = "SELECT * FROM " . $this->_table_name . " WHERE "; // TODO
        $arrAllParams = array(); // TODO

        if( is_array($argColumnName) && is_array($argOperator) && is_array($argValue) )
        {
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
                        $arrQuestionMarksTemp[] = "?";
                        $arrAllParams[] = $currentValItem;
                    }
                    $sqlWithQuestion .= implode(", ", $arrQuestionMarksTemp) . ") AND ";
                }
                else if($currentVal != null)
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
        }
        else if( !is_array($argColumnName) && !is_array($argOperator) && !is_array($argValue) )
        {
            $sqlWithQuestion .= $argColumnName . " " . $argOperator . " ? ";
            $arrAllParams[] = $argValue;
        }
        else
        {
            throw new Exception("All arguments must be all array or all string!", 1);
        }

        if($rawOrderBy != null)
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
        return $this->getMany($rawSql, array());
    }

    public function getTableFields() // <STATIC>
    {
        if($this->_table_fields == null || !is_array($this->_table_fields) )
        {
            $return = array();
            //$rs = $this->_conn->query("SHOW COLUMNS FROM " . $this->_table_name);

            $sql = "SHOW COLUMNS FROM " . $this->_table_name;
            $stmt = bindPreparedStatement($this->_conn, $sql, array());
            $rs = $stmt->execute();
            $stmt->store_result();
            if ($rs)
            {
                /*
                while ($row = $rs->fetch_assoc())
                {
                    $return[] = $row["Field"];
                }
                return $return;
                */

                $res = iimysqli_stmt_get_result($stmt);
                $assocArray = iimysqli_result_fetch_array($res); //var_dump($assocArray);
                while($assocArray != null)
                {
                    $return[] = $assocArray["Field"];
                    $assocArray = iimysqli_result_fetch_array($res);
                }
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

    public function createFromArray($arrayTarget, $argArrayKeys = null) // <FACTORY> <STATIC>
    {
        // membantu ketika ambil data dari POST/GET variables
        $result = new $this->_this_class_name();
        if($argArrayKeys == null)
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
    public function setDataFromArray($arrayTarget, $argArrayKeys = null, $argTableFields = null) // NOT FACTORY / STATIC, BUT RELATED TO createFromArray
    {
        if($argArrayKeys != null && $argTableFields == null)
        {
            foreach ($argArrayKeys as $key => $value)
            {
                $this->_data[$value] = $arrayTarget[$value];
            }
        }
        else if($argArrayKeys == null && $argTableFields != null)
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
    public function save()
    {
        // jika data belum ada di db maka insert, jika sudah ada maka update
        $sql = "";
        if($this->_is_inserted == true)
        {
            // update
            // set timestamp column
            if($this->_timestamp_updated_at != null && is_string($this->_timestamp_updated_at))
            {
                $this->set($this->_timestamp_updated_at, date("Y-m-d H:i:s"));
            }

            $arrSetStatement = array(); // contoh isi: ["col1 = 'val1'", "col2 = 'val2'", "col3 = NULL"]
            $arrWhereStatement = array(); // contoh isi: ["colPK1 = 'valPK1'", "colPK2 = 'valPK2'", "colPK3 = 'valPK3'"]

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
                if($value != null)
                {
                    $arrSetStatement[] = $key . " = '" . $value . "'"; // old

                    $arrSetClauseQuestionMarks[] = "?";
                    $arrSetClauseValue[] = $value;
                }
                else
                {
                    $arrSetStatement[] = $key . " = NULL"; // old

                    $arrSetClauseQuestionMarks[] = "NULL";
                }
            }

            if(is_array($this->_primary_key))
            {
                foreach ($this->_primary_key as $key => $value)
                {
                    $arrWhereClauseColName[] = $value;
                    if($this->get($value) != null)
                    {
                        $arrWhereStatement[] = $value . " = '" . $this->get($value) . "'"; // old

                        $arrWhereClauseQuestionMarks[] = "?";
                        $arrWhereClauseValue[] = $this->get($value);
                    }
                    else
                    {
                        $arrWhereStatement[] = $value . " = NULL"; // old

                        $arrWhereClauseQuestionMarks[] = "NULL";
                    }
                }
            }
            else
            {
                $arrWhereStatement[] = $this->_primary_key . " = '" . $this->get($this->_primary_key) . "'";

                $arrWhereClauseColName[] = $this->_primary_key;
                $arrWhereClauseQuestionMarks[] = "?";
                $arrWhereClauseValue[] = $this->get($this->_primary_key);
            }


            //$sql .= "UPDATE " . $this->_table_name . " SET " . implode(" , ", $arrSetStatement) . " WHERE " . implode(" AND ", $arrWhereStatement) . " ; ";
            //$queryResult = $this->_conn->query($sql) == true;
            //if($queryResult == true)

            //$arrSetClauseColName;
            //$arrSetClauseQuestionMarks;
            $arrPreparedSetClause = array();
            for ($i = 0; $i < count($arrSetClauseColName); $i++)
            {
                $arrPreparedSetClause[] = $arrSetClauseColName[$i] . " = " . $arrSetClauseQuestionMarks[$i];
            }

            //$arrWhereClauseColName;
            //$arrWhereClauseQuestionMarks;
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

            if($stmt == null || $stmt == false) return false;
            $resultExecute = $stmt->execute();
            $stmt->store_result();
            return $resultExecute;

        }
        else
        {
            // insert
            // set timestamp column
            if($this->_timestamp_created_at != null && is_string($this->_timestamp_created_at))
            {
                $this->set($this->_timestamp_created_at, date("Y-m-d H:i:s"));
            }

            $arrColumnStatement = array();
            $arrValueStatement = array();

            // new
            $arrInsertClauseColName = array(); // contoh isi: ['col1', 'col2', 'col3'];
            $arrInsertClauseQuestionMarks = array(); // contoh isi: ['?', '?', 'NULL']; // tujuannya mengakomodasi jika ada yang null, nanti ini akan di implode
            $arrInsertClauseValue = array(); // contoh isi: ['val1', 'val2']; // yang null tidak ikut dimasukkan agar masukkan ke prepared statement lebih mudah

            foreach ($this->_data as $key => $value)
            {
                if($this->_is_incrementing && $key == $this->_primary_key)
                {
                    // do not add it to query
                }
                else
                {
                    $arrColumnStatement[] = $key; // old

                    $arrInsertClauseColName[] = $key;
                    if($value != null)
                    {
                        $arrValueStatement[] = "'" . $value . "'"; // old

                        $arrInsertClauseQuestionMarks[] = "?";
                        $arrInsertClauseValue[] = $value;
                    }
                    else
                    {
                        $arrValueStatement[] = "NULL"; // old

                        $arrInsertClauseQuestionMarks[] = "NULL";
                    }
                }

            }

            /*
            $sql .= "INSERT INTO " . $this->_table_name . " (" . implode(" , ", $arrColumnStatement) . ") VALUES (" . implode(" , ", $arrValueStatement) . ") ; ";
            $result = $this->_conn->query($sql);

            if($result == true)
            {
                $this->_is_inserted = true;

                // set PK jika AUTO_INCREMENT
                if(!is_array($this->_primary_key) && $this->_is_incrementing == true)
                {
                    $this->set($this->_primary_key, $this->_conn->insert_id);
                }
                return true;
            }
            else
            {
                return false;
            }
            */

            $sqlWithQuestion = "INSERT INTO " . $this->_table_name . " (" . implode(" , ", $arrInsertClauseColName) . ") VALUES (" . implode(" , ", $arrInsertClauseQuestionMarks) . ") ";
            $arrInsertClauseValue;
            $stmt = bindPreparedStatement($this->_conn, $sqlWithQuestion, $arrInsertClauseValue);
            if($stmt == null || $stmt == false) return false;
            $executeResult = $stmt->execute();
            $stmt->store_result();
            if($executeResult == false) return false;

            $this->_is_inserted = true;
            // set PK jika AUTO_INCREMENT
            if(!is_array($this->_primary_key) && $this->_is_incrementing == true)
            {
                $this->set($this->_primary_key, $stmt->insert_id);
            }
        }
    }
    public function delete()
    {
        // delete dari db
        $sql = "";

        $arrWhereStatement = array();

        // new
        $arrWhereClauseColName = array();
        $arrWhereClauseQuestionMarks = array();
        $arrWhereClauseValue = array();

        if(is_array($this->_primary_key))
        {
            foreach ($this->_primary_key as $key => $value)
            {
                $arrWhereClauseColName[] = $value;
                if($this->get($value) != null)
                {
                    $arrWhereStatement[] = $value . " = '" . $this->get($value) . "'"; // old

                    $arrWhereClauseQuestionMarks[] = "?";
                    $arrWhereClauseValue[] = $this->get($value);
                }
                else
                {
                    $arrWhereStatement[] = $value . " = NULL"; // old

                    $arrWhereClauseQuestionMarks[] = "NULL";
                }
            }
        }
        else
        {
            $arrWhereStatement[] = $this->_primary_key . " = '" . $this->get($this->_primary_key) . "'"; // old

            $arrWhereClauseColName[] = $this->_primary_key;
            $arrWhereClauseQuestionMarks[] = "?";
            $arrWhereClauseValue[] = $this->get($this->_primary_key);
        }

        /*
        $sql .= "DELETE FROM " . $this->_table_name . " WHERE " . implode(" AND ", $arrWhereStatement) . " ; " ; // old
        return $this->_conn->query($sql) == true; // old
        */

        $arrPreparedWhereClause = array();
        for ($i = 0; $i < count($arrWhereClauseColName); $i++)
        {
            $arrPreparedWhereClause[] = $arrWhereClauseColName[$i] . " = " . $arrWhereClauseQuestionMarks[$i];
        }
        $sqlWithQuestion = "DELETE FROM " . $this->_table_name . " WHERE " . implode(" AND ", $arrPreparedWhereClause);
        $stmt = bindPreparedStatement($this->_conn, $sqlWithQuestion, $arrWhereClauseValue);

        if($stmt == null || $stmt == false) return false;
        $executeResult = $stmt->execute();
        $stmt->store_result();
        return $executeResult;
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

