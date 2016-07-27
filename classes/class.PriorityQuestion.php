<?php

require_once "Modules/TestQuestionPool/classes/class.assQuestion.php";
require_once "Customizing/global/plugins/Modules/TestQuestionPool/Questions/PriorityQuestion/classes/class.ilPriorityQuestionPlugin.php";

class PriorityQuestion extends assQuestion {

	protected $pl;

	public function __construct($title, $comment, $author, $owner, $question) {
		parent::__construct($title, $comment, $author, $owner, $question);
		$this->pl = ilPriorityQuestionPlugin::getInstance();
	}

	/**
	 * Returns true iff all information is given for the question to be answered.
	 * @return bool
	 */
	public function isComplete() {
		return ($this->title) and ($this->author) and ($this->question) and ($this->getMaximumPoints() > 0);
	}

	/**
	 * Saves a question object to a database
	 *
	 * @param    string        original id
	 * @access    public
	 * @see assQuestion::saveToDb()
	 */
	function saveToDb($original_id = "") {
		// afterwards the new id is set
		$this->saveQuestionDataToDb($original_id);

		// Now you can save additional data
		// ...

		parent::saveToDb();
	}

	/**
	 * Loads a question object from a database
	 * This has to be done here (assQuestion does not load the basic data)!
	 *
	 * @param integer $question_id A unique key which defines the question in the database
	 * @see assQuestion::loadFromDb()
	 */
	public function loadFromDb($question_id) {
		global $ilDB;

		// load the basic question data
		$result = $ilDB->query("SELECT qpl_questions.* FROM qpl_questions WHERE question_id = "
			. $ilDB->quote($question_id, 'integer'));

		$data = $ilDB->fetchAssoc($result);
		$this->setId($question_id);
		$this->setTitle($data["title"]);
		$this->setComment($data["description"]);
		$this->setSuggestedSolution($data["solution_hint"]);
		$this->setOriginalId($data["original_id"]);
		$this->setObjId($data["obj_fi"]);
		$this->setAuthor($data["author"]);
		$this->setOwner($data["owner"]);
		$this->setPoints($data["points"]);

		include_once("./Services/RTE/classes/class.ilRTE.php");
		$this->setQuestion(ilRTE::_replaceMediaObjectImageSrc($data["question_text"], 1));
		$this->setEstimatedWorkingTime(substr($data["working_time"], 0, 2), substr($data["working_time"], 3, 2), substr($data["working_time"], 6, 2));

		// now you can load additional data
		// ...

		try {
			$this->setAdditionalContentEditingMode($data['add_cont_edit_mode']);
		} catch (ilTestQuestionPoolException $e) {
		}

		// loads additional stuff like suggested solutions
		parent::loadFromDb($question_id);
	}

	/**
	 * Copies a question
	 * This is used when a question is copied on a question pool
	 *
	 * @access public
	 */
	function copyObject($target_questionpool_id, $title = "")
	{
		if ($this->getId() <= 0)
		{
			// The question has not been saved. It cannot be duplicated
			return;
		}

		// make a real clone to keep the object unchanged
		$clone = clone $this;

		$original_id = assQuestion::_getOriginalId($this->getId());
		$source_questionpool_id = $this->getObjId();
		$clone->setId(-1);
		$clone->setObjId($target_questionpool_id);
		if ($title)
		{
			$clone->setTitle($title);
		}

		// save the clone data
		$clone->saveToDb('', false);

		// copy question page content
		$clone->copyPageOfQuestion($original_id);
		// copy XHTML media objects
		$clone->copyXHTMLMediaObjectsOfQuestion($original_id);

		// call the event handler for copy
		$clone->onCopy($source_questionpool_id, $original_id, $clone->getObjId(), $clone->getId());

		return $clone->getId();
	}

	/**
	 * Duplicates a question
	 * This is used for copying a question to a test
	 *
	 * @access public
	 */
	function duplicate($for_test = true, $title = "", $author = "", $owner = "", $testObjId = null) {
		if ($this->getId() <= 0) {
			// The question has not been saved. It cannot be duplicated
			return;
		}

		// make a real clone to keep the object unchanged
		$clone = clone $this;

		$original_id = assQuestion::_getOriginalId($this->getId());
		$clone->setId(-1);

		if ((int)$testObjId > 0) {
			$clone->setObjId($testObjId);
		}

		if ($title) {
			$clone->setTitle($title);
		}
		if ($author) {
			$clone->setAuthor($author);
		}
		if ($owner) {
			$clone->setOwner($owner);
		}

		if ($for_test) {
			$clone->saveToDb($original_id, false);
		} else {
			$clone->saveToDb('', false);
		}

		// copy question page content
		$clone->copyPageOfQuestion($this->getId());
		// copy XHTML media objects
		$clone->copyXHTMLMediaObjectsOfQuestion($this->getId());

		// call the event handler for duplication
		$clone->onDuplicate($this->getObjId(), $this->getId(), $clone->getObjId(), $clone->getId());

		return $clone->getId();
	}

