<?php
namespace NatureQuizzer\Database\Utils;


use NatureQuizzer\Database\Model\Language;

class LanguageLookup
{
	/** @var Language */
	private $language;

	private $storage;

	public function __construct(Language $language)
	{
		$this->language = $language;
	}

	public function getId($code)
	{
		if (!isset($this->storage[$code])) {
			$row = $this->language->findByCode($code);
			if ($row === NULL) {
				throw new \Exception('No such language with code [' . $code . ']');
			}
			$this->storage[$code] = $row;
		}
		return $this->storage[$code]->id_language;
	}
}