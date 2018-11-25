<?php
// get from http://php.net/manual/en/mysqli-stmt.get-result.php
// on user contributed note by "Anonymous"

// how to use (out of the box):
/*
require_once("stmt_fetch_assoc.php");
$conn = new mysqli("localhost","root","123","test");
$stmt = $conn->prepare("select * from tblsample");
if($stmt->execute())
{
  $res = iimysqli_stmt_get_result($stmt);
  $assocArray = iimysqli_result_fetch_array($res);
  while($assocArray != null)
  {
    echo $assocArray[0] . "\n";
    // bentuk $assocArray = [0 => 'val1', 1 => 'val2'];

    $assocArray = iimysqli_result_fetch_array($res);
  }
}
*/

// how to use (modified by kristian):
/*
require_once("stmt_fetch_assoc.php");
$conn = new mysqli("localhost","root","123","test");
$stmt = $conn->prepare("select * from tblsample");
if($stmt->execute())
{
  $res = iimysqli_stmt_get_result($stmt);
  $assocArray = iimysqli_result_fetch_array($res);
  while($assocArray != null)
  {
    var_dump($assocArray);
    // bentuk $assocArray = ['col1' => 'val1', 'col2' => 'val2'];

    echo "\n";

    $assocArray = iimysqli_result_fetch_array($res);
  }
}
*/


class iimysqli_result // wrapper
{
    public $stmt, $nCols, $arrColsName;
}

function iimysqli_stmt_get_result($stmt)
{
    /**    EXPLANATION:
     * We are creating a fake "result" structure to enable us to have
     * source-level equivalent syntax to a query executed via
     * mysqli_query().
     *
     *    $stmt = mysqli_prepare($conn, "");
     *    mysqli_bind_param($stmt, "types", ...);
     *
     *    $param1 = 0;
     *    $param2 = 'foo';
     *    $param3 = 'bar';
     *    mysqli_execute($stmt);
     *    $result _mysqli_stmt_get_result($stmt);
     *        [ $arr = _mysqli_result_fetch_array($result);
     *            || $assoc = _mysqli_result_fetch_assoc($result); ]
     *    mysqli_stmt_close($stmt);
     *    mysqli_close($conn);
     *
     * At the source level, there is no difference between this and mysqlnd.
     **/

    /*
    // out of the box
    $metadata = mysqli_stmt_result_metadata($stmt);
    $ret = new iimysqli_result;
    if (!$ret) return NULL;

    $ret->nCols = mysqli_num_fields($metadata);
    $ret->stmt = $stmt;

    mysqli_free_result($metadata);
    return $ret;
    */



    // kristian version
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
    return $ret;
}

function iimysqli_result_fetch_array(&$result)
{
    /*
    // out of the box
    $ret = array();
    $code = "return mysqli_stmt_bind_result(\$result->stmt ";

    for ($i=0; $i<$result->nCols; $i++)
    {
        $ret[$i] = NULL;
        $code .= ", \$ret['" .$i ."']";
    };

    $code .= ");";
    if (!eval($code)) { return NULL; };

    // This should advance the "$stmt" cursor.
    if (!mysqli_stmt_fetch($result->stmt)) { return NULL; };

    // Return the array we built.
    return $ret;
    */



    // kristian version
    $ret = array();
    $code = "return mysqli_stmt_bind_result(\$result->stmt ";

    foreach ($result->arrColsName as $key => $colName)
    {
        $ret[$colName] = null;
        $code .= ", \$ret['" .$colName ."']";
    }

    $code .= ");";
    if (!eval($code)) { return null; };

    // This should advance the "$stmt" cursor.
    if (!mysqli_stmt_fetch($result->stmt)) { return null; };

    // Return the array we built.
    return $ret;
}
?>