<?php

class EmployeePrivilege extends KristianModel
{
    protected $_this_class_name = "EmployeePrivilege";
    protected $_primary_key = array("employee_id", "privilege_id");
    protected $_conn_varname = "conn";
    protected $_table_name = "employee_privileges";
    protected $_is_incrementing = false;
}
