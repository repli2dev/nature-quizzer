<?php

namespace NatureQuizzer\Model;
use NatureQuizzer\Model\Utils\UserAnswer;


/**
 * This is interface for models which encapsulate:
 *  1. Quiz Generation (from model data)
 *     - selection of questions
 *     - selection of question distractors
 *     - selection of type of question
 *  2. Post answer update (of model data)
 *
 * More abstractly, this is a facade which shields details about managing and using model data.
 *
 * Some expectations:
 *  - Constants of models names are synced with database table `model`
 */
interface IModelFacade
{

	/**
	 * Returns (code) name consistent with its `model` entry.
	 * @return string Unique code name of the model
	 */
	public function getName();


	/**
	 * Returns ID of model consistent with its `model` entry.
	 * @return int ID of model
	 */
	public function getId();

	/**
	 * Returns ID of model to which the model data are persisted.
	 * When models are independent this should return the same value as getId().
	 * However, for some situation the override may be useful.
	 * @return int ID of model to persist to
	 */
	public function getPersistenceId();

	/**
	 * For particular user and concept returns specified number of questions.
	 *
	 * The expected output is following:
	 * [
	 *   [
	 *     'type' => QuestionType::CHOOSE_NAME,
	 *     'questionImage' => '<HTML of image to show>',
	 *     'questionImageRightsHolder' => '<right holder>',
	 *     'questionImageLicense' => '<license>',
	 *     'options' => [
	 *       [
	 *         'id_organism' => '<ID of organism>',
	 *         'text' => '<organism name>',
	 *         'correct' => TRUE/FALSE
	 *       ]
	 *     ]
	 *   ],
	 *   [
	 *     'type' => QuestionType::CHOOSE_REPRESENTATION,
	 *     'questionText' => '<organism name>',
	 *     'options' => [
	 *        [
	 *          'id_representation' => '<ID of organism representation>',
	 *          'image' => '<HTML of image to show>',
	 *          'imageRightsHolder' => '<right holder>',
	 *          'imageLicense' => '<license>',
	 *          'correct' => TRUE/FALSE
	 *        ]
	 *     ]
	 *   ]
	 * ]
	 *
	 * @param int $userId ID of user
	 * @param mixed $concept ID of concept from which the questions should be drawn
	 * @param int $count Wanted number of questions
	 * @return array Array with prepared sequence of questions
	 */
	public function get($userId, $concept, $count);


	/**
	 * Updates model data of given user reflecting user answer.
	 *
	 * @param int $userId User ID
	 * @param UserAnswer $answer User answer
	 */
	public function answer($userId, UserAnswer $answer);
}
