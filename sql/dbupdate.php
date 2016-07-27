<#1>
<?php
/**
 * Question plugin example: database update script
 *
 * @author Fred Neumann <fred.neumann@fim.uni-erlangen.de>
 * @version $Id$
 */

$res = $ilDB->queryF("SELECT * FROM qpl_qst_type WHERE type_tag = %s", array('text'), array('assPriorityQuestion')
);

if ($res->numRows() == 0)
{
	$res = $ilDB->query("SELECT MAX(question_type_id) maxid FROM qpl_qst_type");
	$data = $ilDB->fetchAssoc($res);
	$max = $data["maxid"] + 1;

	$affectedRows = $ilDB->manipulateF(
		"INSERT INTO qpl_qst_type (question_type_id, type_tag, plugin) VALUES (%s, %s, %s)",
		array("integer", "text", "integer"),
		array($max, 'assExampleQuestion',
			1)
	);
}
?>