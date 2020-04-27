<?php

require_once("vendor/autoload.php");
require_once("KristianModel.php");
foreach (glob("tests/models/*") as $modelFileName)
{
    require_once($modelFileName);
}

use PHPUnit\Framework\TestCase;

class UnitTest extends TestCase
{

}

