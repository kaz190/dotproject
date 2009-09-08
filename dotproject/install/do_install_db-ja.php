<?php // $Id: do_install_db.php 5788 2008-07-28 10:05:48Z ajdonnison $

include_once 'check_upgrade.php';
if ($_POST['mode'] == 'install' && dPcheckUpgrade() == 'upgrade') {
 die('セキュリティチェック: dotProjectは既に構成されているようです。セキュリティ理由のため通信が壊れています！');
}
######################################################################################################################

$baseUrl = ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? 'https://' : 'http://';
$baseUrl .= isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : getenv('HTTP_HOST');
$baseUrl .= isset($_SERVER['SCRIPT_NAME']) ? dirname(dirname($_SERVER['SCRIPT_NAME'])) : dirname(dirname(getenv('SCRIPT_NAME')));

require_once DP_BASE_DIR.'/install/install.inc.php';

$AppUI = new InstallerUI; // Fake AppUI class to appease the db_connect utilities.

$dbMsg = '';
$cFileMsg = '作成されません';
$dbErr = false;
$cFileErr = false;

$dbtype = trim( dPInstallGetParam( $_POST, 'dbtype', 'mysql' ) );
$dbhost = trim( dPInstallGetParam( $_POST, 'dbhost', '' ) );
$dbname = trim( dPInstallGetParam( $_POST, 'dbname', '' ) );
$dbuser = trim( dPInstallGetParam( $_POST, 'dbuser', '' ) );
$dbpass = trim( dPInstallGetParam( $_POST, 'dbpass', '' ) );
$dbdrop = dPInstallGetParam( $_POST, 'dbdrop', false );
$mode = dPInstallGetParam( $_POST, 'mode', 'upgrade' );
$dbpersist = dPInstallGetParam( $_POST, 'dbpersist', false );
$dobackup = isset($_POST['dobackup']);
$do_db = isset($_POST['do_db']);
$do_db_cfg = isset($_POST['do_db_cfg']);
$do_cfg = isset($_POST['do_cfg']);

// Create a dPconfig array for dependent code
$dPconfig = array(
 'dbtype' => $dbtype,
 'dbhost' => $dbhost,
 'dbname' => $dbname,
 'dbpass' => $dbpass,
 'dbuser' => $dbuser,
 'dbpersist' => $dbpersist,
 'root_dir' => $baseDir,
 'base_url' => $baseUrl
);

// Version array for moving from version to version.
$versionPath = array(
	'1.0.2',
	'2.0-alpha',
	'2.0-beta',
	'2.0',
	'2.0.1',
	'2.0.2',
	'2.0.3',
	'2.0.4',
	'2.1-rc1',
	'2.1-rc2',
	'2.1',
	'2.1.1',
	'2.1.2'
);

global $lastDBUpdate;
$lastDBUpdate = '';

require_once( DP_BASE_DIR.'/lib/adodb/adodb.inc.php' );
@include_once DP_BASE_DIR.'/includes/version.php';

$db = NewADOConnection($dbtype);

if(!empty($db)) {
  $dbc = $db->Connect($dbhost,$dbuser,$dbpass);
  if ($dbc)
    $existing_db = $db->SelectDB($dbname);
} else { $dbc = false; }

// Quick hack to ensure MySQL behaves itself (#2323)
$db->Execute("SET sql_mode := ''");


$current_version = $dp_version_major . '.' . $dp_version_minor;
$current_version .= isset($dp_version_patch) ? ('.'.$dp_version_patch) : '';
$current_version .= isset($dp_version_prepatch) ? ('-'.$dp_version_prepatch) : '';

if ($dobackup){

 if( $dbc ) {
  require_once( DP_BASE_DIR.'/lib/adodb/adodb-xmlschema.inc.php' );

  $schema = new adoSchema( $db );

  $sql = $schema->ExtractSchema(true);

  header('Content-Disposition: attachment; filename="dPdbBackup'.date('Ymd').date('His').'.xml"');
  header('Content-Type: text/xml');
  echo $sql;
	exit;
 } else {
  $backupMsg = 'エラー: 有効なデータベースコネクションがありません！ - バックアップは動作しません！';
 }
}

?>
<html>
<head>
 <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
 <title>dotProjectインストーラー</title>
 <meta name="Description" content="dotProject Installer">
  <link rel="stylesheet" type="text/css" href="../style/default/main.css">
</head>
<body>
<h1><img src="dp.png" align="middle" alt="dotProject Logo"/>&nbsp;dotProjectインストーラー</h1>
<table cellspacing="0" cellpadding="3" border="0" class="tbl" width="100%" align="left">
<tr><td class="item" colspan="2" style="color:red">メモ: このページで表示される内容には、データベースが出力するメッセージを直接表示している場合があり、英語のままの可能性があります。</td></tr>
<tr class='title'><td>進捗:</td></tr>
<tr><td><pre>
<?php

