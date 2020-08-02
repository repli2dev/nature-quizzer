<?php

namespace NatureQuizzer\Utils;

use Latte\Engine;
use Nette\SmartObject;
use stdClass;

class Sitemap
{
	use SmartObject;

	const ALWAYS = 'always',
		  HOURLY = 'hourly',
		  DAILY = 'daily',
		  WEEKLY = 'weekly',
		  MONTHLY = 'monthly',
		  YEARLY = 'yearly',
		  NEVER = 'never';

	private $entries = [];

	public function addEntry($location, $changefreq = self::WEEKLY, $priority = null, $lastmod = null)
	{
		$temp = new stdClass();
		$temp->loc = $location;
		$temp->lastmod = $lastmod;
		$temp->changefreq = $changefreq;
		$temp->priority = $priority ?: 0.5;
		$this->entries[] = $temp;
	}

	public function compile()
	{
		$parameters = ['entries' => $this->entries];

		$latte = new Engine;
		return $latte->renderToString(__DIR__ . '/Sitemap.latte', $parameters);
	}
}