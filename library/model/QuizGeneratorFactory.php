<?php

namespace NatureQuizzer\Model;


use NatureQuizzer\Database\Model\Setting;
use Nette\InvalidStateException;

class QuizGeneratorFactory
{
	private $setting;
	private $basicEloRandomDistractors;

	public function __construct(Setting $setting, BasicEloRandomDistractors $basicEloRandomDistractors)
	{
		$this->setting = $setting;
		$this->basicEloRandomDistractors = $basicEloRandomDistractors;
	}

	public function get($userId)
	{
		$temp = $this->setting->getModelNameByUser($userId);
		return $this->getModel($temp->name);
	}
	public function getModel($model)
	{
		if ($model == IQuizGenerator::SIMPLE_ELO_RANDOM_DISTRACTORS) {
			return $this->basicEloRandomDistractors;
		} else if ($model == IQuizGenerator::SIMPLE_ELO_TAXONOMY_DISTRACTORS) {
			return $this->basicEloRandomDistractors; // TBD
		} else {
			throw new InvalidStateException('QuizGenerator named [' . $model .'] is non-existent.');
		}
	}
}