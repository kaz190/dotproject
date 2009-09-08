<?php /* PROJECTS $Id: tasklist.php 5784 2008-07-26 03:56:57Z ajdonnison $ */
if (!defined('DP_BASE_DIR')){
  die('You should not access this file directly.');
}

/**
* Generates a report of the task logs for given dates
*/

require_once $AppUI->getSystemClass('tree');

//error_reporting(E_ALL);
$do_report = dPgetParam($_POST, "do_report", 0);
$log_all = dPgetParam($_POST, 'log_all', 0);
$log_pdf = dPgetParam($_POST, 'log_pdf', 0);
$incomplete = dPgetParam($_POST, 'incomplete', 0);
$log_ignore = dPgetParam($_POST, 'log_ignore', 0);
$days = dPgetParam($_POST, 'days', 30);

$list_start_date = dPgetParam($_POST, "list_start_date", 0);
$list_end_date = dPgetParam($_POST, "list_end_date", 0);

$period = dPgetParam($_POST, "period", 0);
$period_value = dPgetParam($_POST, "pvalue", 1);
if ($period)
{
  $today = new CDate();
  $ts = $today->format(FMT_TIMESTAMP_DATE);
        if (strtok($period, " ") == $AppUI->_("Next"))
                $sign = +1;
        else //if(...)
                $sign = -1;

        $day_word = strtok(" ");
        if ($day_word == $AppUI->_("Day"))
                $days = $period_value;
        else if ($day_word == $AppUI->_("Week"))
                $days = 7*$period_value;
        else if ($day_word == $AppUI->_("Month"))
                $days = 30*$period_value;

        $start_date = new CDate($ts);
        $end_date = new CDate($ts);

        if ($sign > 0)
                $end_date->addSpan(new Date_Span("$days,0,0,0"));
        else
                $start_date->subtractSpan(new Date_Span("$days,0,0,0"));

        $do_report = 1;
        
}
else
{
// create Date objects from the datetime fields
        $start_date = intval($list_start_date) ? new CDate($list_start_date) : new CDate();
        $end_date = intval($list_end_date) ? new CDate($list_end_date) : new CDate();
}


if (!$list_start_date) {
	$start_date->subtractSpan(new Date_Span("14,0,0,0"));
}
$end_date->setTime(23, 59, 59);

?>
<script language="javascript">

var calendarField = '';

function popCalendar(field){
	calendarField = field;
	idate = eval('document.editFrm.list_' + field + '.value');
	window.open('index.php?m=public&a=calendar&dialog=1&callback=setCalendar&date=' + idate, 'calwin', 'width=250, height=220, scrollbars=no, status=no');
}

/**
 *	@param string Input date in the format YYYYMMDD
 *	@param string Formatted date
 */
function setCalendar(idate, fdate) {
	fld_date = eval('document.editFrm.list_' + calendarField);
	fld_fdate = eval('document.editFrm.' + calendarField);
	fld_date.value = idate;
	fld_fdate.value = fdate;
}

</script>

<table cellspacing="0" cellpadding="4" border="0" width="100%" class="std">

<form name="editFrm" action="index.php?m=projects&a=reports" method="post">
	<input type="hidden" name="project_id" value="<?php echo $project_id;?>" />
	<input type="hidden" name="report_type" value="<?php echo $report_type;?>" />

<tr>
        <td align="left" width ="15%" ><?php echo $AppUI->_('Default Actions'); ?>:</td>
        <td nowrap="nowrap" colspan="2">
          <?php
            $jaflg = false;
            if ($AppUI->user_locale == 'ja') {
              $jaflg = true;
			}
			if ($jaflg) {
		?><input class="text" type="field" size="2" name="pvalue" value="1" /><?php
			}
          ?>
          <input class="button" type="submit" name="period" value="<?php echo $AppUI->_('Previous Month'); ?>" />
          <input class="button" type="submit" name="period" value="<?php echo $AppUI->_('Previous Week'); ?>" />
          <input class="button" type="submit" name="period" value="<?php echo $AppUI->_('Previous Day'); ?>" />
          <input class="button" type="submit" name="period" value="<?php echo $AppUI->_('Next Day'); ?>" />
          <input class="button" type="submit" name="period" value="<?php echo $AppUI->_('Next Week'); ?>" />
          <input class="button" type="submit" name="period" value="<?php echo $AppUI->_('Next Month'); ?>" />
        <?php
        if (!$jaflg) {
		?><input class="text" type="field" size="2" name="pvalue" value="1" /> - value for the previous buttons<?php } ?></td>
