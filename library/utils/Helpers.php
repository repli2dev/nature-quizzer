<?php
namespace NatureQuizzer\Utils;

use Nette\Utils\Html;

class Helpers
{
	public static function getRepresentationImage($representationId)
	{
		return Html::el('img')->src('/images/organisms/' . $representationId)->style('max-height: 300px; max-width: 300px')->render();
	}
}