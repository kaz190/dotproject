<?php /* FUNCTIONS $Id$ */
if (!defined('DP_BASE_DIR')){
	die('You should not access this file directly');
}

// project statii
global $pstatus, $ptype, $priority;
$pstatus = dPgetSysVal( 'ProjectStatus' );
$ptype = dPgetSysVal( 'ProjectType' );

$priority = array(
 -1 => array(
 	'name' => 'low',
 	'color' => '#E5F7FF'
 	),
 0 => array(
 	'name' => 'normal',
 	'color' => ''//#CCFFCA
 	),
 1 => array(
 	'name' => 'high',
 	'color' => '#FFDCB3'
 	),
 2 => array(
 	'name' => 'immediate',
 	'color' => '#FF887C'
 	)
);

?>