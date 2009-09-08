<?php // $Id: db.php 4791 2007-02-26 21:04:48Z merlinyoda $
include_once 'check_upgrade.php';
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
<?php
if ( $_POST['mode'] == 'upgrade')
	@include_once '../includes/config.php';
else if (dPcheckUpgrade() == 'upgrade')
	die('セキュリティチェック: dotProjectは既に構成されているようです。インストールを中断しました！');
else
	@include_once '../includes/config-dist.php';

?>
<form name="instFrm" action="do_install_db-ja.php" method="post">
<input type='hidden' name='mode' value='<?php echo $_POST['mode']; ?>' />
<table cellspacing="0" cellpadding="3" border="0" class="tbl" width="100%" align="center">
        <tr>
            <td class="title" colspan="2">データベース設定</td>
        </tr>
         <tr>
            <td class="item">データベースサーバタイプ <span class='warning'>注意 - 現在はMySQLのみが正しく動作します</span></td>
            <td align="left">
		<select name="dbtype" size="1" style="width:200px;" class="text">
<?php
   if (strstr('WIN', strtoupper(PHP_OS)) !== false) {
?>
			<option value="access">MS Access</option>
			<option value="ado">Generic ADO</option>
			<option value="ado_access">ADO to MS Access Backend</option>
			<option value="ado_mssql">ADO to MS SQL Server</option>

			<option value="vfp">MS Visual FoxPro</option>
			<option value="fbsql">FrontBase</option>
<?php
}
?>
			<option value="db2">IBM DB2</option>
			<option value="ibase">Interbase 6以前</option>
			<option value="firebird">Firebird</option>
			<option value="borland_ibase">Borland Interbase 6.5以上</option>

			<option value="informix">Informix 7.3以上</option>
			<option value="informix72">Informix 7.2以前</option>
			<option value="ldap">LDAP</option>
			<option value="mssql">MS SQL Server 7以上</option>
			<option value="mssqlpro">Portable MS SQL Server</option>
			<option value="mysql" selected="selected">MySQL - 推奨</option>

			<option value="mysqlt">MySQL With Transactions</option>
			<option value="maxsql">MySQL MaxDB</option>
			<option value="oci8">Oracle 8/9</option>
			<option value="oci805">Oracle 8.0.5</option>
			<option value="oci8po">Oracle 8/9 Portable</option>
			<option value="odbc">ODBC</option>

			<option value="odbc_mssql">MS SQL Server via ODBC</option>
			<option value="odbc_oracle">Oracle via ODBC</option>
			<option value="odbtp">Generic Odbtp</option>
			<option value="odbtp_unicode">Odbtp With Unicode Support</option>
			<option value="oracle">Older Oracle</option>
			<option value="netezza">Netezza</option>

			<option value="postgres">Generic PostgreSQL</option>
			<option value="postgres64">PostreSQL 6.4以前</option>
			<option value="postgres7">PostgreSQL 7</option>
			<option value="sapdb">SAP DB</option>
			<option value="sqlanywhere">Sybase SQL Anywhere</option>
			<option value="sqlite">SQLite</option>

			<option value="sqlitepo">Portable SQLite</option>
			<option value="sybase">Sybase</option>
		</select>
	   </td>
  	 </tr>
         <tr>
            <td class="item">データベースホスト名</td>
            <td align="left"><input class="button" type="text" name="dbhost" value="<?php echo $dPconfig['dbhost']; ?>" title="データベースがインストールされたサーバのホスト名" /></td>
          </tr>
           <tr>
            <td class="item">データベース名</td>
            <td align="left"><input class="button" type="text" name="dbname" value="<?php echo  $dPconfig['dbname']; ?>" title="dotProjectをインストールし、使用するデータベースの名前" /></td>
          </tr>
          <tr>
            <td class="item">データベースユーザー名</td>
			<td align="left"><input class="button" type="text" name="dbuser" value="<?php echo $dPconfig['dbuser']; ?>" title="dotProjectがデータベースコネクションに使用するデータベースユーザー" /></td>
          </tr>
          <tr>
            <td class="item">データベースユーザーパスワード</td>
            <td align="left"><input class="button" type="password" name="dbpass" value="<?php echo $dPconfig['dbpass']; ?>" title="上記ユーザーのパスワード" /></td>
          </tr>
           <tr>
            <td class="item">継続的なデータベースコネクションを使用しますか？</td>
            <td align="left"><input type="checkbox" name="dbpersist" value="1" <?php echo ($dPconfig['dbpersist']==true) ? 'checked="checked"' : ''; ?> title="データベースサーバへの継続的な接続を使用" /></td>
          </tr>
<?php if ($_POST['mode'] == 'install') { ?>
          <tr>
            <td class="item">既存データベースを削除しますか？</td>
            <td align="left"><input type="checkbox" name="dbdrop" value="1" title="既存のデータベースを削除してから新規データベースをインストールします。データベース上のすべてのデータが削除されます。" /><span class="item"> チェックした場合、存在するデータはすべて消失します！</span></td>
        </tr>
<?php } ?>
        </tr>
          <tr>
            <td class="title" colspan="2">&nbsp;</td>
        </tr>
          <tr>
            <td class="title" colspan="2">存在するデータをダウンロードする(推奨)</td>
        </tr>
        <tr>
            <td class="item" colspan="2">「XMLをダウンロード」ボタンをクリックしますと、先ほど入力したデータベースのすべてのテーブルの内容のXMLファイルをダウンロードできます。
			このファイルにより、バックアップモジュールで以前のシステムをリストアすることができます。
			データベースサイズやシステム環境によって、このプロセスは多くの時間を要します。
		<br/>エラーメッセージがこのファイルの中に書き込まれているときは、内容と一貫性について、すぐに受け取ったファイルをチェックしてください。<br/><br /><b>このファイルはバックアップモジュールがインストールされたdotProject 2.xのシステムに限り、リストアを行うことが可能となります。ただ一つのバックアップとしての利用はしないでください。</b></td>
        </tr>
        <tr>
            <td class="item">XMLバックアップファイルをダウンロード</td>
            <td align="left"><input class="button" type="submit" name="dobackup" value="ダウンロード" title="クリックするとデータを取り出しXMLファイルでローカルに保存することができます" /></td>
        </tr>
		<tr>
		    <td class="item">データベースの<?php echo $_POST['mode'] == 'install' ? 'インストール' : 'アップグレード'; ?>のみを行う</td>
		    <td align="left"><input class="button" type="submit" name="do_db" value="実行" title="与えられた情報でデータベースの設定を試行します" /></td>
		</tr>
		<tr>
		    <td class="item">コンフィグファイルの書き込み</td>
		    <td align="left"><input class="button" type="submit" name="do_cfg" value="実行" title="詳細なコンフィグファイルを書き込みます" /></td>
		</tr>
	    <tr>
	        <td class="item">データベースの<?php echo $_POST['mode'] == 'install' ? 'インストール' : 'アップグレード'; ?>とコンフィグファイルの書き込み (推奨)</td>
	        <td align="left"><input class="button" type="submit" name="do_db_cfg" value="実行" title="コンフィグファイルの書き込みと与えられた情報によるデータベースのセットアップ" /></td>
        </tr>
        </table>
</form>
</body>
</html>
