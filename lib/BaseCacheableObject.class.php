<?php

abstract class BaseCacheableObject extends BaseObject {
	protected static $cache_handler = false;

	protected static $local_store	= array();
	
	protected static $cache_ttl;
	
	public function __construct($id = 0) {
		parent::__construct($id);
		$this->cache();
	}
	
	public function insert() {
		parent::insert();
		$this->cache();
	}
	
	public function update() {
		parent::update();
		$this->cache();
	}
	
	protected static function getItemByField($field_name, $field_value) {
		$class_name = get_called_class();
		if (empty(self::$local_store[$class_name])) { // No local store for this Object yet
			self::$local_store[$class_name] = array();
		}
		
		$local = &self::$local_store[$class_name];
		
		$sql = static::getDB();
		$res = $sql->query('SELECT * FROM ' . $sql->prepField(static::$table) . ' WHERE ' . $sql->prepField($field_name) . ' = ' . $sql->prepVal($field_value));
		$row = $res->fetch_assoc();
		
		if (empty($row)) {
			throw new ItemNotFoundException('No ' . $class_name . ' for ' . $field_name . ' = ' . $field_value);
		}
		
		$obj = new $class_name;
		$obj->load($row);
		$obj->cache();
		
		$local[$obj->{static::$primary_key}] = $obj;
		
		return $obj;
	}
	
	protected static function getItemById($id = 0) {
		$class_name = get_called_class();
		if (empty(self::$local_store[$class_name])) { // No local store for this Object yet
			self::$local_store[$class_name] = array();
		}
		
		$local = &self::$local_store[$class_name];
		
		if (empty($local[$id])) {
			//self::$local_store[$class_name][$id] = new $class_name($id);
			$cache_handler = self::getCacheHandler();
			$cache_key = $class_name . '_' . $id;
			if ((!$cache_handler || ($local[$id] = $cache_handler->get($cache_key)) === false)) {
				$local[$id] = new $class_name($id);
			}
		}
		
		return $local[$id];
	}
	
	protected static function getItemsByIds($ids) {
		if (empty($ids)) {
			return array();
		}
		
		$class_name = get_called_class();
		if (empty(self::$local_store[$class_name])) { // No local store for this Object yet
			self::$local_store[$class_name] = array();
		}
		
		$local = &self::$local_store[$class_name];
		
		$result = array();
		// Fetch from the caller's local store first
		foreach ($ids as $item_id) {
			if (!empty($local_store[$item_id])) {
				$result[$item_id] = $local_store[$item_id];
			}
		}
		
		if (count($ids) != count($result)) { // We still have more to go
			$primary_key = static::$primary_key;
			
			if ($cache_handler = self::getCacheHandler()) {
				$leftover_ids = array_diff($ids, array_keys($result));
				$cache_keys = array();
				foreach ($leftover_ids as $item_id) {
					$cache_keys[] = $class_name . '_' . $item_id;
				}
				$cached_items = $cache_handler->getMultiple($cache_keys);
				foreach ($cached_items as $item) {
					$local[$item->$primary_key] = $item;
					$result[$item->$primary_key] = $local[$item->$primary_key];
				}
			}
			
			// Alright, we're going to have to hit the DB to fetch these
			$leftover_ids = array_diff($ids, array_keys($result));
			foreach ($leftover_ids as $item_id) {
				$local_store[$item_id] = $result[$item_id] = new $class_name($item_id);
			}
			
			$items = array();
			foreach ($ids as $item_id) {
				if (!empty($result[$item_id])) {
					$items[] = $result[$item_id];
				}
			}
		} else {
			$items = array_values($result);
		}
		
		return $items;
	}
	
	public function cache() {
		if ($this->{static::$primary_key}) { // Let's not cache empty objects (id=0)
			if (static::$cache_ttl) { // Some objects may want to override the default
				$ttl = static::$cache_ttl;
			} else {
				$ttl = Cache::TTL_LONG;
			}
			
			$obj_name = get_class($this);
			
			$cache_handler = $obj_name::getCacheHandler();
			if ($cache_handler) {
				$cache_key = $obj_name . '_' . $this->{static::$primary_key};
				return $cache_handler->set($cache_key, $this, $ttl);
			}
		}
		
		return false;
	}
	
	public function uncache() {
		
	}
	
	protected static function getCacheHandler() {
		if (!empty(static::$cache_handler)) {
			return static::$cache_handler;
		} elseif (!empty(self::$cache_handler)) {
			return self::$cache_handler;
		} elseif (self::$cache_handler = Cache::getDefaultCacheHandler()) {
			return self::$cache_handler;
		}
		return false;
	}
	
	public static function setCacheHandler(Cache $cache_handler) {
		var_dump(static::$cache_handler);
		static::$cache_handler = $cache_handler;
	}
	
	public static function setDefaultCacheHandler(Cache $cache_handler) {
		self::$cache_handler = $cache_handler;
	}
}

?>