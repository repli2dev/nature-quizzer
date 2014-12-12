<?php
namespace NatureQuizzer\Database\Model;


/**
 * Enum with types of question
 *  - 1 = Choose representation: name of an organism is given, user chooses a representation of the organism
 *  - 2 = Choose name: image of an organism is given, user chooses name of the organism
 */
final class QuestionType
{
	const CHOOSE_REPRESENTATION = 1;
	const CHOOSE_NAME = 2;

	public static function isValid($type)
	{
		return $type === self::CHOOSE_REPRESENTATION || $type === self::CHOOSE_NAME;
	}
} 