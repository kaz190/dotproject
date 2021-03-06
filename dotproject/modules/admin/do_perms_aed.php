<?php /* ADMIN $Id: do_perms_aed.php 5722 2008-05-31 02:25:52Z ajdonnison $ */
if (!defined('DP_BASE_DIR')){
  die('You should not access this file directly.');
}

$del = isset($_POST['del']) ? $_POST['del'] : 0;

$obj =& $AppUI->acl();

$AppUI->setMsg( 'Permission' );

if ($del) {
	if (! $obj->checkModule($m, 'delete')) {
		$AppUI->redirect('m=public&a=access_denied');
	}
	if ($obj->del_acl($_REQUEST['permission_id'])) {
		$AppUI->setMsg( "deleted", UI_MSG_ALERT, true );
		$AppUI->redirect();
	} else {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
		$AppUI->redirect();
	}
} else {
	if (($isNotNew && ! $obj->checkModule($m, 'edit'))
	|| (!$isNotNew && ! $obj->checkModule($m, 'add'))) {
		$AppUI->redirect('m=public&a=access_denied');
	}
	if ($obj->addUserPermission()) {
		$AppUI->setMsg( $isNotNew ? 'updated' : 'added', UI_MSG_OK, true );
	} else {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
	}
	$AppUI->redirect();
}
?>
