<?php

/**
 * Description of DeleteQuery
 *
 * @author JunYoungK
 */
class DeleteQuery extends BaseQuery {
    /* defined in BaseQuery
      protected $db;
      protected $method;
      protected $indexes;
      protected $where;
      protected $values;
     */

    private $table;

    function __construct($table, $db) {
        parent::__construct($db);
        $this->method = "Delete";
        $this->table = trim($table);
        return $this;
    }

    protected function buildQuery() {
        $this->query = $this->method;
        $this->query .= " " . $this->indexes;
        $this->query .= "From " . $this->table;
        $this->query .= " Where " . $this->where;        
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
