<#1>
<?php
$res = $ilDB->queryF('SELECT * FROM svy_qtype WHERE type_tag = %s', array( 'text' ), array( 'PriorityQuestion' ));
if ($res->numRows() == 0) {
	$res = $ilDB->query('SELECT MAX(questiontype_id) maxid FROM svy_qtype');
	$data = $ilDB->fetchAssoc($res);
	$max = $data['maxid'] + 1;

	$affectedRows = $ilDB->manipulateF('INSERT INTO svy_qtype (questiontype_id, type_tag, plugin) VALUES (%s, %s, %s)', array(
			'integer',
			'text',
			'integer'
		), array(
			$max,
			'PriorityQuestion',
			1
		));
}
?>
<#2>
<?php
$fields = array(
	'question_fi' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'num_prios' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => false
	),
	'ranked_prios' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => false
	)
);
if(!$ilDB->tableExists('spl_svyq_prioq_prioq')) {
	$ilDB->createTable("spl_svyq_prioq_prioq", $fields);
	$ilDB->addPrimaryKey("spl_svyq_prioq_prioq", array( "question_fi" ));
}
?>
<#3>
<?php
$fields = array(
	'question_fi' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'prio' => array(
		'type' => 'text',
		'length' => 120,
		'notnull' => false
	)
);
if(!$ilDB->tableExists('spl_svyq_prioq_prios')) {
	$ilDB->createTable("spl_svyq_prioq_prios", $fields);
}
?>
<#4>
<?php
$fields = array(
	'answer_id' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'priority' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => true
	),
	'priority_text' => array(
		'type' => 'text',
		'length' => 120,
		'notnull' => true
	)
);
if(!$ilDB->tableExists('spl_svyq_prioq_pria')) {
	$ilDB->createTable("spl_svyq_prioq_pria", $fields);
}
?>
<#5>
<?php
if(!$ilDB->tableColumnExists('spl_svyq_prioq_prios', 'ordernumber')) {
	$ilDB->addTableColumn('spl_svyq_prioq_prios', 'ordernumber', array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => false
	));
}
?>
