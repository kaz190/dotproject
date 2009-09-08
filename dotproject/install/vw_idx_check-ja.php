<?php // $Id: vw_idx_check.php 4791 2007-02-26 21:04:48Z merlinyoda $

if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

global $cfgDir, $cfgFile, $failedImg, $filesDir, $locEnDir, $okImg, $tblwidth, $tmpDir;
global $locJaDir;

$cfgDir = isset($cfgDir) ? $cfgDir : DP_BASE_DIR.'/includes';
$cfgFile = isset($cfgFile) ? $cfgFile : DP_BASE_DIR.'/includes/config.php';
$filesDir = isset($filesDir) ? $filesDir : DP_BASE_DIR.'/files';
$locEnDir = isset($locEnDir) ? $locEnDir : DP_BASE_DIR.'/locales/en';
$locJaDir = isset($locJaDir) ? $locJaDir : DP_BASE_DIR.'/locales/ja';
$tmpDir = isset($tmpDir) ? $tmpDir : DP_BASE_DIR.'/files/temp';
$tblwidth = isset($tblwidth) ? $tblwidth :'100%';
$chmod = 0777;

function dPgetIniSize($val) {
   $val = trim($val);
   if (strlen($val <= 1)) return $val;
   $last = $val{strlen($val)-1};
   switch($last) {
       case 'k':
       case 'K':
           return (int) $val * 1024;
           break;
       case 'm':
       case 'M':
           return (int) $val * 1048576;
           break;
       default:
           return $val;
   }
}

?>

<table cellspacing="0" cellpadding="3" border="0" class="tbl" width="<?php echo $tblwidth; ?>" align="center">
<tr>
            <td class="title" colspan="2">必須項目チェック</td>
</tr>
<tr>
 <td class="item"><li>PHPバージョン &gt;= 4.1</li></td>
 <td align="left"><?php echo version_compare(phpversion(), '4.1', '<') ? '<b class="error">'.$failedImg.' ('.phpversion().'): dotProject may not work. Please upgrade!</b>' : '<b class="ok">'.$okImg.'</b><span class="item"> ('.phpversion().')</span>';?></td>
</tr>
<tr>
 <td class="item"><li>サーバAPI</li></td>
  <td align="left"><?php echo (php_sapi_name() != 'cgi') ? '<b class="ok">'.$okImg.'</b><span class="item"> ('.php_sapi_name().')</span>' : '<b class="error">'.$failedImg.' CGI mode is likely to have problems</b>';?></td>
</tr>

<tr>
 <td class="item"><li>GDサポート(ガントチャート用)</li></td>
  <td align="left"><?php echo extension_loaded('gd') ? '<b class="ok">'.$okImg.'</b>' : '<b class="error">'.$failedImg.'</b> GANTT Chart functionality may not work correctly.';?></td>
</tr>
<tr>
 <td class="item"><li>Zlib圧縮サポート</li></td>
  <td align="left"><?php echo extension_loaded('zlib') ? '<b class="ok">'.$okImg.'</b>' : '<b class="error">'.$failedImg.'</b> Some non-core modules such as Backup may have restricted operation.';?></td>
</tr>
<?php
$maxfileuploadsize = min(dPgetIniSize(ini_get('upload_max_filesize')), dPgetIniSize(ini_get('post_max_size')));
$memory_limit = dPgetIniSize(ini_get('memory_limit'));
if ($memory_limit > 0 && $memory_limit < $maxfileuploadsize) $maxfileuploadsize = $memory_limit;
// Convert back to human readable numbers
if ($maxfileuploadsize > 1048576) {
	$maxfileuploadsize = (int)($maxfileuploadsize / 1048576) . 'M';
} else if ($maxfileuploadsize > 1024) {
	$maxfileuploadsize = (int)($maxfileuploadsize / 1024) . 'K';
}
?>

<tr>
 <td class="item"><li>ファイルアップロード</li></td>
  <td align="left"><?php echo ini_get('file_uploads') ? '<b class="ok">'.$okImg.'</b><span class="item"> (最大サイズ: '. $maxfileuploadsize .')</span>' : '<b class="error">'.$failedImg.'</b><span class="warning"> ファイルアップロード機能が有効になっていません</span>';?></td>
</tr>
<tr>
            <td class="item"><li>セッション保存パス</li></td>
            <td align="left">
