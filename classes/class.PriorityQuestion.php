<?php
require_once('./Services/RTE/classes/class.ilRTE.php');

/**
 * Class PriorityQuestion
 *
 * @author Oskar Truffer <ot@studer-raimann.ch>
 */
class PriorityQuestion extends SurveyQuestion {

	/**
	 * @var int
	 */
	protected $numberOfPriorities;

	/**
	 * @var bool
	 */
	protected $ranked;

	/**
	 * @var string[]
	 */
	protected $priorities;

	/**
	 * @var string
	 */
	protected $tableName= "spl_svyq_prioq_prioq";

	/**
	 * @var string
	 */
	protected $priosTableName = "spl_svyq_prioq_prios";

	/**
	 * @var string
	 */
	protected $valuesTableName = "spl_svyq_prioq_pria";

	/**
	 * @var string
	 */
	protected $error;

	/**
	 * @return string
	 */
	public function getAdditionalTableName() {
		return $this->tableName;
	}


	/**
	 * @return array
	 */
	public function _getQuestionDataArray() {
		return array(
			'question_id' => $this->getId(),
			'questiontype_fi' => $this->getQuestionTypeID(),
			'obj_fi' => $this->getObjId(),
			'owner_fi' => $this->getOwner(),
			'title' => $this->getTitle(),
			'description' => $this->getDescription(),
			'author' => $this->getAuthor(),
			'obligatory' => (int)$this->getObligatory(),
			'complete' => (int)$this->isComplete(),
			'created' => time(),
			'original_id' => $this->getOriginalId(),
			'tstamp' => time(),
			'questiontext' => $this->getQuestiontext(),
			'label' => '',
			'question_fi' => $this->getId(),
		);
	}


	/**
	 * @param array $post_data
	 * @param       $survey_id
	 *
	 * @return string
	 */
	public function checkUserInput(array $post_data, $survey_id) {
		$prios = array();
		foreach($post_data['prio'] as $prio){
			if(in_array($prio, $prios)) {
				ilUtil::sendFailure("dublicated_entry", true);
				$this->error = "dublicated_entry";
				return "dublicated_entry";
			} else {
				$prios[] = $prio;
			}
		}
		return "";
	}


	/**
	 * @param array $post_data
	 * @param       $active_id
	 * @param bool  $a_return
	 */
	public function saveUserInput(array $post_data, $active_id, $a_return = false) {
		if(!$active_id) {
			return false;
		}
		global $ilDB;
		/**
		 * @var $ilDB ilDB
		 */
		$next_id = $ilDB->nextId('svy_answer');
		$ilDB->manipulateF("INSERT INTO svy_answer (answer_id, question_fi, active_fi, value, textanswer, tstamp) VALUES (%s, %s, %s, %s, %s, %s)", array(
			'integer',
			'integer',
			'integer',
			'float',
			'text',
			'integer'
		), array(
			$next_id,
			$this->getId(),
			$active_id,
			NULL,
			NULL,
			time()
		));

		$this->savePriorityAnswers($next_id);
	}


	protected function savePriorityAnswers($answer_id) {
		/** @var ilDB ilDB */
		global $ilDB;
		$answers = $_POST["prio"];
		$prios = $this->getPriorities();
		for($i = 0; $i < count($prios); $i++) {
			if($prios[$answers[$i]])
				$ilDB->insert($this->valuesTableName, array(
					"answer_id" => array("integer", $answer_id),
					"priority" => array("integer", $i),
					"priority_text" => array("text", $prios[$answers[$i]])
				));
		}
	}

	public function getPriorityAnswers($answer_id) {
		/** @var ilDB ilDB */
		global $ilDB;
		$prios = array();
		$result = $ilDB->queryF("SELECT * FROM {$this->valuesTableName} WHERE answer_id = %s", array("integer"), array($answer_id));
		while($prio = $ilDB->fetchAssoc($result)) {
			$prios[$prio['priority']] = $prio['priority_text'];
		}
		return $prios;
	}

	/**
	 * Adds the values for the user specific results export for a given user
	 *
	 * @param array $a_array An array which is used to append the values
	 * @param array $resultset The evaluation data for a given user
	 * @access public
	 */
	function addUserSpecificResultsData(&$a_array, &$resultset)
	{
		if (count($resultset["answers"][$this->getId()]))
		{
			foreach ($resultset["answers"][$this->getId()] as $key => $answer)
			{
				foreach ($this->getUserAnswerByActiveFi($answer['active_fi']) as $item) {
					array_push($a_array, $item);
				}
			}
		}
		else
		{
			array_push($a_array, $this->getSkippedValue());
		}
	}

