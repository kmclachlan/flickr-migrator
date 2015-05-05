<?php

class Template extends Smarty {
	const AREA_ADMIN = 'admin';
	const AREA_WEB = 'web';

	public function __construct() {
		parent::__construct();
	}
	
	public function setupWebEnvironment($area = self::AREA_WEB) {
		if ($area == self::AREA_ADMIN) {
			$this->setTemplateDir(WEB_DOC_ROOT . '/admin/templates/');
			$this->compile_dir = WEB_DOC_ROOT . '/admin/templates_c/';
		} else {
			$this->setTemplateDir(WEB_DOC_ROOT . '/templates/');
			$this->compile_dir = WEB_DOC_ROOT . '/templates_c/';
			
			$head_params = array(
				'meta' => array(
					'name' => array(
						'robots' => 'index,follow'
					),
					'http-equiv' => array(
						'content-type' => 'text/html; charset=utf-8'
					)
				)
			);
	        
			$this->assign('head', $head_params);
		}
	}
}

?>
