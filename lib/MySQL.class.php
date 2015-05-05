<?php

require_once LIB . '/MySQLResult.class.php';

class DatabaseException extends Exception {}

class MySQL {
	/**
	* Pool of available connections
	* 
	* @var mixed
	*/
	static $conn_pool = array();
	
	/**
	* Current connections
	* 
	* @var mixed
	*/
	private $connections = array('masters' => array(), 'slaves' => array());
	
	/**
	* The CURRENT connection for the CURRENT query
	* 
	* @var mixed
	*/
	private $conn = null;
	
	/**
	* Current/last master connection
	* 
	* @var mixed
	*/
	private $master_conn = null;
	
	/**
	* Current/last slave connection
	* 
	* @var mixed
	*/
	private $slave_conn = null;
	
	public function __construct($connections = array()) {
		$this->addConnections($connections);
	}
	
	private function addConnection($host, $user, $pass, $name, $is_master) {
		$conn_md5 = md5($host . $user . $pass . $name);
		
		$conn_key = ($is_master) ? 'masters' : 'slaves';
		
		if (empty($this->connections[$conn_key][$conn_md5])) {
			if (empty(self::$conn_pool[$conn_md5])) {
				self::$conn_pool[$conn_md5] = array(
					'host' => $host,
					'user' => $user,
					'pass' => $pass,
					'name' => $name,
					'conn' => null
				);
			}
			$this->connections[$conn_key][$conn_md5] = &self::$conn_pool[$conn_md5];
		}
	}
	
	private function addConnections($connections) {
		foreach ($connections as $conn) {
			$this->addConnection($conn['host'], $conn['user'], $conn['pass'], $conn['name'], $conn['is_master']);
		}
	}
	
    protected function haveConnections() {
        return ($this->connections['masters'] || $this->connections['slaves']);
    }
	
	public function query($query, $force_master = false, $cache_ttl = 0) {
		// SELECT statements can go to slaves unless forced upon master
        if (!$force_master && strtoupper(substr(trim($query), 0, 6)) == 'SELECT') {
            $this->connectToASlave();
        } else {
            $this->connectToAMaster();
        }
        
        //echo '<pre>' . $query . '</pre><br />';
        
        /**
        * @todo caching
        */
        $pre_result = $this->conn->query($query);
        
        if ($pre_result instanceof MySQLi_Result) {
        	$result = new MySQLResult($pre_result);
		} else {
			$result = $pre_result;
		}
        
        return $result;
	}
	
	private function connectToAMaster() {
		if (!$this->master_conn) {
			/**
			* @todo multiple attempts/tries at different servers
			*/
			$server = &$this->getRandomMaster();
			if ($server['conn']) {
				$this->master_conn = &$server['conn'];
			} else { // We're not connected to this server yet
				try {
					$this->master_conn = &$this->connect($server);
				} catch (Exception $e) {
					// it'll get caught below
					//throw new Exception('Unable to connect to MySQL');
				}
			}
		}
		
		// After all that, still no slave connection!
		if (!$this->master_conn) {
			throw new Exception('Unable to connect to MySQL');
		}
		
		$this->conn = &$this->master_conn;
	}
	
	private function connectToASlave() {
		if (!$this->slave_conn) {
			/**
			* @todo multiple attempts/tries at different servers
			*/
			$server = &$this->getRandomSlave();
			
			if ($server['conn']) {
				$this->slave_conn = &$server['conn'];
			} else { // We're not connected to this server yet
				try {
					$this->slave_conn = &$this->connect($server);
				} catch (Exception $e) {
					// it'll get caught below
					//throw new Exception('Unable to connect to MySQL');
				}
			}
		}
		
		// After all that, still no slave connection!
		if (!$this->slave_conn) {
			throw new Exception('Unable to connect to MySQL');
		}
		
		$this->conn = &$this->slave_conn;
	}
	
	private function &connect(&$conn_array) {
		$conn_array['conn'] = new mysqli($conn_array['host'], $conn_array['user'], $conn_array['pass'], $conn_array['name']);
		
		// Do we have touchdown?
        if ($conn_array['conn'] && !$conn_array['conn']->connect_error) {
			$conn_array['conn']->set_charset('utf8');
            return $conn_array['conn'];
        } else {
			throw new Exception('Unable to connect to MySQL');
        }
	}
	
	
	private function &getRandomMaster() {
		if (!empty($this->connections['masters'])) {
			$index = array_rand($this->connections['masters']);
			return $this->connections['masters'][$index];
		} else {
			// Seriously!? No connections!? :(
			$return = false;
			return $return;
		}
	}
	
	private function &getRandomSlave() {
		if (!empty($this->connections['slaves'])) {
			$index = array_rand($this->connections['slaves']);
			return $this->connections['slaves'][$index];
		} else {
			return $this->getRandomMaster();
		}
	}
	
    public function prepVal($value, $force_str = false) {
        if (!$force_str && is_bool($value)) {
    	    $value = $value * 1;
    	} elseif (!$force_str && is_null($value)) {
			$value = 'NULL';
    	} else {
    	    if (!is_numeric($value) || $force_str) {
                $value = "'" . $this->escape($value) . "'";
    	    }
        }
    	return $value;
    }
    
	public function prepField($field) {
		return '`' . str_replace('`', '', $field) . '`';
	}

    protected function escape($string) {
        if ($this->conn) {
            return $this->conn->escape_string($string);
        } elseif ($this->haveConnections()) {
            try {
            	$this->connectToASlave();
                return $this->conn->escape_string($string);
            } catch (DatabaseException $de) {}
        }
        
        throw new Exception('Cannot escape; no MySQL connection present');
    }
    
    /**
    * Provide access directly the mysqli connection properties
    */
    public function __get($key) {
        if (!empty($this->conn->$key)) {
            return $this->conn->$key;
        }
    }
}

?>