 	function addUserSpecificResultsExportTitles(&$a_array, $a_use_label = false, $a_substitute = true) {
		$array = array();
		$title = parent::addUserSpecificResultsExportTitles($array, $a_use_label, $a_substitute);
		$pl = new ilPriorityQuestionPlugin();
		for($i = 1; $i <= $this->getNumberOfPriorities(); $i ++) {
			array_push($a_array, $title." ".$i.". ".$pl->txt("prio"));
		}
	}

	/**
	 * @param $survey_id
	 * @param $nr_of_users
	 * @param $finished_ids
	 *
	 * @return int
	 */
	public function getCumulatedResults($survey_id, $nr_of_users, $finished_ids) {
		global $ilDB;

		$question_id = $this->getId();

		$result_array = array();
		$cumulated = array();
		$textvalues = array();

		$sql = 'SELECT svy_answer.* FROM svy_answer' . ' JOIN svy_finished ON (svy_finished.finished_id = svy_answer.active_fi)'
			. ' WHERE svy_answer.question_fi = ' . $ilDB->quote($question_id, 'integer')
			. ' AND svy_finished.survey_fi = ' . $ilDB->quote($survey_id, 'integer');
		if ($finished_ids) {
			$sql .= ' AND ' . $ilDB->in('svy_finished.finished_id', $finished_ids, '', 'integer');
		}

		$result = $ilDB->query($sql);
		while ($row = $ilDB->fetchAssoc($result)) {
			$cumulated[$row['value']] ++;
			array_push($textvalues, "in getCumu");
		}
		asort($cumulated, SORT_NUMERIC);
		end($cumulated);
		$numrows = $result->numRows();
		$pl = ilPriorityQuestionPlugin::getPlugin();

		$result_array['USERS_ANSWERED'] = $numrows;
		$result_array['USERS_SKIPPED'] = $nr_of_users - $numrows;
		$result_array['USERS_SKIPPED'] = '-';
		$result_array['QUESTION_TYPE'] = $pl->getPrefix() . '_common_question_type';
		$result_array['textvalues'] = $textvalues;

		return $result_array;
	}


	/**
	 * Creates a the cumulated results data for the question
	 *
	 * @param $survey_id
	 * @param $counter
	 * @param $finished_ids
	 *
	 * @return array Data
	 */
	//	public function getCumulatedResultData($survey_id, $counter, $finished_ids) {
	//		return array();
	//	}

	/**
	 * Returns an array containing all answers to this question in a given survey
	 *
	 * @param integer $survey_id The database ID of the survey
	 * @param         $finished_ids
	 *
	 * @return array An array containing the answers to the question. The keys are either the user id or the anonymous id
	 * @access public
	 */
	public function getUserAnswers($survey_id, $finished_ids) {
		global $ilDB;

		$answers = array();

		$sql = "SELECT svy_answer.* FROM svy_answer, svy_finished" . " WHERE svy_finished.survey_fi = " . $ilDB->quote($survey_id, "integer")
			. " AND svy_answer.question_fi = " . $ilDB->quote($this->getId(), "integer") . " AND svy_finished.finished_id = svy_answer.active_fi";
		if ($finished_ids) {
			$sql .= " AND " . $ilDB->in("svy_finished.finished_id", $finished_ids, "", "integer");
		}
		$result = $ilDB->query($sql);
		while ($row = $ilDB->fetchAssoc($result)) {
			$res= $ilDB->queryF("SELECT * FROM {$this->valuesTableName} WHERE answer_id = %s", array("integer"), array($row['answer_id']));
			$array = array();
			while($ro = $ilDB->fetchAssoc($res)) {
				$array[] = $ro['priority_text'];
			}
			$answers[$row["active_fi"]] = implode(", ", $array);
		}

		return $answers;
	}

	/**
	 * @param $active_fi
	 * @return array
	 */
	public function getUserAnswerByActiveFi($active_fi) {
		global $ilDB;

		$array = array();

		$sql = "SELECT * FROM svy_answer WHERE active_fi = ".$ilDB->quote($active_fi, "integer");
		$result = $ilDB->query($sql);
		while ($row = $ilDB->fetchAssoc($result)) {
			$res= $ilDB->queryF("SELECT * FROM {$this->valuesTableName} WHERE answer_id = %s", array("integer"), array($row['answer_id']));
			$array = array();
			while($ro = $ilDB->fetchAssoc($res)) {
				$array[] = $ro['priority_text'];
			}
		}

		return $array;
	}