<!--
        <td><input class="button" type="submit" name="do_report" value="<?php echo $AppUI->_('Previous Month'); ?>" onClick="set(-30)" /></td>
        <td><input class="button" type="submit" name="do_report" value="<?php echo $AppUI->_('Previous Week'); ?>" onClick="set(-7)" /></td>
        <td><input class="button" type="submit" name="do_report" value="<?php echo $AppUI->_('Next Week'); ?>" onClick="set(7)" /></td>
        <td><input class="button" type="submit" name="do_report" value="<?php echo $AppUI->_('Next Month'); ?>" onClick="set(30)" /></td>
-->
</tr>
<tr>

	<td align="left" nowrap="nowrap"><?php echo $AppUI->_('For period');?>:</td>
	<td nowrap="nowrap">
		<input type="hidden" name="list_start_date" value="<?php echo $start_date->format(FMT_TIMESTAMP_DATE);?>" />
		<input type="text" name="start_date" value="<?php echo $start_date->format($df);?>" class="text" disabled="disabled" />
		<a href="#" onclick="popCalendar('start_date')">
			<img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0" />
		</a> &nbsp;&nbsp; ~ &nbsp;&nbsp;
		<input type="hidden" name="list_end_date" value="<?php echo $end_date ? $end_date->format(FMT_TIMESTAMP_DATE) : '';?>" />
		<input type="text" name="end_date" value="<?php echo $end_date ? $end_date->format($df) : '';?>" class="text" disabled="disabled" />
		<a href="#" onclick="popCalendar('end_date')">
			<img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0" />
		</a>
		<input type="checkbox" name="log_all" id="log_all" <?php if ($log_all) echo 'checked="checked"' ?> />
		<label for="log_all"><?php echo $AppUI->_('Log All');?></label>
		<input type="checkbox" name="log_pdf" id="log_pdf" <?php if ($log_pdf) echo 'checked="checked"' ?> />
		<label for="log_pdf"><?php echo $AppUI->_('Make PDF');?></label>
		<input type="checkbox" name="incomplete" id="incomplete" <?php if ($incomplete) echo 'checked="checked"' ?> />
		<label for="log_pdf"><?php echo $AppUI->_('Incomplete Tasks');?></label>
	</td>

	<td align="right" width="50%" nowrap="nowrap">
		<input class="button" type="submit" name="do_report" value="<?php echo $AppUI->_('submit');?>" />
	</td>
</tr>
</form>
</table>

<?php

