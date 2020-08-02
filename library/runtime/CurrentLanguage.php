<?php
namespace NatureQuizzer\Runtime;
use Nette\SmartObject;

class CurrentLanguage
{
	use SmartObject;

	public function get()
	{
		return 1; /* CZECH FOR NOW */
	}
}