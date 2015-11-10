<?php

/**
 * Description of BaseQuery
 *
 * @author JunYoungK
 */
abstract class BaseQuery {

    protected $db;    
    protected $method;
    protected $indexes;
    protected $where;
    protected $values;
    protected $query;
        
    protected $joining_hint = "@";
    public $affectedRowCounts = -1;
    
    
    /**
     * Returns a new Instance of this class
     * 
     * @param string $app - Target DB connection
     * @return \QueryBuilder
     */
    function __construct($app = 'h2owirelessnow') {
        $this->db = $app;
        return $this;
    }

    public function changeDB($app) {
        if(in_array(strtolower(trim($app)), $this->db_list)){
            $this->db = $app;
            return true;
        }
        return false;
    }
    

    public function index($indexes, $isOrdered = false) {
        $indexes = is_array($indexes)? $indexes: array($indexes);        
        
        if($indexes && is_array($indexes)){
            $ordered = $isOrdered? "Ordered," : "";
            $this->indexes = "--+ $ordered Index(" . implode('), Index(', $indexes) . ") \n";
            return $this;
        }

        return null;
    }

    public function where($conditions = array()) {
        if(is_array($conditions)){
            $cond = $this->_buildConditions($conditions);
            $this->where = $cond['where'];
            if($this->values){
                $this->values += $cond['values'];
            }else{
                $this->values = $cond['values'];
            }
            return $this;
        }        
        return null;
    }

    public function addWhere($conditions = array(), $type = 'AND') {
        $type = strtoupper($type);

        $types = array(
            'AND', 'OR'
        );

        if($this->where && in_array($type, $types)){            
            $cond = $this->_buildConditions($conditions, $type);
            $this->where .= " $type " . $cond['where'];
            $this->values += $cond['values'];
            return $this;
        }
        
        return null;
    }
    
    public function getQuery(){
        $this->buildQuery();
        $db = $this->db;
        $query = $this->query;
        $values = $this->values;
        return compact('db', 'query', 'values');
    }
    
    public function changeJoiningHint($hint){
        $allowed = array(
            '!', '@', '#', '&', '%', '^', '&', '*'
        );
        if(!in_array($hint, $allowed)){
            return false;
        }
        $this->joining_hint = $hint;
        return true;
    }

    protected function _selectRecords($query, $var_arr) {
        try {
            $db = new PDOConnector($this->db);
            $ret = $db->selectRecords($query, $var_arr);
            $db->close();
            return $ret;
        } catch (PDOException $e) {
            Util::displayError("System error occurred.", "Please go back", $e);
        }
    }

    protected function _execute($query, $var_arr) {
        try {
            $db = new PDOConnector($this->db);
            $ret = $db->executeQuery($query, $var_arr);
            $this->affectedRowCounts  = $db->affectedRowCounts;
            $db->close();
            return $this->affectedRowCounts;
        } catch (PDOException $e) {
            Util::displayError("System error occurred.", "Please go back", $e);
        }
    }
    