	/**
	 * @param int $question_id
	 */
	public function loadFromDb($question_id) {
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;
		$result = $ilDB->queryF('SELECT svy_question.* FROM svy_question WHERE svy_question.question_id = %s', array( 'integer' ), array( $question_id ));

		if ($result->numRows() == 1) {
			$data = $ilDB->fetchObject($result);
			$this->setId($data->question_id);
			$this->setTitle($data->title);
			$this->label = $data->label;
			$this->setDescription($data->description);
			$this->setObjId($data->obj_fi);
			$this->setAuthor($data->author);
			$this->setOwner($data->owner_fi);
			$this->setObligatory($data->obligatory);
			$this->setComplete($data->complete);
			$this->setOriginalId($data->original_id);
		}
		parent::loadFromDb($question_id);

		$result = $ilDB->queryF("SELECT * FROM {$this->tableName} WHERE question_fi = %s", array( 'integer' ), array($this->getId()));
		if ($result->numRows() == 1) {
			$data = $ilDB->fetchObject($result);
			$this->setNumberOfPriorities($data->num_prios);
			$this->setRanked($data->ranked_prios);
		}

		$this->loadPrios();
	}


	/**
	 * @return bool
	 */
	public function isComplete() {
		return true;
	}


	/**
	 * @return string
	 */
	public function getQuestionType() {
		$plugin_object = new ilPriorityQuestionPlugin();

		return $plugin_object->getQuestionType();
	}


	/**
	 * @var string
	 */
	protected $info_page_text = '';


	/**
	 * @param string $info_page_text
	 */
	public function setInfoPageText($info_page_text) {
		$this->info_page_text = $info_page_text;
	}


	/**
	 * @return string
	 */
	public function getInfoPageText() {
		return $this->info_page_text;
	}

	public function saveToDb($original_id = "") {
		if(!parent::saveToDb($original_id))
			return 0;

		$this->readFromPost();

		/** @var ilDB ilDB */
		global $ilDB;
		if($this->getId())
			$ilDB->manipulateF("DELETE FROM {$this->tableName} WHERE question_fi = %s", array("integer"), array($this->getId()));
		$affectedRows = $ilDB->insert($this->tableName, array(
			"question_fi" => array("integer", $this->getId()),
			"num_prios" => array("integer", $this->getNumberOfPriorities()),
			"ranked_prios" => array("integer", ($this->isRanked()) ? 1: 0)
		));
		$this->deletePriorities();
		$this->writePriorities();


		return $affectedRows;
	}

	protected function readFromPost() {
		$array = $_POST;
		$array['rankedPrios'] = ($array['rankedPrios'] == "")?false:true;
		$this->readFromArray($array);
	}

	protected function readFromArray($array) {
		$this->setNumberOfPriorities((int) $array['numPrios']);
		$this->setRanked($array['rankedPrios']);
		$this->setPriorities($array['priorities']);
	}

	protected function writePriorities() {
		/** @var ilDB ilDB */
		global $ilDB;

		$i = 0;
		foreach($this->getPriorities() as $prio) {
			$ilDB->insert($this->priosTableName, array(
				"question_fi" => array("integer", $this->getId()),
				"prio" => array("text", $prio),
				'ordernumber' => array('integer', $i)
				));
			$i++;
		}
	}


	protected function deletePriorities() {
		global $ilDB;

		$ilDB->manipulateF("DELETE FROM {$this->priosTableName} WHERE question_fi = %s",
			array('integer'),
			array($this->getId())
		);
	}

	/**
	 * @return int
	 */
	public function getNumberOfPriorities() {
		return $this->numberOfPriorities;
	}

	/**
	 * @param int $numberOfPriorities
	 */
	public function setNumberOfPriorities($numberOfPriorities) {
		$this->numberOfPriorities = $numberOfPriorities;
	}

	/**
	 * @return boolean
	 */
	public function isRanked() {
		return $this->ranked;
	}

	/**
	 * @param boolean $ranked
	 */
	public function setRanked($ranked) {
		$this->ranked = $ranked;
	}

	/**
	 * @return string[]
	 */
	public function getPriorities() {
		return $this->priorities;
	}

	/**
	 * @param string[] $priorities
	 */
	public function setPriorities($priorities) {
		$this->priorities = $priorities;
	}

	private function loadPrios() {
		/** @var ilDB ilDB */
		global $ilDB;
		$result = $ilDB->queryF("SELECT * FROM {$this->priosTableName} WHERE question_fi = %s ORDER BY ordernumber", array("integer"), array($this->getId()));
		while($data = $ilDB->fetchAssoc($result)) {
			$this->priorities[] = $data['prio'];
		}
	}

	/**
	 * @return string
	 */
	public function getError() {
		return $this->error;
	}

	/**
	 * @param string $error
	 */
	public function setError($error) {
		$this->error = $error;
	}
}

?>
