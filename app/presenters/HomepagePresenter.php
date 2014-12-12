<?php

namespace NatureQuizzer\Presenters;

class HomepagePresenter extends BasePresenter
{

	public function beforeRender()
	{
		parent::beforeRender();
		$this->setLayout('layout');

	}

}
