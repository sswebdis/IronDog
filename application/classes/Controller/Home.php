<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Home extends  Controller_Common {

	public function action_index()
	{

		$content = $this->response->body(View::factory('home'));

		$this->template->content = $content;

		$this->template->title = 'Проложить маршрут';

		$this->template->description = 'Проложить маршрут на электричках';

	}

} // End Welcome
