<?php defined('SYSPATH') or die('No direct script access.');

abstract class Controller_Common extends Controller_Template {


	public function before()
	{
		parent::before();
		View::set_global('title', 'IronDog');
		View::set_global('description', 'Подбор электричек');
		$this->template->content = '';
		$this->template->styles = array('bootstrap', 'style');
		$this->template->scripts = '';
	}

} // End Common