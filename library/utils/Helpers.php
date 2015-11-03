<?php
namespace NatureQuizzer\Utils;

use DateInterval;
use DatePeriod;
use DateTime;
use Nette\Utils\Html;
use Nette\Utils\Strings;

class Helpers
{
	public static function getRepresentationImage($representationId)
	{
		return Html::el('img')->src('/images/organisms/' . $representationId)->style('max-height: 300px; max-width: 300px')->render();
	}

	/**
	 * Returns an array of time intervals (default is days) between given dates
	 * @param DateTime $start
	 * @param DateTime $end
	 * @param string $period
	 * @return array
	 */
	public static function getDatePeriod($start, $end, $period = '1 day')
	{
		return iterator_to_array(new DatePeriod($start, DateInterval::createFromDateString($period), $end));
	}

	public static function confirmPrompt($prompt = "", $default = FALSE)
	{
		if ($default == FALSE) {
			$legend = '[y/N]';
		} else {
			$legend = '[Y/n]';
		}
		printf("%s%s: ", $prompt . ' ', $legend);
		$input = fgets(STDIN);
		if (Strings::lower($input) == "y\n") {
			return TRUE;
		} else {
			return FALSE;
		}
	}

}