<?php
namespace NatureQuizzer\Model\Utils;
use NatureQuizzer\Database\Model\Organism;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;
use Nette\Utils\Json;
use Tracy\Debugger;

/**
 * Factory class to instantiate, properly initialize and validate user answer
 */
class UserAnswerFactory
{
	use SmartObject;

	/** @var Organism */
	private $organism;

	public function __construct(Organism $organism)
	{
		$this->organism = $organism;
	}

	public function create($modelId, $persistenceModelId, $roundId, array $data)
	{
		$error = false;

		$a = new UserAnswer();
		$a->id_model = $modelId;
		$a->id_persistence_model = $persistenceModelId;
		$a->id_round = $roundId;
		$a->question_seq_num = $data['seqNum'];
		$a->question_type = (int) $data['questionType'];
		$a->extra = Json::encode([
			'time' => $data['time'],
			'viewportWidth' => $data['viewportWidth'],
			'viewportHeight' => $data['viewportHeight']
		]);
		foreach ($data['answers'] as $optionSeqNum => $answer) {
			$representationId = NULL;
			if (isset($answer['id_representation'])) {
				$representationId = $answer['id_representation'];
				$row = $this->organism->getOrganismByRepresentation($answer['id_representation']);
				if ($row === NULL) {
					Debugger::log(sprintf('Cannot find id_organism for id_representation [%s]', $answer['id_representation']), Debugger::EXCEPTION);
					$error = true;
					continue;
				}
				$organismId = $row->getPrimary();
			} else {
				$organismId = $answer['id_organism'];
			}
			$a->options[] = ArrayHash::from([
				'id_representation' => $representationId,
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