    /**
     * Build SQL statement and WHERE clause
     * 
     * @param array $conditions - Conditions for buidling WHERE clause
     * @return array    $arr['query'] and $arr['var_arr'], which are used for PDO binding params
     */
    private function _buildConditions($conditions, $type = "And") {

        $query = "";
        $var_arr = array();
        $idx = 0;
        $joining_key = $this->joining_hint;
        
        foreach($conditions as $key => $val){
            $joining = strpos($val, $joining_key) !== false;            
            $where_clause = $idx++ ? " {$type}" : "";

            #1. get pure key                
            $params = explode(" ", trim($key));
            $key = strtolower(trim($params[0]));
            $vkey = str_replace(".", "_", $key);

            #2. check if second param exists           
            if(!isset($params[1])){
                if($joining){
                    $val = substr($val, strpos($val, $joining_key)+1);
                    $query .= "{$where_clause} {$key} = {$val}";
                }else{                    
                    $query .= "{$where_clause} {$key} = :{$vkey}";
                    $var_arr[$vkey] = trim($val);
                }
            }
            else{
                $isNegative = $this->_isNegative($params[1]);

                #2-1 . second param is negative
                if($isNegative){

                    #3. check if third param exists
                    if(isset($params[2])){
                        $op = $this->_checkOperator($vkey, $params[2], $val, $isNegative);
                    }
                    else{
                        $op = array(
                            'operator' => "!=",
                            'value' => trim($val)
                        );
                    }
                }
                #2-2 second param is operatior
                else{
                    $op = $this->_checkOperator($vkey, $params[1], $val, $isNegative);
                }

                $isRangeOperator = $op['isRangeOperator'];
                $operator = $op['operator'];

                if(!$isRangeOperator){
                    if($joining){
                        $val = substr($val, strpos($val, $joining_key)+1);
                        $query .= "{$where_clause} {$key} {$operator} {$val}";
                    }else{
                        $query .= "{$where_clause} {$key} {$operator} :{$vkey}";
                        $var_arr[$vkey] = $op['value'];
                    }
                }
                else{
                    //Range opertation exist, such as Between, IN().

                    $var_arr += $op['value'];

                    $prefix = $op['prefix'];
                    $delimiter = $op['delimiter'];
                    $suffix = $op['suffix'];

                    $query .= "{$where_clause} {$key} {$operator}{$prefix}";

                    $length = count($op['value']) - 1;
                    $i = 0;
                    foreach($op['value'] as $k => $v){
                        $query .= ":$k";

                        if($i++ < $length){
                            $query .= "{$delimiter} ";
                        }
                    }
                    $query .= $suffix;
                }
            }
        }

        $where = $query;
        $values = $var_arr;

        return compact('where', 'values');
    }

    /**
     * Check if the $target is a negative operator
     * 
     * @param string $target
     * @return boolean
     */
    private function _isNegative($target) {
        $notations = array(
            '!', '<>', 'NOT'
        );

        $target = strtoupper(trim($target));

        foreach($notations as $notation){
            if(strpos($target, $notation) !== false){
                return true;
            }
        }
        return false;
    }

    /**
     * Check operator type and return corresponding information
     * 
     * @param string $key
     * @param string $target
     * @param mixed $value
     * @param boolean $isNegative
     * @return array
     */
    private function _checkOperator($key, $target, $value, $isNegative) {

        $operators = array(
            'LIKE' => array(
                'syntax' => 'Like',
                'opposite' => 'Not Like'
            ),
            'IS' => array(
                'syntax' => 'IS',
                'opposite' => 'IS NOT'
            ),
            'IS NOT' => array(
                'syntax' => 'IS NOT',
                'opposite' => 'IS'
            ),
            '<' => array(
                'syntax' => '<',
                'opposite' => '>='
            ),
            '<=' => array(
                'syntax' => '<=',
                'opposite' => '>'
            ),
            '>' => array(
                'syntax' => '>',
                'opposite' => '<='
            ),
            '>=' => array(
                'syntax' => '>=',
                'opposite' => '<'
            ),
        );

        $range_operators = array(
            'IN' => array(
                'syntax' => 'In',
                'opposite' => 'Not In'
            ),
            'BETWEEN' => array(
                'syntax' => 'Between',
                'opposite' => 'Not Between',
            ),
        );

        $operator = null;
        $target = strtoupper(trim($target));
        $syntax = !$isNegative ? 'syntax' : 'opposite';
        $prefix = $delimiter = $suffix = "";
        $isRangeOperator = false;


        if(array_key_exists($target, $operators)){
            $operator = $operators[$target][$syntax];
        }
        elseif(array_key_exists($target, $range_operators)){
            $isRangeOperator = true;
            $operator = $range_operators[$target][$syntax];

            if($target == 'IN'){
                $prefix = "(";
                $delimiter = ",";
                $suffix = ")";
            }
            else{
                $prefix = " ";
                $delimiter = " AND";
                $suffix = " ";
            }
            $length = count($value);
            $tmp_val = array();
            for($i = 0; $i < $length; $i++){
                $tmp_val["{$key}_{$i}"] = $value[$i];
            }
            $value = $tmp_val;
        }

        return compact('isRangeOperator', 'operator', 'value', 'prefix', 'delimiter', 'suffix');
    }

}
