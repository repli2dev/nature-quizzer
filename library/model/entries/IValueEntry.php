<?php
namespace NatureQuizzer\Model;

/**
 * Shared interface for value entries for knowledge/difficulty
 */
interface IValueEntry
{
	function getValue();

	function setValue($value);
}