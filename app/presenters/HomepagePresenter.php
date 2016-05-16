<?php

namespace NatureQuizzer\Presenters;

use NatureQuizzer\Database\Model\Concept;
use NatureQuizzer\Runtime\CurrentLanguage;
use NatureQuizzer\Utils\Sitemap;
use NatureQuizzer\Utils\XmlResponse;

class HomepagePresenter extends BasePresenter
{
	/** @var Concept */
	private $concept;
	/** @var CurrentLanguage */
	private $currentLanguage;

	public function injectHomepages(Concept $concept, CurrentLanguage $currentLanguage)
	{
		$this->concept = $concept;
		$this->currentLanguage = $currentLanguage;
	}

	public function beforeRender()
	{
		parent::beforeRender();
		$this->setLayout('layout');
	}

	public function actionTemp()
	{
		$this->setView('default');
	}

	public function actionSitemap()
	{
		$baseLink = $this->link('//Homepage:');
		$sitemap = new Sitemap();


		$sitemap->addEntry($baseLink, Sitemap::WEEKLY, 0.8);	// Landing page
		$sitemap->addEntry($baseLink . 'concepts');	// Topics
		$sitemap->addEntry($baseLink . 'about'); 	// About
		$sitemap->addEntry($baseLink . 'terms'); 	// Terms
		$sitemap->addEntry($baseLink . 'play/mix/all', Sitemap::ALWAYS, 0.8);	// Random mix
		// List all topics
		$topics = $this->concept->getAllWithInfo($this->currentLanguage->get());
		foreach ($topics as $topic) {
			$sitemap->addEntry($baseLink . 'play/' . $topic->id_concept . '/' . $topic->code_name, Sitemap::ALWAYS, 0.9);
		}

		$content = $sitemap->compile();
		$this->sendResponse(new XmlResponse($content, 'application/xml'));
	}

	// Needed action due to history API
	public function actionOffline() { $this->setView('default'); }
	public function actionAbout() { $this->setView('default'); }
	public function actionTerms() { $this->setView('default'); }
	public function actionFacebookLoginProblem($type = null) { $this->setView('default'); }
	public function actionGoogleLoginProblem($type = null) { $this->setView('default'); }
	public function actionPlay($conceptId, $codeName) { $this->setView('default'); }
	public function actionResult($conceptId) { $this->setView('default'); }
	public function actionConcepts() { $this->setView('default'); }
	public function actionUserLogin() { $this->setView('default'); }
	public function actionUserLogout() { $this->setView('default'); }
	public function actionUserRegister() { $this->setView('default'); }

}
