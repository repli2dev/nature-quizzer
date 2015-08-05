<?php

namespace NatureQuizzer\Model;


/**
 * This is interface for models which encapsulate:
 *  - selection of questions
 *  - selection of question distractors
 *  - selection of type of question
 *
 * Some expectations:
 *  - Constants of models names are synced with database table `setting`
 *
 * Historical development:
 *  1. Simple ELO for selection questions with random distractors
 *  2. Simple ELO for selection questions with taxonomy distractors
 */
interface IQuizGenerator
{
	const SIMPLE_ELO_RANDOM_DISTRACTORS = 'SIMPLE_ELO_RANDOM_DISTRACTORS';
	const SIMPLE_ELO_TAXONOMY_DISTRACTORS = 'SIMPLE_ELO_TAXONOMY_DISTRACTORS';

	/**
	 * For particular user and concept returns specified number of questions.
	 *
	 * @param $userId
	 * @param $concept
	 * @param $count
	 * @return mixed
	 */
	public function get($userId, $concept, $count);
}