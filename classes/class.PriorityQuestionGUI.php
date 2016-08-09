<?php
require_once('./Customizing/global/plugins/Modules/SurveyQuestionPool/SurveyQuestions/PriorityQuestion/classes/class.PriorityQuestion.php');
require_once('./Modules/SurveyQuestionPool/classes/class.SurveyQuestionGUI.php');
require_once('./Services/Object/classes/class.ilObject2.php');
require_once "Customizing/global/plugins/Services/EventHandling/EventHook/UserDefaults/classes/Form/class.ilMultipleTextInput3GUI.php";
require_once "Services/Form/classes/class.ilTextInputGUI.php";
require_once "Services/Form/classes/class.ilCheckboxInputGUI.php";
require_once "Services/Form/classes/class.ilTextWizardInputGUI.php";
require_once "Modules/SurveyQuestionPool/classes/class.ilSurveyCategory.php";
require_once "Modules/SurveyQuestionPool/classes/class.SurveyCategories.php";
require_once "Services/Form/classes/class.ilPropertyFormGUI.php";

/**
 * Class PriorityQuestionGUI
 *
 * @author Oskar Truffer <ot@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy PriorityQuestionGUI: ilObjSurveyQuestionPoolGUI, ilSurveyEditorGUI
 */
class PriorityQuestionGUI extends SurveyQuestionGUI {

	const FIELD_NAME = 'field_info_page';
	/**
	 * @var ilPriorityQuestionPlugin
	 */
	protected $plugin_object;
	/**
	 * @var PriorityQuestion
	 */
	public $object;


	/**
	 * @param $a_id
	 */
	public function __construct($a_id = -1) {
		parent::__construct($a_id);
		$this->plugin_object = new ilPriorityQuestionPlugin();
	}


	/**
	 * @return mixed
	 */
	public function &executeCommand() {
		global $ilTabs, $ilCtrl;
		$cmd = $this->ctrl->getCmd();
		switch ($cmd) {
			case 'preview':

				/**
				 * @var $ilTabs ilTabsGUI
				 * @var $ilCtrl ilCtrl
				 */
				$ilTabs->clearTargets();
				$ilCtrl->setParameterByClass('ilObjSurveyQuestionPoolGUI', 'ref_id', $_GET['ref_id']);
				$title = ilObject2::_lookupTitle(ilObject2::_lookupObjId($_GET['ref_id']));
				$ilTabs->setBackTarget($title, $ilCtrl->getLinkTargetByClass('ilObjSurveyQuestionPoolGUI', 'questions'));
				$ilTabs->addTab('preview', $this->plugin_object->txt('common_preview'), '');
				$ret =& $this->$cmd();
				break;
			default:
				$ret =& $this->$cmd();
				break;
		}

		return $ret;
	}


	/**
	 * @return string
	 */
	public function getQuestionType() {
		$plugin_object = new ilPriorityQuestionPlugin();

		return $plugin_object->getPrefix() . '_common_question_type';
	}


	/**
	 * @return string
	 */
	public function getParsedAnswers() {
		return '-';
	}


	/**
	 *
	 */
	protected function initObject() {
		$this->object = new PriorityQuestion();
	}


	public function setQuestionTabs() {
		// TODO: Implement setQuestionTabs() method.
	}


	/**
	 * @param ilPropertyFormGUI $a_form
	 */
	protected function addFieldsToEditForm(ilPropertyFormGUI $a_form) {
		$this->removeFields($a_form);
		$this->addHiddenFields();
		$this->addPriorityFormItems($a_form);
	}


	/**
	 * @param ilPropertyFormGUI $a_form
	 */
	protected function importEditFormValues(ilPropertyFormGUI $a_form) {
		$this->object->setQuestiontext($a_form->getInput(self::FIELD_NAME));
	}


	/**
	 * @param int $question_title
	 * @param int $show_questiontext
	 *
	 * @return string
	 */
	public function getPrintView($question_title = 1, $show_questiontext = 1) {
		return $this->getWorkingForm();
	}


