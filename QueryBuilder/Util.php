<?php

/**
 * Description of Common
 *
 * @author JunYoungK
 */
require_once(__ROOT__ . '/h2owirelessnow_session.php');
require_once(__ROOT__ . '/components/QueryBuilder/BaseQuery.php');

class Util {

    /**
     * Returns a formatted SQL datetime string using the given $timestamp 
     * 
     * @param string $timestamp - Base timestamp
     * @param integer $diff - Difference from the default $timestamp
     * @param string $unit - Timestamp unit
     * @return string
     */
    public static function date($timestamp = "Current", $diff = 0, $unit = null) {

        $DATE_FORMAT = array(
            'CURRENT' => "Y-m-d H:i:s",
            'TODAY' => "Y-m-d"
        );

        $UNITS = array(
            'SECOND',
            'MINUTE',
            'HOUR',
            'DAY',
            'MONTH',
            'YEAR'
        );

        $timestamp = strtoupper($timestamp);

        if(isset($timestamp) && array_key_exists($timestamp, $DATE_FORMAT)){
            $effectiveDate = date($DATE_FORMAT[$timestamp]);

            if(isset($diff) && is_numeric($diff) && isset($unit) && in_array(strtoupper($unit), $UNITS)){
                $sign = $diff > 0 ? "+" : "-";
                $num = abs($diff);
                $unit = strtoupper($unit) . 'S';
                $effectiveDate = date($DATE_FORMAT[$timestamp], strtotime("{$effectiveDate} {$sign}{$num} {$unit}"));
            }
            return $effectiveDate;
        }
        else{
            return date($DATE_FORMAT['TODAY']);
        }
    }

    /**
     * Check if $table exists in the selected DB
     * 
     * @param string $table - Table name that exists in the selected db
     * @return boolean
     */
    public static function lookupTable($table, $target = "h2owirelessnow") {
        $query = " Select count(*) count From systables 
                    Where tabtype ='T'
                      And tabname = :table
                 ";
        $var_arr = compact('table');
        try {
            $db = new PDOConnector($target);
            $ret = $db->selectRecords($query, $var_arr);
            $db->close();
            return $ret[0]['count'] ? true : false;
        } catch (PDOException $e) {
            self::displayError("System error occurred.", "Please go back", $e);
        }
    }

    public static function displayError($msg_title, $msg_body, $e = array()) {
        if(EXEC_ENV === "REAL"){
            $_SESSION['ERROR_TITLE'] = $msg_title;
            $_SESSION['ERROR_BODY'] = $msg_body;
            exit("<script language=JavaScript><!--location.replace('mainControl.php?page=notifyError');//--></script>");
        }
        else{
            dd($e);
            die();
        }
    }

}
