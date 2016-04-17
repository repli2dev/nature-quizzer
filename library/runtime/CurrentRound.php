<?php
namespace NatureQuizzer\Runtime;

use NatureQuizzer\Database\Model\Round;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Object;
use Nette\Utils\Json;

class CurrentRound extends Object
{
	const SECTION_NAME = 'CurrentRound';

	/** @var SessionSection */
	private $sessionSection;
	/** @var Round */
	private $round;

	public function __construct(Session $session, Round $round)
	{
		$this->sessionSection = $session->getSection(self::SECTION_NAME);
		$this->round = $round;
	}

	public function get($identificationNow, $userId, $clientInfo, $conceptId)
	{
		$identificationPrevious = $this->getLastIdentification();
		if (!$identificationPrevious || $identificationPrevious != $identificationNow) {
			$round = $this->round->insert([
				'id_user' => $userId,
				'client' => Json::encode($clientInfo),
				'id_concept' => ($conceptId === 'mix') ? null : $conceptId,
			]);
			$this->setRoundId($round->getPrimary());
			$this->setLastIdentification($identificationNow);
		}
		return $this->getRoundId();
	}

	private function getLastIdentification()
	{
		if (!$this->sessionSection->offsetExists('identification')) {
			return null;
		}
		return $this->sessionSection->offsetGet('identification');
	}
	private function setLastIdentification($value)
	{
		return $this->sessionSection->offsetSet('identification', $value);
	}
	private function getRoundId()
	{
		if (!$this->sessionSection->offsetExists('round')) {
			return null;
		}
		return $this->sessionSection->offsetGet('round');
	}
	private function setRoundId($value) {
		return $this->sessionSection->offsetSet('round', $value);
	}

}