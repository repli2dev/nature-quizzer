<?php
namespace NatureQuizzer\Model\Utils;
use NatureQuizzer\Database\Model\Organism;
use Nette\Object;
use Nette\Utils\ArrayHash;
use Nette\Utils\Json;
use Tracy\Debugger;

/**
 * Factory class to instantiate, properly initialize and validate user answer
 */
class UserAnswerFactory extends Object
{
	/** @var Organism */
	private $organism;

	public function __construct(Organism $organism)
	{
		$this->organism = $organism;
	}

	public function create($modelId, $roundId, array $data)
	{
		$error = false;

		$a = new UserAnswer();
		$a->id_model = $modelId;
		$a->id_round = $roundId;
		$a->question_seq_num = $data['seqNum'];
		$a->question_type = (int) $data['questionType'];
		$a->extra = Json::encode([
			'time' => $data['time'],
			'viewportWidth' => $data['viewportWidth'],
			'viewportHeight' => $data['viewportHeight']
		]);
		foreach ($data['answers'] as $optionSeqNum => $answer) {
			if (isset($answer['id_representation'])) {
				$row = $this->organism->getOrganismByRepresentation($answer['id_representation']);
				if ($row === FALSE) {
					Debugger::log(sprintf('Cannot find id_organism for id_representation [%s]', $answer['id_representation']), Debugger::ERROR);
					$error = true;
					continue;
				}
				$organismId = $row->getPrimary();
			} else {
				$organismId = $answer['id_organism'];
			}
			$a->options[] = ArrayHash::from([
				'id_organism' => $organismId,
				'option_seq_num' => $optionSeqNum,
				'correct' => ($answer['answered'] == $answer['correct']),
				'main' => ($answer['correct'] == 'true'),
			]);
		}
		$a->hasErrors = $error;
		return $a;
	}
} 