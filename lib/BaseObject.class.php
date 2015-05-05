<?php

abstract class BaseObject {
	const BIT_NO	= 0;
	const BIT_YES	= 1;
	
	public function __construct($id = 0) {
		if ($id && static::$table && static::$primary_key) {
			$sql = static::getDB();
			$table = $sql->prepField(static::$table);
			$pk = $sql->prepField(static::$primary_key);
			
			$item_res = $sql->query('SELECT * FROM ' . $table . ' WHERE ' . $pk . ' = ' . $sql->prepVal($id));
			if ($item_res->num_rows) {
				$this->load($item_res->fetch_assoc());
			}
		}
	}
	
	public function load($data) {
		$data = (array) $data;	// Convert objects to an array...
        $vars = get_class_vars(get_class($this));
        foreach ($vars as $name => $default) {
            if (isset($data[$name])) {
                $this->$name = $data[$name];
            }
        }
    }
    
	public function insert() {
		$sql = static::getDB();
		
		$fields = static::$columns;
		
		$qry = 'INSERT INTO ' . $sql->prepField(static::$table) . ' SET ';
		$qry_fields = array();
		foreach (static::$columns as $column) {
			if ($column != static::$primary_key) {
				$qry_fields[] = $sql->prepField($column) . ' = ' . $sql->prepVal($this->$column);
			}
		}
		$qry .= implode(',', $qry_fields);
		
		$sql->query($qry);
		
		$this->{static::$primary_key} = $sql->insert_id;
	}
	
	public function update() {
		$sql = static::getDB();
		
		$fields = static::$columns;
		
		$qry = 'UPDATE ' . $sql->prepField(static::$table) . ' SET ';
		$qry_fields = array();
		foreach (static::$columns as $column) {
			if ($column != static::$primary_key) {
				$qry_fields[] = $sql->prepField($column) . ' = ' . $sql->prepVal($this->$column);
			}
		}
		$qry .= implode(',', $qry_fields);
		$qry .= ' WHERE ' . $sql->prepField(static::$primary_key) . ' = ' . $sql->prepVal($this->{static::$primary_key});

		$sql->query($qry);
	}
    
    public function delete() {
		$sql = static::getDB();
		$sql->query('DELETE FROM ' . $sql->prepField(static::$table) . ' WHERE ' . static::$primary_key . ' = ' . $sql->prepVal($this->{static::$primary_key}));
    }
	
	protected static function getDB() {
		return new MySQL(Config::getDB('APP', 'MAIN'));
	}
	
	public static function getStatusOptions() {
		$refl = new ReflectionClass(get_called_class());
		
		$constants = $refl->getConstants();
		$statuses = array();
		foreach ($constants as $const_name => $value) {
			if (substr($const_name, 0, 6) == 'STATUS') {
				$statuses[$value] = ucwords(str_replace('_', ' ', $value));
			}
		}
	
		return $statuses;
	}
}

?>