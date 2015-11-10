<?php

/**
 * Description of UpdateQuery
 *
 * @author JunYoungK
 */
class UpdateQuery extends BaseQuery {

    //put your code here

    /* defined in BaseQuery
      protected $db;
      protected $method;
      protected $indexes;
      protected $where;
      protected $values;
     */

    private $table;
    private $set;

    function __construct($table, $db) {
        parent::__construct($db);
        $this->method = "Update";
        $this->table = trim($table);
        return $this;
    }

    protected function buildQuery() {
        $this->query = $this->method;
        $this->query .= " " . $this->indexes;
        $this->query .= " " . $this->table;
        $this->query .= " " . "SET $this->set";
        $this->query .= " Where " . $this->where;
    }

    public function set($values = array()) {
        $var_arr = array();
        $query = "";
        foreach($values as $key => $val){
            $query .= " $key = :$key,";
            $var_arr[$key] = trim($val);
        }

        $this->set = substr($query, 0, strlen($query) - 1);
        $this->values = $var_arr;

        return $this;
    }

    public function execute() {
        $this->buildQuery();

        if($this->where){
            $ret  = $this->_execute($this->query, $this->values);
            $this->clearQuery();
            return $ret;
        }
        else{
            return null;
        }
    }
    
    public function clearQuery() {
        foreach(get_class_vars(get_class($this)) as $name => $default){            
            $this->$name = $default;
        }
    }
    
}
