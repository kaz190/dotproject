<?php /* PROJECTS $Id: tasklogs.php 5784 2008-07-26 03:56:57Z ajdonnison $ */
if (!defined('DP_BASE_DIR')){
  die('You should not access this file directly.');
}

/**
* Generates a report of the task logs for given dates
*/
$perms =& $AppUI->acl();
if (! $perms->checkModule('tasks', 'view')) {
	redirect('m=public&a=access_denied');
}	
$do_report = dPgetParam($_GET, "do_report", 0);
$log_all = dPgetParam($_GET, 'log_all', 0);
$log_pdf = dPgetParam($_GET, 'log_pdf', 0);
$log_ignore = dPgetParam($_GET, 'log_ignore', 0);
$log_userfilter = dPgetParam($_GET, 'log_userfilter', '0');

$log_start_date = dPgetParam($_GET, "log_start_date", 0);
$log_end_date = dPgetParam($_GET, "log_end_date", 0);

// create Date objects from the datetime fields
$start_date = intval($log_start_date) ? new CDate($log_start_date) : new CDate();
$end_date = intval($log_end_date) ? new CDate($log_end_date) : new CDate();

if (!$log_start_date) {
	$start_date->subtractSpan(new Date_Span("14,0,0,0"));
}
$end_date->setTime(23, 59, 59);

?>
<script language="javascript">
var calendarField = '';

function popCalendar(field){
	calendarField = field;
	idate = eval('document.editFrm.log_' + field + '.value');
	window.open('index.php?m=public&a=calendar&dialog=1&callback=setCalendar&date=' + idate, 'calwin', 'width=250, height=220, scrollbars=no, status=no');
}

/**
 *	@param string Input date in the format YYYYMMDD
 *	@param string Formatted date
 */
function setCalendar(idate, fdate) {
	fld_date = eval('document.editFrm.log_' + calendarField);
	fld_fdate = eval('document.editFrm.' + calendarField);
	fld_date.value = idate;
	fld_fdate.value = fdate;
}
</script>

<table cellspacing="0" cellpadding="4" border="0" width="100%" class="std">

<form name="editFrm" action="" method="GET">
<input type="hidden" name="m" value="projects" />
<input type="hidden" name="a" value="reports" />
<input type="hidden" name="project_id" value="<?php echo $project_id;?>" />
<input type="hidden" name="report_type" value="<?php echo $report_type;?>" />

<tr>
	<td align="right" nowrap="nowrap"><?php echo $AppUI->_('For period');?>:</td>
	<td nowrap="nowrap">
		<input type="hidden" name="log_start_date" value="<?php echo $start_date->format(FMT_TIMESTAMP_DATE);?>" />
		<input type="text" name="start_date" value="<?php echo $start_date->format($df);?>" class="text" disabled="disabled" style="width: 80px" />
		<a href="#" onClick="popCalendar('start_date')">
			<img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0" />
		</a>
	</td>
	<td align="right" nowrap="nowrap"><?php echo $AppUI->_('to');?></td>
	<td nowrap="nowrap">
		<input type="hidden" name="log_end_date" value="<?php echo $end_date ? $end_date->format(FMT_TIMESTAMP_DATE) : '';?>" />
		<input type="text" name="end_date" value="<?php echo $end_date ? $end_date->format($df) : '';?>" class="text" disabled="disabled" style="width: 80px"/>
		<a href="#" onClick="popCalendar('end_date')">
			<img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0" />
		</a>
	</td>

	<TD NOWRAP>
		<?php echo $AppUI->_('User');?>:
		<SELECT NAME="log_userfilter" CLASS="text" STYLE="width: 80px">

	<?php
		$usersql = "
		SELECT user_id, user_username, contact_first_name, contact_last_name
		FROM users LEFT JOIN contacts ON user_contact = contact_id ORDER BY user_username
		";

		if ($log_userfilter == 0) {
			echo '<OPTION VALUE="0" SELECTED>'.$AppUI->_('All users');
		} else {
			echo '<OPTION VALUE="0">All users';
		}

		if (($rows = db_loadList($usersql, NULL))) {
			foreach ($rows as $row) {
				if ($log_userfilter == $row["user_id"])
					echo "<OPTION VALUE='".$row["user_id"]."' SELECTED>".$row["user_username"];
				else
					echo "<OPTION VALUE='".$row["user_id"]."'>".$row["user_username"];
			}
		}

	?>

		</SELECT>
	</TD>

	<td nowrap="nowrap">
		<input type="checkbox" name="log_all" id="log_all" <?php if ($log_all) echo 'checked="checked"' ?> />
		<label for="log_all"><?php echo $AppUI->_('Log All');?></label>
	</td>

	<td nowrap="nowrap">
		<input type="checkbox" name="log_pdf" id="log_pdf" <?php if ($log_pdf) echo 'checked="checked"' ?> />
		<label for="log_pdf"><?php echo $AppUI->_('Make PDF');?></label>
	</td>

	<td nowrap="nowrap">
		<input type="checkbox" name="log_ignore" id="log_ignore" />
		<label for="log_ignore"><?php echo $AppUI->_('Ignore 0 hours');?></label>
	</td>

	<td align="right" width="50%" nowrap="nowrap">
		<input class="button" type="submit" name="do_report" value="<?php echo $AppUI->_('submit');?>" />
	</td>
