<?php

class MySQLResult {
	private $mysqli_result = null;
	
	public function __construct(mysqli_result $result) {
		$this->mysqli_result = $result;
	}
	
    public function fetch_all() {
        $this->data_seek(0);
        $rows = array();
        while ($row = $this->fetch_assoc()) {
            $rows[] = $row;
        }
        $this->data_seek(0);
        return $rows;
    }
	
    public function fetch_all_values($column = 0) {
        $this->data_seek(0);
        $values = array();
        while ($row = $this->fetch_row()) {
            $values[] = $row[$column];
        }
        return $values;
    }
    
    public function fetch_value($column = 0) {
        $row = $this->mysqli_result->fetch_row();
        return $row[$column];
    }

	public function __call($name, $arguments) {
        return call_user_func_array(array($this->mysqli_result, $name), $arguments);
    }

    public function __set($name, $value) {
        $this->mysqli_result->$name = $value;
    }

    public function __get($name) {
        return $this->mysqli_result->$name;
    }
}

?>