if ($dobackup)
 dPmsg($backupMsg);

if ($dbc && ($do_db || $do_db_cfg)) {

 if ($mode == 'install') {

  if ($dbdrop) { 
   dPmsg('以前のデータベースを削除しています');
   $db->Execute('DROP DATABASE IF EXISTS `'.$dbname.'`'); 
	 $existing_db = false;
  }

  if (! $existing_db) {
		dPmsg('新しいデータベースを作成しています');
		$db->Execute('CREATE DATABASE '.$dbname.' CHARACTER SET utf8');//K.Sen@Itsutsubashi-20080824
         $dbError = $db->ErrorNo();
 
         if ($dbError <> 0 && $dbError <> 1007) {
                 $dbErr = true;
                $dbMsg .= 'データベースエラーが発生しました。データベースは作成されていません！提供されたデータベースの詳細はおそらく間違っています。<br>'.$db->ErrorMsg().'<br>';

         }
   }
 }

 // For some reason a db->SelectDB call here doesn't work.
 $db->Execute('USE `' . $dbname .'`');
 $db_version = InstallGetVersion($mode, $db);

 $code_updated = '';
 if ($mode == 'upgrade') {
  dPmsg('データベースのアップデートを適用します');
  $last_version = $db_version['code_version'];
  // Convert the code version to a version string.
  if ($last_version != $current_version) {
    // Check for from and to versions
    $from_key = array_search($last_version, $versionPath);
    $to_key = array_search($current_version, $versionPath);
    for ($i = $from_key; $i < $to_key; $i++) {
      $from_version = str_replace(array('.','-'), '', $versionPath[$i]);
      $to_version = str_replace(array('.','-'), '', $versionPath[$i+1]);
      // Only do updates since last update - this is only necessary if updating via CVS of a previous
      // version, but well worth doing anyway.
      InstallLoadSql(DP_BASE_DIR."/db/upgrade_{$from_version}_to_{$to_version}.sql", $db_version['last_db_update']);
      $db_version['last_db_update'] = $lastDBUpdate; // Global set by InstallLoadSql.
    }
  } else if (file_exists(DP_BASE_DIR.'/db/upgrade_latest.sql')) {
    // Need to get the installed version again, as it should have been
    // updated by the from/to stuff.
    InstallLoadSql(DP_BASE_DIR.'/db/upgrade_latest.sql', $db_version['last_db_update']);
  }
 } else {
  dPmsg('データベースインストール');
  InstallLoadSql(DP_BASE_DIR.'/db/dotproject.sql');
  // After all the updates, find the new version information.
  $new_version = InstallGetVersion($mode, $db);
  $lastDBUpdate = $new_version['last_db_update'];
  $code_updated = $new_version['last_code_update'];
 }

				$dbError = $db->ErrorNo();
        if ($dbError <> 0 && $dbError <> 1007) {
  $dbErr = true;
                $dbMsg .= 'データベースエラーが発生しました。データベースはおそらく完全には作成されていません！<br>'.$db->ErrorMsg().'<br>';
        }
 if ($dbErr) {
  $dbMsg = '不完全なデータベースセットアップ - 以下のエラーが発生しました:<br>'.$dbMsg;
 } else {
  $dbMsg = 'データベースを正しくセットアップしました<br>';
 }

 if ($mode == 'upgrade') {
  dPmsg('Applying data modifications');
  // Check for an upgrade script and run it if necessary.
  // Note we don't need to run individual version files any more
  if (file_exists(DP_BASE_DIR.'/db/upgrade_latest.php')) {
   include_once DP_BASE_DIR.'/db/upgrade_latest.php';
   $code_updated = dPupgrade($db_version['code_version'], $current_version, $db_version['last_code_update']);
  } else {
		dPmsg('データのアップデートは要求されていません');
	}
 } else {
  include_once DP_BASE_DIR.'/db/upgrade_permissions.php'; // Always required on install.
 }

 dPmsg('バージョン情報更新');
 // No matter what occurs we should update the database version in the dpversion table.
 if (empty($lastDBUpdate)) {
 	$lastDBUpdate = $code_updated;
 }
 $sql = "UPDATE dpversion
 SET db_version = '$dp_version_major',
 last_db_update = '$lastDBUpdate',
 code_version = '$current_version',
 last_code_update = '$code_updated'
 WHERE 1";
 $db->Execute($sql);

} else {
	$dbMsg = '作成されません';
	if (! $dbc) {
		$dbErr=1;
		$dbMsg .= '<br/>有効なデータベースコネクションがありません！ '  . ($db ? $db->ErrorMsg() : '');
	}
}