</tr>
</form>
</table>

<?php
if ($do_report) {

	$sql = "SELECT p.project_id, p.project_name, t.*, CONCAT_WS(' ',contact_first_name,contact_last_name) AS creator, " 
		."\n if(bc.billingcode_name is null, '', bc.billingcode_name) as billingcode_name"
		."\nFROM task_log AS t"
		."\nLEFT JOIN billingcode bc ON bc.billingcode_id = t.task_log_costcode "
		."\nLEFT JOIN users AS u ON user_id = task_log_creator"
                ."\nLEFT JOIN contacts ON user_contact = contact_id, tasks"
		."\nLEFT JOIN projects p ON p.project_id = task_project"
		."\nWHERE task_log_task = task_id";
	if ($project_id != 0) {
		$sql .= "\nAND task_project = $project_id";
	}
	if (!$log_all) {
		$sql .= "\n	AND task_log_date >= '".$start_date->format(FMT_DATETIME_MYSQL)."'"
		."\n	AND task_log_date <= '".$end_date->format(FMT_DATETIME_MYSQL)."'";
	}
	if ($log_ignore) {
		$sql .= "\n	AND task_log_hours > 0";
	}
	if ($log_userfilter) {
		$sql .= "\n	AND task_log_creator = $log_userfilter";
	}

	$proj = new CProject;
	$allowedProjects = $proj->getAllowedSQL($AppUI->user_id, 'task_project');
	if (count($allowedProjects)) {
		$sql .= "\n     AND " . implode(" AND ", $allowedProjects);
	}

	$obj = new CTask;
	$allowedTasks = $obj->getAllowedSQL($AppUI->user_id, 'tasks.task_id');
	if (count($allowedTasks)) {
		$sql .= ' AND ' . implode(' AND ', $allowedTasks);
	}
	$allowedChildrenTasks = $obj->getAllowedSQL($AppUI->user_id, 'tasks.task_parent');
	if (count($allowedChildrenTasks)) {
		$sql .= ' AND ' . implode(' AND ', $allowedChildrenTasks);
	}

	$sql .= " ORDER BY task_log_date";

	//echo "<pre>$sql</pre>";

	$logs = db_loadList($sql);
	echo db_error();
?>
	<table cellspacing="1" cellpadding="4" border="0" class="tbl">
	<tr>
		<th nowrap="nowrap"><?php echo $AppUI->_('Created by');?></th>
		<?php if ($project_id == 0) { ?>
			<th><?php echo $AppUI->_('Project');?></th>
		<?php } ?>
		<th><?php echo $AppUI->_('Summary');?></th>
		<th><?php echo $AppUI->_('Description');?></th>
		<th><?php echo $AppUI->_('Date');?></th>
		<th><?php echo $AppUI->_('Hours');?></th>
		<th><?php echo $AppUI->_('Cost Code');?></th>
	</tr>
<?php
	$hours = 0.0;
	$pdfdata = array();

        foreach ($logs as $log) {
		$date = new CDate($log['task_log_date']);
		$hours += $log['task_log_hours'];

		$pdfdata[] = array(
			$log['creator'],
			$log['task_log_name'],
			$log['task_log_description'],
			$date->format($df),
			sprintf("%.2f", $log['task_log_hours']),
			$log['billingcode_name'],
		);
?>
	<tr>
		<td><?php echo $log['creator'];?></td>
		<?php if ($project_id == 0) { ?>
			<td><a href="index.php?m=projects&a=view&project_id=<?php echo $log['project_id']; ?>"><?php echo $log['project_name'] ?></a></td>
		<?php } ?>
		<td>
			<a href="index.php?m=tasks&a=view&tab=1&task_id=<?php echo $log['task_log_task'];?>&task_log_id=<?php echo $log['task_log_id'];?>"><?php echo $log['task_log_name'];?></a>
		</td>
		<td><?php
      $transbrk = "\n[translation]\n";
			$descrip = str_replace("\n", "<br />", $log['task_log_description']);
			$tranpos = strpos($descrip, str_replace("\n", "<br />", $transbrk));
			if ($tranpos === false) {
				echo $descrip;
			} else {
				$descrip = substr($descrip, 0, $tranpos);
				$tranpos = strpos($log['task_log_description'], $transbrk);
				$transla = substr($log['task_log_description'], $tranpos + strlen($transbrk));
				$transla = trim(str_replace("'", '"', $transla));
				echo $descrip."<div style='font-weight: bold; text-align: right'><a title='$transla' class='hilite'>[".$AppUI->_("translation")."]</a></div>";
			}
// dylan_cuthbert; auto-translation end
			?></td>
		<td><?php echo $date->format($df);?></td>
		<td align="right"><?php printf("%.2f", $log['task_log_hours']);?></td>
		<td><?php echo $log['billingcode_name'];?></td>
	</tr>
<?php
	}
	$pdfdata[] = array(
		'',
		'',
		'',
		$AppUI->_('Total Hours').':',
		sprintf("%.2f", $hours),
		'',
	);
?>
	<tr>
		<?php if ($project_id == 0) { ?>
			<td></td>
		<?php } ?>
		<td align="right" colspan="4"><?php echo $AppUI->_('Total Hours');?>:</td>
		<td align="right"><?php printf("%.2f", $hours);?></td>
	</tr>
	</table>
<?php
	if ($log_pdf) {
	// make the PDF file
		if ($project_id != 0) {
			$sql = "SELECT project_name FROM projects WHERE project_id=$project_id";
			$pname = db_loadResult($sql);
		} else {
			$pname = $AppUI->_('All Projects');
		}
		echo db_error();

		$temp_dir = DP_BASE_DIR.'/files/temp';
		
		$title = 'Task Logs';

	        $pdfheaders = array(
		        $AppUI->_('Created by'),
        		$AppUI->_('Summary'),
        		$AppUI->_('Description'),
        		$AppUI->_('Date'),
        		$AppUI->_('Hours'),
	        	$AppUI->_('Cost Code')
        	);

	        $pdfheaderdata[] = array (
	                                  '',
	                                  '',
	                                  '',
	                                  '',
	                                  '',
	                                  '',
	                                 );
       
// ---------------------------------------------------------
//Tcpdf Report Output [Itsutsubashi-K.Sen-200808-17] 

//Itsutsubashi-K.Sen-20090814 
require_once(DP_BASE_DIR .'/lib/tcpdf/config/lang/jpn.php');
require_once(DP_BASE_DIR .'/lib/tcpdf/tcpdf.php');	

//Define Placement Parameters
$header_w = 30;
$header_h = 4;
$header_line_gap = 5;
$str_pad = 5;

$l_gap = 0;
$cell_height = 7;
$width = 20; 
$x_start = PDF_MARGIN_LEFT;
$y_start = 10;
$line_gap = 5;
$x_max = 200;
$y_max = 160; 
$x =  $x_start ;
$y =  $y_start ;

// create new PDF document
$pdf = new TCPDF("L", PDF_UNIT, PDF_PAGE_FORMAT, true); 

// set document information
$pdf->SetCreator(PDF_CREATOR);
//$pdf->SetAuthor();
$pdf->SetTitle($AppUI->_('Task Log Report'));
$pdf->SetSubject($AppUI->_('Report as PDF'));
$pdf->SetKeywords("TCPDF, PDF");

// remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

//set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, 10, PDF_MARGIN_RIGHT);

//set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

//set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO); 

//set some language-dependent strings
$pdf->setLanguageArray($l); 

//initialize document
$pdf->AliasNbPages();

// add a page
$pdf->AddPage();

// ---------------------------------------------------------
// set font
$pdf->SetFont("arialunicid0", "B", 12); //B= bold , I = Italic , U = Underlined

//Document Header
//Line1
$slen = strlen(dPgetConfig( 'company_name' )."\n") + $str_pad;
$pdf->writeHTMLCell($header_w + $slen, $header_h, $x, $y,dPgetConfig( 'company_name' ));
$pdf->writeHTMLCell($header_w, $header_h,  $x + $slen, $y,date("Y/m/d"));

// (Width,Height,Text,Border,Align,Fill,Line,x,y,reset,stretch,ishtml,autopadding,maxh)

// Line break - Line2
$y = $y + $header_line_gap;

// set font
$pdf->SetFont("", "B", 12);

// Title 
$slen = mb_strlen($AppUI->_('Task Log Report')) + $str_pad;
$pdf->writeHTMLCell($header_w + $slen, $header_h, $x, $y,$AppUI->_('Task Log Report'));


// Line break - Line3 
$y = $y + $header_line_gap;

$slen = strlen($pname."\n") + $str_pad;
$pdf->writeHTMLCell($header_w + $slen, $header_h, $x, $y,$pname);

// Line break - Line4
$y = $y + $header_line_gap;

if ($log_all) {
    $slen = strlen($AppUI->_('All task log entries')) + $str_pad;
    $pdf->writeHTMLCell($header_w + $slen, $header_h, $x + 100, $y,$AppUI->_('All task log entries'));

} else {
    $slen = mb_strlen($AppUI->_('Task log entries from')."  ".$start_date->format($df).' ~ '.$end_date->format($df)) + $str_pad+10;
    $pdf->writeHTMLCell($header_w + $slen, $header_h, $x + 100, $y,$AppUI->_('Task log entries from')
                                                             ."  "
                                                             .$start_date->format($df)
                                                             .' ~ '
                                                             .$end_date->format($df));
  }


// Line break - Line5
$y = $y + $header_line_gap;
$y = $y + $header_line_gap;
$y = $y + $header_line_gap;

// Color and font restoration 
$pdf->SetFillColor(224, 235, 255); 
$pdf->SetTextColor(0); 

// Column Header 
$pdf->SetFont("", "B", 10); //B= bold , I = Italic , U = Underlined
$w = array(50, 40, 100, 30,30,30); 
$x_init=$x;

for($i = 0; $i < count($pdfheaders); $i++){ 
    $pdf->writeHTMLCell($w[$i], $header_h, $x_init, $y,$pdfheaders[$i]."\n",0,0,1);
    $x_init = $x_init + $w[$i];

}
 
// Line break - Line6
$row_height = round($pdf->getLastH());
$y = $y + $row_height;

// Color and font restoration 
$pdf->SetFont("", "", 10); //B= bold , I = Italic , U = Underlined
$pdf->SetFillColor(224, 235, 255); 
$pdf->SetTextColor(0); 


// Data 
$fill = 0; 
$x_init = $x;$pdf->SetXY($x_init,$y);
$max_rows = 1; 

foreach($pdfdata as $rows) {
    $lc = array();
    for($i = 0; $i < count($rows); $i++)
        $lc[] = $pdf->getNumLines($rows[$i],$w[$i]);
 
    //Max no of Lines the row occupies
    $linecount = max($lc);

	for($i = 0; $i < count($rows); $i++){    
		$pdf->MultiCell($w[$i], ($header_h * $linecount)+ $header_h, trim($rows[$i])."\n", 0, 'J', 0, 0, $x_init, $y, true);
    	//$pdf->writeHTMLCell($w[$i], $row_height, $x_init, $y,$rows[$i]."\n",0,0,0);
    	$x_init = $x_init + $w[$i];$pdf->SetX($x_init);
		
	} 
       
	// Line break - Line X
	$y = $y + ($header_h * $linecount)+ $header_h ; 
    
    if ($y > $y_max){
        $pdf->AddPage();
	    $y =  $y_start ;
    }

    $x_init = $x;$pdf->SetXY($x_init,$y);
    $fill=!$fill;  
} 


// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output("$temp_dir/temp$AppUI->user_id.pdf", "F");//I for testing, D for Download, F Save to a File
$temp_dir = dPgetConfig( 'root_dir' )."/files/temp";
$base_url  = dPgetConfig( 'base_url' );

// Create document body and pdf temp file

	if ($fp = fopen( "$temp_dir/temp$AppUI->user_id.pdf", 'r' )) {
		fclose( $fp );
		echo "<a href=\"$base_url/files/temp/temp$AppUI->user_id.pdf\" target=\"pdf\">";
		echo $AppUI->_( "View PDF File" );
		echo "</a>";
	} else {
		echo "Could not open file to save PDF.  ";
		if (!is_writable( $temp_dir ))
			echo "The files/temp directory is not writable.  Check your file system permissions.";
			}

	}
}
?>
