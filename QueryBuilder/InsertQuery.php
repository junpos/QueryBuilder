<?php

/**
 * Description of InsertQuery
 *
 * @author JunYoungK
 */
class InsertQuery{
    
    private $db;
    private $method;
    private $query;
    private $table;
    private $columns_clause;
    private $values_clause;
    private $values;

    function __construct($table, $db) {
        $this->db = $db;
        $this->method = "Insert";
        $this->table = trim($table);
        return $this;
    }

    protected function buildQuery() {
        $this->query = $this->method;
        $this->query .= " Into " . $this->table;
        $this->query .= " " . $this->columns_clause;
        $this->query .= " " . $this->values_clause;
    }

    public function values($values = array()) {
        $columns_clause = " (";
        $values_clause = " Values(";

        foreach($values as $key => $val){
            $columns_clause .= " $key,";
            $values_clause .= " :$key,";
            $var_arr[$key] = trim($val);
        }

        $this->columns_clause = substr($columns_clause, 0, strlen($columns_clause) - 1) . ")";
        $this->values_clause = substr($values_clause, 0, strlen($values_clause) - 1) . ")";
        $this->values = $var_arr;

        return $this;
    }

    public function execute() {
        $this->buildQuery();
        $ret = $this->_execute($this->query, $this->values);
        $this->clearQuery();
        return $ret;
    }

    public function clearQuery() {
        foreach(get_class_vars(get_class($this)) as $name => $default){
            $this->$name = $default;
        }
    }
    
    private function _execute($query, $var_arr) {
        try {
            $db = new PDOConnector($this->db);
            $db->executeQuery($query, $var_arr);
            $lastInsertedID = $db->getLastInsertedID();            
            $db->close();
            return $lastInsertedID;
        } catch (PDOException $e) {
            Util::displayError("System error occurred.", "Please go back", $e);
        }
    }

}
