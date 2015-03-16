<?php

namespace NatureQuizzer\Presenters;

use Exception;
use NatureQuizzer\Processors\UserProcessor;
use NatureQuizzer\Utils\Facebook;

class ExternalPresenter extends BasePresenter
{

	/** @var Facebook */
	private $facebook;

	/** @var UserProcessor */
	private $userProcessor;

	public function injectExternalDependencies(Facebook $facebook, UserProcessor $userProcessor)
	{
		$this->facebook = $facebook;
		$this->userProcessor = $userProcessor;
	}

	public function actionFb()
	{
		$result = NULL;
		try {
			$result = $this->facebook->authenticate($this->link('//this'));
		} catch (Exception $ex) {
			$this->redirect('Homepage:default#/facebook-login-problem');
			$this->terminate();
		}
		if ($result == NULL) {
			$this->redirect('Homepage:default#/facebook-login-problem');
			$this->terminate();
		}
		$this->userProcessor->loginViaFacebook($result);
	}

}
