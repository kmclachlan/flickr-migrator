<?php

class BaseFilter {
	public $page				= 1;
	public $per_page			= 25;

	const SORT_ASC	= 'ASC';
	const SORT_DESC	= 'DESC';

	protected function setFilter($filter) {
		if (!empty($filter['order'])) {
			$this->order = $filter['order'];
		}
		if (!empty($filter['sort'])) {
			$this->sort = $filter['sort'];
		}
	}
}

?>