// always create the config file content

 dPmsg('コンフィグファイル作成');
 $config = '<?php '."\n";
 $config .= 'if (!defined(\'DP_BASE_DIR\')) {'."\n";
 $config .= '	die(\'このファイルに直接アクセスすることは出来ません。\');'."\n";
 $config .= '}'."\n";
 $config .= '### Copyright (c) 2004, The dotProject Development Team dotproject.net and sf.net/projects/dotproject ###'."\n";
 $config .= '### All rights reserved. Released under GPL License. For further Information see LICENSE ###'."\n";
 $config .= "\n";
 $config .= '### CONFIGURATION FILE AUTOMATICALLY GENERATED BY THE DOTPROJECT INSTALLER ###'."\n";
 $config .= '### FOR INFORMATION ON MANUAL CONFIGURATION AND FOR DOCUMENTATION SEE ./includes/config-dist.php ###'."\n";
 $config .= "\n";
 $config .= '$dPconfig[\'dbtype\'] = \''.$dbtype.'\';'."\n";
 $config .= '$dPconfig[\'dbhost\'] = \''.$dbhost.'\';'."\n";
 $config .= '$dPconfig[\'dbname\'] = \''.$dbname.'\';'."\n";
 $config .= '$dPconfig[\'dbuser\'] = \''.$dbuser.'\';'."\n";
 $config .= '$dPconfig[\'dbpass\'] = \''.$dbpass.'\';'."\n";
 $config .= '$dPconfig[\'dbpersist\'] = ' . ($dbpersist ? 'true' : 'false') . ";\n";
 $config .= '$dPconfig[\'root_dir\'] = $baseDir;'."\n";
 $config .= '$dPconfig[\'base_url\'] = $baseUrl;'."\n";
 $config .= '?>';
 $config = trim($config);

if ($do_cfg || $do_db_cfg){
 if ( (is_writable('../includes/config.php')  || ! is_file('../includes/config.php') ) && ($fp = fopen('../includes/config.php', 'w'))) {
  fputs( $fp, $config, strlen( $config ) );
  fclose( $fp );
  $cFileMsg = 'コンフィグファイルの書き込みは成功しました'."\n";
 } else {
  $cFileErr = true;
  $cFileMsg = 'コンフィグファイルを書き込むことができませんでした'."\n";
 }
}

//echo $msg;
?>
</pre></td></tr>
</table><br/>
<table cellspacing="0" cellpadding="3" border="0" class="tbl" width="100%" align="left">
        <tr>
            <td class="title" valign="top">データベースインストールフィードバック:</td>
     <td class="item"><b style="color:<?php echo $dbErr ? 'red' : 'green'; ?>"><?php echo $dbMsg; ?></b><?php if ($dbErr) { ?> <br />
		   アップグレードの間のインデックスのドロップに関するエラーは<b>通常</b>問題を示さない点に注意してください。
			 <?php } ?>
			 </td>
         <tr>
  <tr>
            <td class="title">コンフィグファイル作成フィードバック:</td>
     <td class="item" align="left"><b style="color:<?php echo $cFileErr ? 'red' : 'green'; ?>"><?php echo $cFileMsg; ?></b></td>
  </tr>
<?php if(($do_cfg || $do_db_cfg) && $cFileErr){ ?>
 <tr>
	 <td class="item" align="left" colspan="2">以下の内容は./includes/config.phpに書かなければなりません。テキストファイルを作成し、以下の内容をコピーしてください。「?>」以降のすべての空白と空行を削除して保存します。このファイルをウェブサーバによって読み込まれるようにしなければなりません。</td>
  </tr>
         <tr>
            <td align="center" colspan="2"><textarea class="button" name="dbhost" cols="100" rows="20" title="config.phpを手動で作成するための内容" /><?php echo $msg.$config; ?></textarea></td>
         </tr>
<?php } ?>
 <tr>
     <td class="item" align="center" colspan="2"><br/><b><a href="<?php echo $baseUrl.'/index.php?m=system&a=systemconfig';?>">ログインとdotProjectのシステム環境構成</a></b></td>
  </tr>
<?php if ($mode == 'install') { ?>
	<tr>
		<td class="item" align="center" colspan="2"><p>管理者ログインはユーザー名: <b>admin</b> パスワード: <b>passwd</b> でログインできます。 ログイン後、パスワードを変更してください。</p></td>
	</tr>
<?php } ?>
        </table>
</body>
</html>
