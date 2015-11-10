<?php

/**
 * Description of SelectQuery
 *
 * @author JunYoungK
 */

class SelectQuery extends BaseQuery {
    
    /* defined in BaseQuery
    protected $db;
    protected $method;
    protected $indexes;
    protected $where;
    protected $values;     
    */
    
    private $columns;
    private $from;
    private $join;
    private $group_by;
    private $having;
    private $order_by;
    
    
    function __construct($columns = "*", $db) {        
        parent::__construct($db);
        
        $this->method = "SELECT";
        if(is_array($columns)){
            $columns = implode(", ", $columns);
        }
        $this->columns = $columns;

        return $this;
    }
    
    protected function buildQuery() {        
        $this->query = $this->method . " ";
        $this->query .= " " . $this->indexes ? $this->indexes : " ";
        $this->query .= " " . $this->columns ? $this->columns : "*";
        $this->query .= " " . $this->from;
        $this->query .= " " . $this->join;
        if($this->where){
            $this->query .= $this->join ? "And" . $this->where : "Where" . $this->where;
        }
        $this->query .= " " . $this->group_by;
        $this->query .= " " . $this->having;
        $this->query .= " " . $this->order_by;
    }
    
        
    public function get() {
        $allowed_methods = array('SELECT');

        if(!in_array(strtoupper($this->method), $allowed_methods)){
            return false;
        }
        $this->buildQuery();
        $ret = $this->_selectRecords($this->query, $this->values);
        $this->clearQuery();
        
        return $ret;
    }    
    
    public function first($num = 1, $skip = 0) {
        if(is_numeric($num) && is_numeric($skip)){
            $skip = $skip ? "Skip $skip" : "";
            $this->method = "Select";
            $this->columns = " $skip First $num $this->columns";
            return $this->get();
        }
    }

    public function count() {
        $this->method = "SELECT";
        $this->columns = "Count(*) count";
        return $this->get();
    }    


    //Example - $qb->from('table');
    //Example - $qb->from('table a');
    public function from($table) {
        if(isset($table)){
            $t = explode(" ", $table);
            $table = trim($t[0]);
            $alias = isset($t[1]) ? trim($t[1]) : "";
            
            if(Util::lookupTable($table,  $this->db)){
                $this->from = "From $table $alias";
                return $this;
            }
        }
        
        return null;
    }

    // Example - Join('table1 b', ['b.id' => 'a.id']);
    // Example - Join(['table1 b', 'table2 c'], ['b.id' => 'a.id', 'c.cid' => 'b.cid']);
    public function join($tables, $mappings = array(), $join_type = 'INNER') {
        $from = "";
        $join = "";
        
        $allowed_join_types = array(
            'OUTER'
        );        
        $join_type_clause = in_array(strtoupper($join_type), $allowed_join_types)? " " . strtoupper($join_type) : "";
        
        if($this->from){
            if(!is_array($tables)){
                $t = explode(" ", $tables);
                $table = trim($t[0]);
                $alias = trim($t[1]);
                if(Util::lookupTable($table, $this->db)){
                    $from = ",{$join_type_clause} {$table} {$alias}";
                }
            }
            else{
                $from = "";
                $index = 0;
                foreach($tables as $table){
                    $t = explode(" ", $table);
                    $table = trim($t[0]);
                    $alias = trim($t[1]);
                    if(Util::lookupTable($table,  $this->db)){
                        $from .= ",{$join_type_clause} {$table} {$alias}";
                    }
                    else{
                        $from = "";
                        break;
                    }
                    $index++;
                }
            }

            if(is_array($mappings) && count($mappings)){
                $idx = 0;
                foreach($mappings as $tab1 => $tab2){
                    $where_clause = ($idx++ > 0 && !$this->join)? "And" : "Where";
                    $join .= "{$where_clause} {$tab1} = {$tab2} ";
                    $idx++;
                }
            }
        }
        $this->from .= $from;
        $this->join = $join;
        return $this;
    }

    //$qb->groupBy('table.col');
    //$qb->groupBy(['table.col1', 'table.col2']);
    public function groupBy($group_by) {
        if(isset($group_by)){
            $group_by = is_array($group_by) ? $group_by : array($group_by);
            $this->group_by = "Group by " . implode(", ", $group_by);
            return $this;
        }
    }

    public function having($conditions) {
        if(isset($conditions)){
            $conditions = is_array($conditions) ? $conditions : array($conditions);
            $cond = $this->_buildConditions($conditions);
            $this->having = "Having " . $cond['where'];
            $this->values += $cond['values'];
            return $this;
        }
    }

    public function orderBy($order_by, $order = 'asc') {
        if(isset($order_by)){
            $order_by = is_array($order_by) ? $order_by : array($order_by);
            $order = is_array($order) ? $order : array($order);

            $t_order_by = "Order By";
            $or = new ArrayObject($order);
            $it = $or->getIterator();
            foreach($order_by as $each){
                $order = $it->valid() ? $it->current() : 'asc';
                $t_order_by .= " $each $order,";
                $it->next();
            }

            $this->order_by = substr($t_order_by, 0, strlen($t_order_by) - 1);
            return $this;
        }
    }
    
    public function clearQuery() {
        foreach(get_class_vars(get_class($this)) as $name => $default){            
            $this->$name = $default;
        }
    }
    
    
}