<?php 
	$sspath = ini_get('session.save_path');
	if (! $sspath) {
		echo "<b class='error'>$failedImg 致命的エラー:</b> <span class='item'>session.save_path</span> <b class='error'>は設定されていません</b>";
	} else if (is_dir($sspath) && is_writable($sspath)) {
		echo "<b class='ok'>$okImg</b> <span class='item'>($sspath)</span>";
	} else {
		echo "<b class='error'>$failedImg 致命的エラー:</b> <span class='item'>$sspath</span><b class='error'> は存在しないか、書き込めません</b>";
	}
	?></td>
</tr>
<tr>
            <td class="title" colspan="2"><br />データベース接続</td>
</tr>
<tr>
			<td class="item" colspan="2"><p>次のチェックはPHPのデータベースサポートについてです。多くのデータベースドライバの抽象データベースレイヤーのADODBを使用します。詳細はADODBドキュメントを参照してください。
<p>当面はMySQLのみが完全にサポートされるので、MySQLを利用できることを確認する必要があります。</td>
</tr>
<tr>
 <td class="item"><li>iBaseサポート</li></td>
  <td align="left"><?php echo ( function_exists( 'ibase_connect' ) && function_exists( 'ibase_server_info' )) ? '<b class="ok">'.$okImg.'</b><span class="item"> ('.ibase_server_info().')</span>' : '<span class="warning">'.$failedImg.' 利用できません</span>';?></td>
</tr>
<tr>
 <td class="item"><li>Informixサポート</li></td>
  <td align="left"><?php echo function_exists( 'ifx_connect' ) ? '<b class="ok">'.$okImg.'</b><span class="item"> </span>' : '<span class="warning">'.$failedImg.' 利用できません</span>';?></td>
</tr>
<tr>
 <td class="item"><li>LDAPサポート</li></td>
  <td align="left"><?php echo function_exists( 'ldap_connect' ) ? '<b class="ok">'.$okImg.'</b><span class="item"> </span>' : '<span class="warning">'.$failedImg.' 利用できません</span>';?></td>
</tr>
<tr>
 <td class="item"><li>mSQLサポート</li></td>
  <td align="left"><?php echo function_exists( 'msql_connect' ) ? '<b class="ok">'.$okImg.'</b><span class="item"></span>' : '<span class="warning">'.$failedImg.' 利用できません</span>';?></td>
</tr>
<tr>
 <td class="item"><li>MSSQL Serverサポート</li></td>
  <td align="left"><?php echo function_exists( 'mssql_connect' ) ? '<b class="ok">'.$okImg.'</b><span class="item"></span>' : '<span class="warning">'.$failedImg.' 利用できません</span>';?></td>
</tr>
<tr>
 <td class="item"><li>MySQLサポート</li></td>
  <td align="left"><?php echo function_exists( 'mysql_connect' ) ? '<b class="ok">'.$okImg.'</b><span class="item"> ('.@mysql_get_server_info().')</span>' : '<span class="warning">'.$failedImg.' 利用できません</span>';?></td>
</tr>
<tr>
 <td class="item"><li>ODBCサポート</li></td>
  <td align="left"><?php echo function_exists( 'odbc_connect' ) ? '<b class="ok">'.$okImg.'</b><span class="item"></span>' : '<span class="warning">'.$failedImg.' 利用できません</span>';?></td>
</tr>
<tr>
 <td class="item"><li>Oracleサポート</li></td>
  <td align="left"><?php echo function_exists( 'oci_connect' ) ? '<b class="ok">'.$okImg.'</b><span class="item"> ('.ociserverversion().')</span>' : '<span class="warning">'.$failedImg.' 利用できません</span>';?></td>
</tr>
<tr>
 <td class="item"><li>PostgreSQLサポート</li></td>
  <td align="left"><?php echo function_exists( 'pg_connect' ) ? '<b class="ok">'.$okImg.'</b><span class="item"></span>' : '<span class="warning">'.$failedImg.' 利用できません</span>';?></td>
</tr>
<tr>
 <td class="item"><li>SQLiteサポート</li></td>
  <td align="left"><?php echo function_exists( 'sqlite_open' ) ? '<b class="ok">'.$okImg.'</b><span class="item"> ('.sqlite_libversion().')</span>' : '<span class="warning">'.$failedImg.' 利用できません</span>';?></td>
