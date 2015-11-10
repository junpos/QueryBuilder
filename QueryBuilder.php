<?php

/**
 * Description of QueryBuilder
 *
 * @author JunYoungK
 */

require_once(__ROOT__ . '/PDO.php');
include_once 'QueryBuilder/Util.php';
include_once 'QueryBuilder/BaseQuery.php';
include_once 'QueryBuilder/SelectQuery.php';
include_once 'QueryBuilder/InsertQuery.php';
include_once 'QueryBuilder/UpdateQuery.php';
include_once 'QueryBuilder/DeleteQuery.php';

class QueryBuilder {
    
    protected $db;
    
    function __construct($db = 'h2owirelessnow') {
        $this->db = $db;
    }
    
    public function select($columns = "*") {
        return new SelectQuery($columns, $this->db);
    }
    
    public function update($table) {
        return new UpdateQuery($table, $this->db);
    }

    public function insert($table) {
        return new InsertQuery($table, $this->db);
    }

    public function delete($table) {
        return new DeleteQuery($table, $this->db);
    }

    
     /**
     * Returns a formatted SQL datetime string using the given $timestamp 
     * 
     * @param string $timestamp - Base timestamp
     * @param integer $diff - Difference from the default $timestamp
     * @param string $unit - Timestamp unit
     * @return string
     */
    public function date($timestamp = "Current", $diff = 0, $unit = null) {
        return Util::date($timestamp, $diff, $unit);
    }

}