	/**
	 * Synchronize a question with its original
	 * You need to extend this function if a question has additional data that needs to be synchronized
	 *
	 * @access public
	 */
	function syncWithOriginal()
	{
		parent::syncWithOriginal();
	}

	/**
	 * Get a submitted solution array from $_POST
	 *
	 * In general this may return any type that can be stored in a php session
	 * The return value is used by:
	 * 		savePreviewData()
	 * 		saveWorkingData()
	 * 		calculateReachedPointsForSolution()
	 *
	 * @return	array	('value1' => string, 'value2' => string, 'points' => float)
	 */
	protected function getSolutionSubmit()
	{
		return array(
			'value1' => ilUtil::stripSlashes($_POST["question".$this->getId()."value1"]),
			'value2' => ilUtil::stripSlashes($_POST["question".$this->getId()."value2"]),
			'points' =>  (float) $_POST["question".$this->getId()."points"]
		);
	}


	/**
	 * Calculate the reached points from a solution array
	 *
	 * @param	array	('value1' => string, 'value2' => string, 'points' => float)
	 * @return  float	reached points
	 */
	protected function calculateReachedPointsForSolution($solution)
	{
		// in our example we take the points entered by the student
		// and adjust them to be in the allowed range
		$points = $solution["points"];
		if (empty($points) or $points < 0 or $points > $this->getMaximumPoints())
		{
			$points = 0;
		}

		// return the raw points given to the answer
		// these points will afterwards be adjusted by the scoring options of a test
		return $points;
	}


	/**
	 * Returns the points, a learner has reached answering the question
	 * The points are calculated from the given answers.
	 *
	 * @param int $active_id
	 * @param integer $pass The Id of the test pass
	 * @param bool $authorizedSolution
	 * @param boolean $returndetails (deprecated !!)
	 * @return int /array $points/$details (array $details is deprecated !!)
	 * @throws ilTestException
	 * @internal param int $active The Id of the active learner
	 * @access public
	 * @see  assQuestion::calculateReachedPoints()
	 */
	function calculateReachedPoints($active_id, $pass = NULL, $authorizedSolution = true, $returndetails = FALSE)
	{
		if( $returndetails )
		{
			throw new ilTestException('return details not implemented for '.__METHOD__);
		}

		if(is_null($pass))
		{
			$pass = $this->getSolutionMaxPass($active_id);
		}

		// get the answers of the learner from the tst_solution table
		// the data is saved by saveWorkingData() in this class
		$solutions = $this->getSolutionValues($active_id, $pass);

		// there may be more solutions stored due to race conditions
		// the last saved solution record wins
		return $this->calculateReachedPointsForSolution(empty($solutions) ? array() : end($solutions));
	}

	/**
	 * Saves the learners input of the question to the database
	 *
	 * @param int $active_id
	 * @param null $pass
	 * @param bool $authorized
	 * @return bool Indicates the save status (true if saved successful, false otherwise)
	 * @internal param int $test_id The database id of the test containing this question
	 * @access    public
	 * @see    assQuestion::saveWorkingData()
	 */
	function saveWorkingData($active_id, $pass = NULL, $authorized = true)
	{
		global $ilDB;
		global $ilUser;

		if (is_null($pass))
		{
			include_once "./Modules/Test/classes/class.ilObjTest.php";
			$pass = ilObjTest::_getPass($active_id);
		}

		// get the submitted solution
		$solution = $this->getSolutionSubmit();

		// lock to prevent race conditions
		$this->getProcessLocker()->requestUserSolutionUpdateLock();

		// save the answers of the learner to tst_solution table
		// this data is question type specific
		// it is used used by calculateReachedPointsForSolution() in this class

		$result = $ilDB->queryF("SELECT solution_id FROM tst_solutions WHERE active_fi = %s AND question_fi = %s AND pass = %s",
			array('integer','integer','integer'),
			array($active_id, $this->getId(), $pass)
		);

		$row = $ilDB->fetchAssoc($result);
		if ($row)
		{
			$affectedRows = $ilDB->update("tst_solutions",
				array(
					"active_fi"   => array("integer", $active_id),
					"question_fi" => array("integer", $this->getId()),
					"pass"        => array("integer", $pass),
					"tstamp"      => array("integer", time()),

					// points, value1 and value2 are multi-purpose fields
					// store here what you want from the POST data
					// in our example we allow to enter these values directly
					"points" 	  => array("float", $solution["points"]),
					"value1"      => array("clob", $solution["value1"]),
					"value2"      => array("clob", $solution["value2"]),
				),
				array (
					"solution_id" => array("integer", $row['solution_id']),
				)
			);
		}
		else
		{
			$next_id = $ilDB->nextId('tst_solutions');
			$affectedRows = $ilDB->insert("tst_solutions",
				array(
					"solution_id" => array("integer", $next_id),
					"active_fi"   => array("integer", $active_id),
					"question_fi" => array("integer", $this->getId()),
					"pass"        => array("integer", $pass),
					"tstamp"      => array("integer", time()),

					// points, value1 and value2 are multi-purpose fields
					// store here what you want from the POST data
					// in our example we allow to enter these values directly
					"points" 	  => array("float", $solution["points"]),
					"value1"      => array("clob", $solution["value1"]),
					"value2"      => array("clob", $solution["value2"]),
				));
		}

		// unlock
		$this->getProcessLocker()->releaseUserSolutionUpdateLock();

		// Check if the user has entered something
		// Then set entered_values accordingly
		if (!empty($solution["points"]))
		{
			$entered_values = TRUE;
		}

		if ($entered_values)
		{
			include_once ("./Modules/Test/classes/class.ilObjAssessmentFolder.php");
			if (ilObjAssessmentFolder::_enabledAssessmentLogging())
			{
				$this->logAction($this->lng->txtlng("assessment", "log_user_entered_values", ilObjAssessmentFolder::_getLogLanguage()), $active_id, $this->getId());
			}
		}
		else
		{
			include_once ("./Modules/Test/classes/class.ilObjAssessmentFolder.php");
			if (ilObjAssessmentFolder::_enabledAssessmentLogging())
			{
				$this->logAction($this->lng->txtlng("assessment", "log_user_not_entered_values", ilObjAssessmentFolder::_getLogLanguage()), $active_id, $this->getId());
			}
		}

		return true;
	}

