<?php

namespace NatureQuizzer\Presenters;

use Exception;
use NatureQuizzer\Processors\UserProcessor;
use NatureQuizzer\Utils\Facebook;
use NatureQuizzer\Utils\Google;
use Nette\Application\AbortException;
use Tracy\Debugger;
use Tracy\ILogger;

class ExternalPresenter extends BasePresenter
{

	/** @var Facebook */
	private $facebook;
	/** @var Google */
	private $google;

	/** @var UserProcessor */
	private $userProcessor;

	public function injectExternalDependencies(Facebook $facebook, Google $google, UserProcessor $userProcessor)
	{
		$this->facebook = $facebook;
		$this->google = $google;
		$this->userProcessor = $userProcessor;
	}

	public function actionFb()
	{
		Debugger::enable(Debugger::PRODUCTION); // Suppress Tracy bar as it is causing problems with redirect
		$result = NULL;
		try {
			$result = $this->facebook->authenticate($this->link('//this'));
		} catch (Exception $ex) {
			if ($ex instanceof AbortException) {
				throw $ex;
			}
			Debugger::log($ex, ILogger::EXCEPTION);
			if ($ex->getCode() === Facebook::NOT_AVAILABLE) {
				$this->redirect('Homepage:default#/facebook-login-problem?type=4');
			} else {
				$this->redirect('Homepage:default#/facebook-login-problem?type=1');
			}
			$this->terminate();
		}
		if ($result == NULL) {
			Debugger::log('Obtaining of Facebook session failed for unknown reason.', ILogger::EXCEPTION);
			$this->redirect('Homepage:default#/facebook-login-problem?type=2');
			$this->terminate();
		}
		try {
			$this->userProcessor->loginViaFacebook($result);
		} catch (Exception $ex) {
			Debugger::log($ex, ILogger::EXCEPTION);
			$this->redirect('Homepage:default#/facebook-login-problem?type=3');
			$this->terminate();
		}
		$this->redirect('Homepage:default#/');
		$this->terminate();
	}

	public function actionGoogle()
	{
		$result = NULL;
		try {
			$result = $this->google->authenticate($this->link('//this'));
		} catch (Exception $ex) {
			if ($ex instanceof AbortException) {
				throw $ex;
			}
			Debugger::log($ex, ILogger::EXCEPTION);
			if ($ex->getCode() === Google::NOT_AVAILABLE) {
				$this->redirect('Homepage:default#/google-login-problem?type=4');
			} else {
				$this->redirect('Homepage:default#/google-login-problem?type=1');
			}
			$this->terminate();
		}
		if ($result == NULL) {
			Debugger::log('Obtaining of Google session failed for unknown reason.', ILogger::EXCEPTION);
			$this->redirect('Homepage:default#/google-login-problem?type=2');
			$this->terminate();
		}
		try {
			$this->userProcessor->loginViaGoogle($result);
		} catch (Exception $ex) {
			Debugger::log($ex, ILogger::EXCEPTION);
			$this->redirect('Homepage:default#/google-login-problem?type=3');
			$this->terminate();
		}
		$this->redirect('Homepage:default#/');
		$this->terminate();
	}

}
