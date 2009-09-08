<?php /* PROJECTS $Id: overall.php 5784 2008-07-26 03:56:57Z ajdonnison $ */
if (!defined('DP_BASE_DIR')){
  die('You should not access this file directly.');
}

/**
* Generates a report of the task logs for given dates
*/
$do_report = dPgetParam($_POST, "do_report", 0);
$log_pdf = dPgetParam($_POST, 'log_pdf', 0);

$log_start_date = dPgetParam($_POST, "log_start_date", 0);
$log_end_date = dPgetParam($_POST, "log_end_date", 0);
$log_all = dPgetParam($_POST, 'log_all', 0);

// create Date objects from the datetime fields
$start_date = intval($log_start_date) ? new CDate($log_start_date) : new CDate();
$end_date = intval($log_end_date) ? new CDate($log_end_date) : new CDate();

if (!$log_start_date) {
	$start_date->subtractSpan(new Date_Span("14,0,0,0"));
}
$end_date->setTime(23, 59, 59);

$fullaccess = ($AppUI->user_type == 1);
?>
<script language="javascript">
var calendarField = '';

function popCalendar(field){
	calendarField = field;
	idate = eval('document.editFrm.log_' + field + '.value');
	window.open('index.php?m=public&a=calendar&dialog=1&callback=setCalendar&date=' + idate, 'calwin', 'top=250,left=250,width=250, height=220, scrollbars=no, status=no');
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

<form name="editFrm" action="index.php?m=projects&a=reports" method="post">
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

	<td nowrap="nowrap">
		<input type="checkbox" name="log_all" id="log_all" <?php if ($log_all) echo 'checked="checked"' ?> />
		<label for="log_all"><?php echo $AppUI->_('Log All');?></label>
	</td>
	<td nowrap="nowrap">
		<input type="checkbox" name="log_pdf" id="log_pdf" <?php if ($log_pdf) echo 'checked="checked"' ?> />
		<label for="log_pdf"><?php echo $AppUI->_('Make PDF');?></label>
	</td>

	<td align="right" width="50%" nowrap="nowrap">
		<input class="button" type="submit" name="do_report" value="<?php echo $AppUI->_('submit');?>" />
	</td>
</tr>
</form>
</table>

<?php
$allpdfdata = array();
function showcompany($company, $restricted = false)
{
	global $AppUI, $allpdfdata, $log_start_date, $log_end_date, $log_all;
       /* $sql="
        SELECT
                billingcode_id,
                billingcode_name,
                billingcode_value
        FROM billingcode
        WHERE company_id=$company
        ORDER BY billingcode_name ASC
        ";
                                                                                                                  
        $company_billingcodes=NULL;
        $ptrc=db_exec($sql);
        $nums=db_num_rows($ptrc);
        echo db_error();
                                                                                                                         
        for ($x=0; $x < $nums; $x++) {
                $row=db_fetch_assoc($ptrc);
                $company_billingcodes[$row['billingcode_id']]=$row['billingcode_name'];
        }
*/
	$sql = "SELECT project_id, project_name
		FROM projects
		WHERE project_company = $company";

	$projects = db_loadHashList($sql);
  
	$sql = "SELECT company_name
		FROM companies
		WHERE company_id = $company";
	$company_name = db_loadResult($sql);                                                                                                                       

        $table = '<h2>Company: ' . $company_name . '</h2>
        <table cellspacing="1" cellpadding="4" border="0" class="tbl">';
	$project_row = '
        <tr>
                <th>' . $AppUI->_('Project') . '</th>';
                
		$pdfth[] = $AppUI->_('Project');
/*		if (isset($company_billingcodes))
	                foreach ($company_billingcodes as $code)
			{
        	                $project_row .= '<th>' . $code . ' ' . $AppUI->_('Hours') . '</th>';
				$pdfth[] = $code;
			}
  */              
        $project_row .= '<th>' . $AppUI->_('Total') . '</th></tr>';
	$pdfth[] = $AppUI->_('Total');
	$pdfdata[] = $pdfth;
        
        $hours = 0.0;
	$table .= $project_row;

        foreach ($projects as $project => $name)
        {
		$pdfproject = array();
		$pdfproject[] = $name;
		$project_hours = 0;
		$project_row = "<tr><td>$name</td>";
		$sql = "SELECT task_log_costcode, sum(task_log_hours) as hours
			FROM projects, tasks, task_log
			WHERE project_id = $project";
		if ($log_start_date != 0 && !$log_all)
			$sql .= " AND task_log_date >= $log_start_date";
		if ($log_end_date != 0 && !$log_all)
			$sql .= " AND task_log_date <= $log_end_date";
		if ($restricted)
			$sql .= " AND task_log_creator = '" . $AppUI->user_id . "'";
			
		$sql .= " AND project_id = task_project
			AND task_id = task_log_task
			GROUP BY project_id"; //task_log_costcode";

		$task_logs = db_loadHashList($sql);

/*		if (isset($company_billingcodes))
		foreach($company_billingcodes as $code => $name)
		{
			if (isset($task_logs[$code]))
			{
				$value = sprintf("%.2f", $task_logs[$code]);
				$project_row .= '<td>' . $value . '</td>';
				$project_hours += $task_logs[$code];
				$pdfproject[] = $value;
			}
			else
			{
				$project_row .= '<td>&nbsp;</td>';
				$pdfproject[] = 0;
			}
		}
*/
                foreach($task_logs as $task_log)
                        $project_hours += $task_log;
		$project_row .= '<td>' . round($project_hours, 2) . '</td></tr>';
		$pdfproject[]=round($project_hours, 2);
		$hours += $project_hours;
		if ($project_hours > 0)
		{
			$table .= $project_row;
			$pdfdata[] = $pdfproject;
		}
        }
	if ($hours > 0)
	{
		$allpdfdata[$company_name] = $pdfdata;
	
		echo $table;
		echo '<tr><td>Total</td><td>' . round($hours, 2) . '</td></tr></table>';
	}


	return $hours;
}

if ($do_report) {

	$total = 0;

if ($fullaccess)
	$sql = "SELECT company_id FROM companies";
else
	$sql = "SELECT company_id FROM companies WHERE company_owner='" . $AppUI->user_id . "'";

$companies = db_loadColumn($sql);

if (!empty($companies))	
	foreach ($companies as $company)
		$total += showcompany($company);
else
{
	$sql = "SELECT company_id FROM companies";
	foreach(db_loadColumn($sql) as $company)
		$total += showcompany($company, true);
}

	

echo '<h2>' . $AppUI->_('Total Hours') . ":"; 
printf("%.2f", $total);
echo '</h2>';


if ($log_pdf) {
	// make the PDF file

	$temp_dir = DP_BASE_DIR.'/files/temp';
		

	foreach($allpdfdata as $company => $data)
	{
		$title = $company;

		//$pdf->ezTable($data, NULL, $title, $options);
	}

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
$pdf->SetTitle($AppUI->_('Overall Report'));
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
$pdf->SetFont("arialunicid0","B",12);

//Document Header - Line1
$slen = mb_strlen(dPgetConfig( 'company_name' )."\n") + $str_pad;
$pdf->writeHTMLCell($header_w + $slen, $header_h, $x, $y,dPgetConfig( 'company_name' ));
//$pdf->writeHTMLCell($header_w, $header_h,  $x + $slen, $y,date("Y/m/d"));

// (Width,Height,Text,Border,Align,Fill,Line,x,y,reset,stretch,ishtml,autopadding,maxh)

// Line break - Line2 
$y = $y + $header_line_gap;

$slen = strlen($pname."\n") + $str_pad;
$pdf->writeHTMLCell($header_w + $slen, $header_h, $x, $y,$pname);

// Line break - Line3
$y = $y + $header_line_gap;

		if ($log_all)
		{
			$date = new CDate();
			$slen = mb_strlen($AppUI->_('All hours as of')) + $str_pad + 20;
			$pdf->writeHTMLCell($header_w + $slen, $header_h, $x, $y,$AppUI->_('All hours as of'). " " .$date->format($df) );

		}
		else
		{
			$sdate = new CDate($log_start_date);
			$edate = new CDate($log_end_date);
			$slen = mb_strlen($AppUI->_('All hours as of')." " .$sdate->format($df) . " " .$AppUI->_('~') ." ". $edate->format($df)) + $str_pad +20;
			$pdf->writeHTMLCell($header_w + $slen+20, $header_h, $x, $y,$AppUI->_('All hours as of')
                                                                        ." " 
                                                                        .$sdate->format($df) 
                                                                        . " " 
                                                                        .$AppUI->_('~') 
                                                                        ." "
                                                                        . $edate->format($df));
		}

// Line break - Line4
$y = $y + $header_line_gap;
$y = $y + $header_line_gap;

// Title 
$slen = mb_strlen($AppUI->_('Overall Report')) + $str_pad;
$pdf->writeHTMLCell($header_w + $slen, $header_h, $x, $y,$AppUI->_('Overall Report'));

// Line break - Line5
$y = $y + $header_line_gap;
$y = $y + $header_line_gap;
$y = $y + $header_line_gap;

// Column Header 

// set font
$pdf->SetFont("", "", 10); //B= bold , I = Italic , U = Underlined

$w = array(50, 60, 60, 40,40,30);  
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
$pdf->SetFillColor(224, 235, 255); 
$pdf->SetTextColor(0); 

// Data 
$fill = 0; 
$x_init = $x;$pdf->SetXY($x_init,$y);
$max_rows = 1; 
$lc = array();

foreach($allpdfdata as $rows) {

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