	/**
	 * @param string $working_data
	 * @param int $question_title
	 * @param int $show_questiontext
	 * @param string $error_message
	 * @param null $survey_id
	 *
	 * @return string
	 */
	public function getWorkingForm($working_data = '', $question_title = 1, $show_questiontext = 1, $error_message = '', $survey_id = null) {
		$form = $this->getWorkingFormObject($working_data, $question_title, $show_questiontext, $error_message, $survey_id);
		$html = '';
		$selects = $form->getInputItemsRecursive();


		foreach($selects as $selectInput) {
			/** @var ilSelectInputGUI $selectInput */
			$html .= "<div class='form-horizontal'>";
			$html .= "<label class='col-sm-3 control-label'>".$selectInput->getTitle()."</label>";
			$html .= $selectInput->render();
			$html .= "</div>";

		}

		return $html;
	}

	/**
	 *
	 */
	public function getWorkingFormObject($working_data = '', $question_title = 1, $show_questiontext = 1, $error_message = '', $survey_id = null) {
		$form = new ilPropertyFormGUI("working_form", "working_form");
		if(is_array($working_data) && is_array($working_data[0])) {
			$answers = $this->object->getPriorityAnswers($working_data[0]['answer_id']);
		}

		for($i = 1; $i <= $this->object->getNumberOfPriorities(); $i++) {
			$dd = new ilSelectInputGUI($this->plugin_object->txt("prio")." ".$i, "prio[]");
			$priorities = $this->object->getPriorities();
			$dd->setOptions($priorities);
			// if we already got an answer, we set these values. Otherwise we just pick anything.
			if(count($answers) == $this->object->getNumberOfPriorities()) {
				$dd->setValue(array_search($answers[$i-1], $priorities));
			} else {
				$dd->setValue($i-1);
			}
			$form->addItem($dd);
		}



		return $form;
	}


	/**
	 * @param $survey_id
	 * @param $counter
	 * @param $finished_ids
	 */
	public function getCumulatedResultsDetails($survey_id, $counter, $finished_ids) {
		// TODO: Implement getCumulatedResultsDetails() method.
	}


	/**
	 * @param ilPropertyFormGUI $form
	 * @internal param ilPropertyFormGUI $a_form
	 *
	 * @internal param $ilUser
	 */
	protected function addPriorityFormItems(ilPropertyFormGUI $form) {
		$subtitle = new ilFormSectionHeaderGUI();
		$subtitle->setTitle($this->plugin_object->txt("priorities_form_sector"));
		$form->addItem($subtitle);

		$numberOfPriorities = new ilNumberInputGUI($this->plugin_object->txt("numPrios"), "numPrios");
		$numberOfPriorities->setInfo($this->plugin_object->txt("numPrios_info"));
		$numberOfPriorities->setRequired(true);
		$numberOfPriorities->setValue($this->object->getNumberOfPriorities());
		$form->addItem($numberOfPriorities);

//		$rankedPrios = new ilCheckboxInputGUI($this->plugin_object->txt("rankedPrios"), "rankedPrios");
//		$rankedPrios->setInfo("rankedPrios_info");
//		$rankedPrios->setChecked($this->object->isRanked());
//		$form->addItem($rankedPrios);

		$priorities = new ilTextWizardInputGUI($this->plugin_object->txt("priorities"), "priorities");
		$priorities->setValues(count($this->object->getPriorities())?$this->object->getPriorities():array("..."));
		$priorities->setAllowMove(true);
		$priorities->setRequired(true);

		$priorities->setInfo($this->plugin_object->txt('priorities_info'));
		$form->addItem($priorities);

	}


	protected function addHiddenFields() {
		$question_hidden = new ilHiddenInputGUI('question');
		$question_hidden->setValue('-');
		$question_hidden = new ilHiddenInputGUI('obligatory');
		$question_hidden->setValue(false);
	}


	/**
	 * @param ilPropertyFormGUI $a_form
	 */
	protected function removeFields(ilPropertyFormGUI $a_form) {
		$a_form->removeItemByPostVar('question');
		$a_form->removeItemByPostVar('obligatory');
	}

 	protected function validateEditForm(ilPropertyFormGUI $form) {
		$numPrios = $form->getInput('numPrios');
		$prios = $form->getInput('priorities');
		if($numPrios > count($prios)) {
			ilUtil::sendFailure($this->plugin_object->txt("more_priorities_than_choices"));
			return false;
		}
		return true;
	}
}

?>