</tr>
<tr>
 <td class="item"><li>Sybaseサポート</li></td>
  <td align="left"><?php echo function_exists( 'sybase_connect' ) ? '<b class="ok">'.$okImg.'</b><span class="item"> </span>' : '<span class="warning">'.$failedImg.' 利用できません</span>';?></td>
</tr>
<tr>
            <td class="title" colspan="2"><br />ディレクトリとファイルのパーミッションチェック</td>
</tr>
<tr>
			<td class="item" colspan="2">「World Writable」というメッセージがファイル、ディレクトリの後に表示されていれば、該当ファイルの書き込みパーミッションが設定されています。
			セキュリティを改善するために、より制限的な設定に変更することを考えてください。その場合は手動で行います。</td>
</tr>
<?php
$okMessage='';
if ( (file_exists( $cfgFile ) && !is_writable( $cfgFile )) || (!file_exists( $cfgFile ) && !(is_writable( $cfgDir ))) ) {

        @chmod( $cfgFile, $chmod );
        @chmod( $cfgDir, $chmod );
 $filemode = @fileperms($cfgFile);
if ($filemode & 2) {
	$okMessage='<span class="error"> World Writable</span>';
}

 }
?>
<tr>
            <td class="item">./includes/config.phpを書き込めますか？</td>
            <td align="left"><?php echo ( is_writable( $cfgFile ) || is_writable( $cfgDir ))  ? '<b class="ok">'.$okImg.'</b>'.$okMessage : '<b class="error">'.$failedImg.'</b><span class="warning"> 構成プロセスはまだ続行することが可能です。コンフィグファイルの内容は、最後に表示されますので、これをコピー＆貼り付けし、アップロードします。</span>';?></td>
</tr>
<?php
$okMessage="";
if (!is_writable( $filesDir )) {
        @chmod( $filesDir, $chmod );
}
$filemode = @fileperms($filesDir);
if ($filemode & 2) {
	$okMessage='<span class="error"> World Writable</span>';
}
?>
<tr>
			<td class="item">./filesは書き込めますか？</td>
            <td align="left"><?php echo is_writable( $filesDir ) ? '<b class="ok">'.$okImg.'</b>'.$okMessage : '<b class="error">'.$failedImg.'</b><span class="warning"> ファイルアップロードは不可能となります</span>';?></td>
</tr>
<?php
$okMessage="";
if (!is_writable( $tmpDir ))
        @chmod( $tmpDir, $chmod );

$filemode = @fileperms($tmpDir);
if ($filemode & 2) {
	$okMessage='<span class="error"> World Writable</span>';
}
?>
<tr>
            <td class="item">./files/tempは書き込めますか？</td>
            <td align="left"><?php echo is_writable( $tmpDir ) ? '<b class="ok">'.$okImg.'</b>'.$okMessage : '<b class="error">'.$failedImg.'</b><span class="warning"> PDFレポートの生成は不可能となります</span>';?></td>
</tr>
<?php
$okMessage="";
if (!is_writable( $locEnDir )) {
        @chmod( $locEnDir, $chmod );
}
$filemode = @fileperms($locEnDir);
if ($filemode & 2) {
	$okMessage='<span class="error"> World Writable</span>';
}
?>
<tr>
			<td class="item">./locales/enは書き込めますか？</td>
            <td align="left"><?php echo is_writable( $locEnDir ) ? '<b class="ok">'.$okImg.'</b>'.$okMessage : '<b class="error">'.$failedImg.'</b><span class="warning"> 翻訳ファイルを保存することが出来ません。'.DP_BASE_DIR.'/localesとサブディレクトリのパーミッションをチェックしてください。</span>';?></td>
</tr>
<?php
$okMessage="";
if (!is_writable( $locJaDir )) {
        @chmod( $locJaDir, $chmod );
}
$filemode = @fileperms($locJaDir);
if ($filemode & 2) {
	$okMessage='<span class="error"> World Writable</span>';
}
?>
<tr>
			<td class="item">./locales/jaは書き込めますか？</td>
            <td align="left"><?php echo is_writable( $locJaDir ) ? '<b class="ok">'.$okImg.'</b>'.$okMessage : '<b class="error">'.$failedImg.'</b><span class="warning"> 翻訳ファイルを保存することが出来ません。'.DP_BASE_DIR.'/localesとサブディレクトリのパーミッションをチェックしてください。</span>';?></td>
</tr>
<tr>
            <td class="title" colspan="2"><br/>推奨されるPHP設定</td>
