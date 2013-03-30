<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Home extends  Controller_Common {

	public function action_index()
	{

		$train = new Model_Train();

		$trains = $train -> find_all();

		$home = View::factory('home');

		$home->trains = $trains;

		$content = $this->response->body($home);

		$this->template->content = $content;

		$this->template->title = 'Проложить маршрут';

		$this->template->description = 'Проложить маршрут на электричках';

	}

} // End Welcome
