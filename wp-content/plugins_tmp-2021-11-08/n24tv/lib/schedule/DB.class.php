<?php
class N24TV_Schedule_DB_Exception extends Exception {

}

class N24TV_Schedule_DB extends mysqli {

    static private $instance    = null;

    static public function getInstance(){
        if (self::$instance === null){
            self::$instance = new self(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            if (self::$instance->connect_error){
                throw new Exception('MySQL Connect error: ' . self::$instance->connect_error);
            }
            $ret = self::$instance->set_charset(DB_CHARSET);
            $ret = self::$instance->query("SET NAMES 'utf8'");
        }
        return self::$instance;
    }

    public function query($query, $resultmode = MYSQLI_STORE_RESULT){
        $res = parent::query($query, $resultmode);
        if ($res === false){
            throw new N24TV_Schedule_DB_Exception($this->error);
        }
        return $res;
    }

}