	/**
	 * Returns the question type of the question
	 *
	 * @return string The question type of the question
	 */
	public function getQuestionType() {
		return "ilassPriorityQuestion";
	}

	/**
	 * Creates an Excel worksheet for the detailed cumulated results of this question
	 *
	 * @access public
	 * @see assQuestion::setExportDetailsXLS()
	 */
	public function setExportDetailsXLS(&$worksheet, $startrow, $active_id, $pass, &$format_title, &$format_bold)
	{
		global $lng;

		include_once ("./Services/Excel/classes/class.ilExcelUtils.php");
		$solutions = $this->getSolutionValues($active_id, $pass);

		if (is_array($solutions))
		{
			foreach ($solutions as $solution)
			{
				$value1 = isset($solution["value1"]) ? $solution["value1"] : "";
				$value2 = isset($solution["value2"]) ? $solution["value2"] : "";
				$points = isset($solution["points"]) ? $solution["points"] : "";
			}
		}

		$worksheet->writeString($startrow, 0, ilExcelUtils::_convert_text($this->plugin->txt($this->getQuestionType())), $format_title);
		$worksheet->writeString($startrow, 1, ilExcelUtils::_convert_text($this->getTitle()), $format_title);
		$i = 1;

		// now provide a result string and write it to excel
		// it is also possible to write multiple rows
		$worksheet->writeString($startrow + $i, 0, ilExcelUtils::_convert_text($this->plugin->txt("label_value1")), $format_bold);
		$worksheet->write($startrow + $i, 1, ilExcelUtils::_convert_text($value1));
		$i++;

		$worksheet->writeString($startrow + $i, 0, ilExcelUtils::_convert_text($this->plugin->txt("label_value2")), $format_bold);
		$worksheet->write($startrow + $i, 1, ilExcelUtils::_convert_text($value2));
		$i++;

		$worksheet->writeString($startrow + $i, 0, ilExcelUtils::_convert_text($this->plugin->txt("label_points")), $format_bold);
		$worksheet->write($startrow + $i, 1, ilExcelUtils::_convert_text($points));
		$i++;

		return $startrow + $i + 1;
	}

	/**
	 * Creates a question from a QTI file
	 * Receives parameters from a QTI parser and creates a valid ILIAS question object
	 * Extension needed to get the plugin path for the import class
	 *
	 * @access public
	 * @see assQuestion::fromXML()
	 */
	function fromXML(&$item, &$questionpool_id, &$tst_id, &$tst_object, &$question_counter, &$import_mapping)
	{
		$this->getPlugin()->includeClass("import/qti12/class.assExampleQuestionImport.php");
		$import = new assExampleQuestionImport($this);
		$import->fromXML($item, $questionpool_id, $tst_id, $tst_object, $question_counter, $import_mapping);
	}

	/**
	 * Returns a QTI xml representation of the question and sets the internal
	 * domxml variable with the DOM XML representation of the QTI xml representation
	 * Extension needed to get the plugin path for the import class
	 *
	 * @return string The QTI xml representation of the question
	 * @access public
	 * @see assQuestion::toXML()
	 */
	function toXML($a_include_header = true, $a_include_binary = true, $a_shuffle = false, $test_output = false, $force_image_references = false)
	{
		$this->getPlugin()->includeClass("export/qti12/class.assExampleQuestionExport.php");
		$export = new assExampleQuestionExport($this);
		return $export->toXML($a_include_header, $a_include_binary, $a_shuffle, $test_output, $force_image_references);
	}
}