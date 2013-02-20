<?php

namespace net\shawn_huang\pretty\action;
use \net\shawn_huang\pretty as p;

class WelcomeAction extends p\Action {

  protected function run() {
		$this->setView(null, 'welcome');
	}
}
