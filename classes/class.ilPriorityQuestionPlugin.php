<?php

require_once "Modules/TestQuestionPool/classes/class.ilQuestionsPlugin.php";

class ilPriorityQuestionPlugin extends ilQuestionsPlugin{

	/** @var  ilPriorityQuestionPlugin */
	private static $instance;

	/**
	 * Get Plugin Name. Must be same as in class name il<Name>Plugin
	 * and must correspond to plugins subdirectory name.
	 *
	 * Must be overwritten in plugin class of plugin
	 *
	 * @return    string    Plugin Name
	 */
	function getPluginName() {
		return "PriorityQuestion";
	}

	function getQuestionType() {
		return "assPriorityQuestion";
	}

	function getQuestionTypeTranslation() {
		return $this->txt("priority_question");
	}
	/**
	 * @return ilPriorityQuestionPlugin
	 */
	public static function getInstance() {
		if(self::$instance == null)
			self::$instance = new ilPriorityQuestionPlugin();
		return self::$instance;
	}
}