</tr>
<tr>
            <td class="item">Safe Mode = OFF ですか？</td>
            <td align="left"><?php echo !ini_get('safe_mode') ? '<b class="ok">'.$okImg.'</b>' : '<b class="error">'.$failedImg.'</b><span class="warning"></span>';?></td>
</tr>
<tr>
            <td class="item">Register Globals = OFF ですか？</td>
			<td align="left"><?php echo !ini_get('register_globals') ? '<b class="ok">'.$okImg.'</b>' : '<b class="error">'.$failedImg.'</b><span class="warning"> ONの場合、セキュリティリスクがあります</span>';?></td>
</tr>
<tr>
            <td class="item">Session AutoStart = ON ですか？</td>
            <td align="left"><?php echo ini_get('session.auto_start') ? '<b class="ok">'.$okImg.'</b>' : '<b class="error">'.$failedImg.'</b><span class="warning"> もし真っ白な画面になって終了してしまうことを経験しているならONに設定してください</span>';?></td>
</tr>
<tr>
            <td class="item">Session Use Cookies = ON ですか？</td>
            <td align="left"><?php echo ini_get('session.use_cookies') ? '<b class="ok">'.$okImg.'</b>' : '<b class="error">'.$failedImg.'</b><span class="warning"> もしログインに問題が起きることを経験しているならONに設定してください</span>';?></td>
</tr>
<tr>
            <td class="item">Session Use Trans Sid = OFF ですか？</td>
            <td align="left"><?php echo (!ini_get('session.use_only_cookies') && !ini_get('session.use_trans_sid')) ? '<b class="ok">'.$okImg.'</b>' : '<b class="error">'.$failedImg.'</b><span class="warning"> ONの場合、セキュリティリスクがあります</span>';?></td>
</tr>
<tr>
            <td class="title" colspan="2"><br/>他の勧告</td>
</tr>
<tr>
            <td class="item" colspan="2">
						<p>dotProjectチームは自由なオープンソースソフトウェア(Free Open Source software: FOSS)を推奨します。dotProjectがFOSSアプリケーションだから当然なのではありません。FOSSの開発手法が結果として総所有コスト(Total Cost of Ownership: TCO)を下げつつ、良いソフトウェアになると信じているからです。
						<p>これらの勧告はその信念に基づきFOSS開発者としてFOSSシステムを開発し、他の非FOSSシステムより素早くより良いサポートを提供するという事実を反映します。</td>
</tr>
<tr>
            <td class="item">自由なOSですか？</td>
            <td align="left"><?php echo (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN') ? '<b class="ok">'.$okImg.'</b><span class="item"> ('.php_uname().')</span>' : '<b class="error">'.$failedImg.'</b><span class="warning">
            プロプライエタリなOSを使用しているようです。Linuxのような自由なオープンソースのOSを検討してもいいでしょう。dotProjectは通常、初めにLinux上でテストされ、そして常に他のOSよりもLinuxに対して良いサポートをもたらします。
            </span>';?></td>
</tr>
<tr>
            <td class="item">サポートされたウェブサーバですか？</td>
            <td align="left"><?php echo (stristr($_SERVER['SERVER_SOFTWARE'], 'apache') != false) ? '<b class="ok">'.$okImg.'</b><span class="item"> ('.$_SERVER['SERVER_SOFTWARE'].')</span>' : '<b class="error">'.$failedImg.'</b><span class="warning">
            サポートされていないウェブサーバを使用しているようです。Apacheウェブサーバのみ、dotProjectが完全にサポートしており、他のウェブサーバを使用した場合、予想外の問題が発生するかもしれません。
            </span>';?></td>
</tr>
<tr>
            <td class="item">標準対応のブラウザですか？</td>
            <td align="left"><?php echo (stristr($_SERVER['HTTP_USER_AGENT'], 'msie') == false) ? '<b class="ok">'.$okImg.'</b><span class="item"> ('.$_SERVER['HTTP_USER_AGENT'].')</span>' : '<b class="error">'.$failedImg.'</b><span class="warning">
            Internet Explorerを使用しているようです。このブラウザはセキュリティリスクが尋常ではないほど多いことで知られており、さらに悪いことには標準対応ではありません。Firefoxのようなブラウザを使用しましょう。dotProjectチームでは初めにFirefoxで動作するように開発します。
            </span>';?></td>
</tr>
</table>