if ($do_report) {
	
	$q = new DBQuery;
	$q->addTable('tasks', 'a');
	$q->addTable('projects', 'b');
	$q->addQuery('a.*, b.project_name');
	$q->addWhere('a.task_project = b.project_id');
	if ($project_id != 0){ 
		$q->addWhere('task_project ='.$project_id);
	}
	if (!$log_all) {
		$q->addWhere("task_start_date >= '".$start_date->format(FMT_DATETIME_MYSQL)."'");
		$q->addWhere("task_start_date <= '".$end_date->format(FMT_DATETIME_MYSQL)."'");
	}
	if ($incomplete) {
		$q->addWhere("task_percent_complete < 100");
	}

	$obj = new CTask;
	$allowedTasks = $obj->getAllowedSQL($AppUI->user_id);
	if (count($allowedTasks)) {
		$obj->getAllowedSQL($AppUI->user_id, $q);
	}
	$q->addOrder('project_start_date', 'task_project', 'task_parent', 'task_start_date');
	$Task_List = $q->exec();

	echo "<table cellspacing=\"1\" cellpadding=\"4\" border=\"0\" class=\"tbl\">";
	if ($project_id==0) { echo '<tr><th>'.$AppUI->_('Project Name').'</th><th>'.$AppUI->_('Task Name').'</th>';} else {echo '<tr><th>'.$AppUI->_('Task Name').'</th>';}
	echo '<th width=400>'.$AppUI->_('Task Description').'</th>';
	echo '<th>'.$AppUI->_('Assigned To').'</th>';
	echo '<th>'.$AppUI->_('Task Start Date').'</th>';
	echo '<th>'.$AppUI->_('Task End Date').'</th>';
	echo '<th>'.$AppUI->_('Completion').'</th></tr>';
	
	$pdfdata = array();
	$tree = new CDpTree();

	$columns = array(	
	"$AppUI->_('Task Name')",
	"$AppUI->_('Task Description')",
	"$AppUI->_('Assigned To')",
	"$AppUI->_('Task Start Date')",
	"$AppUI->_('Task End Date')",
	"$AppUI->_('Completion')"
	);
	if ($project_id==0) { array_unshift($columns, "<b>".$AppUI->_('Project Name')."</b>");}		

	while ($Tasks = db_fetch_assoc($Task_List)){
		$Tasks['start_date'] = intval($Tasks['task_start_date']) ? new CDate($Tasks['task_start_date']) : ' ';
		$Tasks['end_date'] = intval($Tasks['task_end_date']) ? new CDate($Tasks['task_end_date']) : ' ';
		$task_id = $Tasks['task_id'];
		
		$q = new DBQuery;
		$q->addQuery('CONCAT_WS(\' \', c.contact_first_name, c.contact_last_name) as contact_name');
		$q->addTable('user_tasks', 'ut');
		$q->leftJoin('users', 'u', 'u.user_id = ut.user_id');
		$q->leftJoin('contacts', 'c', 'c.contact_id = u.user_contact');
		$q->addWhere('ut.task_id = '.$task_id);
		$sql_user = $q->loadColumn();
		$Tasks['users'] = implode(', ', $sql_user);
		$tree->add($Tasks['task_parent'], $task_id, $Tasks);
		unset($Tasks);
	}

	// Now show the tasks as HTML
	$tree->display('show_task_as_html');

	echo "</table>";
if ($log_pdf) {
	// make the PDF file
		$pdfdata = array();
		$tree->display('collate_pdf_task');
		$q = new DBQuery;
		$q->addTable('projects');
		$q->addQuery('project_name');
		$q->addWhere('project_id='.(int)$project_id);
		$pname = $q->loadResult();

		$temp_dir = DP_BASE_DIR.'/files/temp';

		$date = new CDate();
		$columns = array(	
			$AppUI->_('Task Name'),
			$AppUI->_('Task Description'),
			$AppUI->_('Assigned To'),
			$AppUI->_('Task Start Date'),
			$AppUI->_('Task End Date'),
			$AppUI->_('Completion')
		);
		$title = null;


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
$pdf->SetTitle($AppUI->_('Project Task Report'));
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
$pdf->SetFont("arialunicid0","B", 12); //B= bold , I = Italic , U = Underlined

//Document Header - Line1
$slen = mb_strlen(dPgetConfig( 'company_name' )."\n") + $str_pad;
$pdf->writeHTMLCell($header_w + $slen, $header_h, $x, $y,dPgetConfig( 'company_name' ));
$pdf->writeHTMLCell($header_w, $header_h,  $x + $slen, $y,date("Y/m/d"));

// (Width,Height,Text,Border,Align,Fill,Line,x,y,reset,stretch,ishtml,autopadding,maxh)

// Line break - Line2
$y = $y + $header_line_gap;

// set font
$pdf->SetFont("", "B", 12);

// Title 
$slen = mb_strlen($AppUI->_('Project Task Report')) + $str_pad;
$pdf->writeHTMLCell($header_w + $slen, $header_h, $x, $y,$AppUI->_('Project Task Report'));

// Line break - Line3
$y = $y + $header_line_gap;

	if ($log_all) {
		$slen = mb_strlen($AppUI->_('All task entries')) + $str_pad;
		$pdf->writeHTMLCell($header_w + $slen, $header_h, $x, $y,$AppUI->_('All task entries'));
	} else {		
			if( $end_date != ' ') {
				$slen = mb_strlen($AppUI->_('Task entries from')."  ".$start_date->format($df).' ~ '.$end_date->format($df)) + $str_pad + 20;
				$pdf->writeHTMLCell($header_w + $slen, $header_h, $x, $y,$AppUI->_('Task entries from')
                                                                         ."  "
                                                                         .$start_date->format($df)
                                                                         .' ~ '
                                                                         .$end_date->format($df));
            } else {
					$slen = mb_strlen($AppUI->_('Task entries from')."  ".$start_date->format($df)) + $str_pad;
					$pdf->writeHTMLCell($header_w + $slen, $header_h, $x, $y,$AppUI->_('Task entries from')."  ".$start_date->format($df));
			}
		}

// Line break - Line4
$y = $y + $header_line_gap;
$y = $y + $header_line_gap;
$y = $y + $header_line_gap;

// Column Header 

// set font
$pdf->SetFont("", "B", 10); //B= bold , I = Italic , U = Underlined

$w = array(50, 90, 50, 30,30,20);  
$x_init=$x;
$pdf->SetFillColor(224, 235, 255);
for($i = 0; $i < count($columns); $i++){ 
    $pdf->writeHTMLCell($w[$i], $header_h, $x_init, $y,$columns[$i]."\n",0,0,1);
    $x_init = $x_init + $w[$i];

}
 
// Line break - Line5
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
    	// $pdf->writeHTMLCell($w[$i], $row_height * $linecount, $x_init, $y,$rows[$i]."\n",0,0,0);
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
//ob_end_clean();
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
</table>
<?php

function show_task_as_html($depth, $task)
{
	global $project_id, $df;

	$str =  "<tr>";
	if ($project_id==0) {$str .= "<td>".$task['project_name']."</td>";}
	$str .= "<td><a href='?m=tasks&a=view&task_id=".$task['task_id']. "'>";
	for ($i = 1; $i < $depth; $i++) {
		$str .= '&nbsp;&nbsp;';
	}
	$str .= $task['task_name']."</a></td>";
	$str .= "<td>".$task['task_description']."</td>";
	$str .= "<td>".$task['users']."</td>";
	$str .= "<td>";
	($task['start_date'] != ' ') ? $str .= $task['start_date']->format($df)."</td>" : $str .= ' '."</td>";			
	$str .= "<td>";		
	($task['end_date'] != ' ') ? $str .= $task['end_date']->format($df)."</td>" : $str .= ' '."</td>";
	$str .= "<td align=\"center\">".$task['task_percent_complete']."%</td>";
	$str .= "</tr>";
	echo $str;
}

/**
 * Need to use safe_utf8_decode because eZPDF doesn't understand UTF8, only Latin1
 */
function collate_pdf_task($depth, $task)
{
	global $project_id, $pdfdata, $df;

	$spacer = '';
	for ($i = 1; $i < $depth; $i++) {
		$spacer .= '  ';
	}

	$data = array();
	if ($project_id==0) {	
	//	$data[] = $task['project_name'];
	}
	$data[] = $spacer . $task['task_name'];
	$data[] = $task['task_description'];
	$data[] = $task['users'];
	$data[] = safe_utf8_decode(($task['start_date'] != ' ') ? $task['start_date']->format($df) : ' ');
	$data[] = safe_utf8_decode(($task['end_date'] != ' ') ? $task['end_date']->format($df) : ' ');
	$data[] = $task['task_percent_complete']."%";
	$pdfdata[] = $data;
	unset($data);
}
?>
