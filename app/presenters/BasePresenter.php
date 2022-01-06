<?php

namespace NatureQuizzer\Presenters;

use Nette\Application\UI\Presenter;

abstract class BasePresenter extends Presenter
{

	public function beforeRender()
	{
		parent::beforeRender();
		$this->setLayout('admin-layout');
	}

	/**
	 * Check if user can see this item (used in menu)
	 * @return bool
	 */
	public function can($resource, $operation = \Nette\Security\Authorizator::ALL)
	{
		$user = $this->getUser();
		if (!$user->isAllowed($resource, $operation)) {
			return FALSE;
		} else {
			return TRUE;
		}
	}

	/**
	 * Check for permission (all administration needs role master)
	 * Needs to be specified more precisely
	 */
	protected function perm($operation = \Nette\Security\Authorizator::ALL)
	{
		$user = $this->getUser();
		if ($this->resource === NULL || !$user->isAllowed($this->resource, $operation)) {
			$this->flashMessage('Please login first');
			$this->redirect('Admin:login');
		}
	}
}
