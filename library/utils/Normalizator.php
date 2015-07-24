<?php

namespace NatureQuizzer\Utils;


use Nette\Utils\Strings;

class Normalizator
{
	public static function normalize($input)
	{
		if(Strings::endsWith($input, ',')) {
			$input = Strings::substring($input, 0, Strings::length($input) - 1);
		}
		return Strings::lower(
			Strings::trim(Strings::normalize(str_replace("\n", '', $input)))
		);
	}

	public static function normalizeAssociativeArray($array)
	{
		$output = [];
		foreach ($array as $key => $value) {
			$output[self::normalize($key)] = self::normalize($value);
		}
		return $output;
	}

} 