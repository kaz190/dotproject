<?php
if (!defined('DP_BASE_DIR')){
	die('You should not access this file directly.');
}

// Output the PDF
// make the PDF file
if ($project_id != 0) {
	$sql = "SELECT project_name FROM projects WHERE project_id=$project_id";
	$pname = db_loadResult( $sql );
} else {
	$pname = $AppUI->_('All Projects');
}	
if ($err = db_error()) {
	$AppUI->setMsg($err, UI_MSG_ERROR);
	$AppUI->redirect();
}

$date = new CDate();
$next_week = new CDate($date);
$next_week->addSpan(new Date_Span(array(7,0,0,0)));

$hasResources = $AppUI->isActiveModule('resources');
$perms =& $AppUI->acl();
if ($hasResources)
	$hasResources = $perms->checkModule('resources', 'view');
// Build the data to go into the table.
$pdfdata = array();
$columns = array();
$columns[] = $AppUI->_('Task Name') ;
$columns[] = $AppUI->_('Owner') ;
$columns[] = $AppUI->_('Assigned Users') ;
if ($hasResources)
	$columns[] = $AppUI->_('Assigned Resources') ;
$columns[] = $AppUI->_('Finish Date') ;

// Grab the completed items in the last week
$q = new DBQuery;
$q->addQuery('a.*');
$q->addQuery('b.user_username');
$q->addTable('tasks', 'a');
$q->leftJoin('users', 'b', 'a.task_owner = b.user_id');
$q->addWhere('task_percent_complete < 100');
if ($project_id != 0)
	$q->addWhere('task_project = ' . $project_id);
$q->addWhere("task_end_date <  '" . $date->format(FMT_DATETIME_MYSQL) . "'");
$tasks = $q->loadHashList('task_id');

if ($err = db_error()) {
	$AppUI->setMsg($err, UI_MSG_ERROR);
	$AppUI->redirect();
}
// Now grab the resources allocated to the tasks.
$task_list = array_keys($tasks);
$assigned_users = array();
// Build the array
foreach ($task_list as $tid)
	$assigned_users[$tid] = array();

if (count($tasks)) {
	$q->clear();
	$q->addQuery('a.task_id, a.perc_assignment, b.*, c.*');
	$q->addTable('user_tasks', 'a');
	$q->leftJoin('users', 'b', 'a.user_id = b.user_id');
	$q->leftJoin('contacts', 'c', 'b.user_contact = c.contact_id');
	$q->addWhere('a.task_id in (' . implode(',', $task_list) . ')');
	$res = $q->exec();
	if (! $res) {
		$AppUI->setMsg(db_error(), UI_MSG_ERROR);
		$q->clear();
		$AppUI->redirect();
	}
	while ($row = db_fetch_assoc($res)) {
		$assigned_users[$row['task_id']][$row['user_id']] 
		= ("$row[contact_first_name] $row[contact_last_name] [$row[perc_assignment]%]");
	}
	$q->clear();
}

$resources = array();
if ($hasResources && count($tasks)) {
	foreach ($task_list as $tid) {
		$resources[$tid] = array();
	}
	$q->clear();
	$q->addQuery('a.*, b.resource_name');
	$q->addTable('resource_tasks', 'a');
	$q->leftJoin('resources', 'b', 'a.resource_id = b.resource_id');
	$q->addWhere('a.task_id in (' . implode(',', $task_list) . ')');
	$res = $q->exec();
	if (! $res) {
		$AppUI->setMsg(db_error(), UI_MSG_ERROR);
		$q->clear();
		$AppUI->redirect();
	}
	while ($row = db_fetch_assoc($res)) {
		$resources[$row['task_id']][$row['resource_id']] 
		= $row['resource_name'] . " [" . $row['percent_allocated'] . "%]";
	}
	$q->clear();
}

// Build the data columns
foreach ($tasks as $task_id => $detail) {
	$row =& $pdfdata[];
	$row[] = $detail['task_name'];
	$row[] = $detail['user_username'];
	$row[] = implode("\n",$assigned_users[$task_id]);
	if ($hasResources)
		$row[] = implode("\n", $resources[$task_id]);
	$end_date = new CDate($detail['task_end_date']);
	$row[] = $end_date->format($df);
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
$pdf->SetTitle($AppUI->_('Project Overdue Task Report'));
$pdf->SetSubject($AppUI->_('Report as PDF'));
$pdf->SetKeywords("TCPDF, PDF");

// remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

//set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, 5, PDF_MARGIN_RIGHT);

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
$slen = mb_strlen($AppUI->_('Project Overdue Task Report')) + $str_pad;
$pdf->writeHTMLCell($header_w + $slen, $header_h, $x, $y,$AppUI->_('Project Overdue Task Report'));


// Line break - Line3
$y = $y + $header_line_gap;

$slen = mb_strlen($date->format( $df )." ".$AppUI->_('Tasks Due to be Completed By')) + $str_pad +20 ;
$pdf->writeHTMLCell($header_w + $slen, $header_h, $x, $y,$date->format( $df )." ".$AppUI->_('Tasks Due to be Completed By'));


// Line break - Line4 
$y = $y + $header_line_gap;

$slen = strlen($pname."\n") + $str_pad;
$pdf->writeHTMLCell($header_w + $slen, $header_h, $x, $y,$pname);


// Line break - Line5
$y = $y + $header_line_gap;
$y = $y + $header_line_gap;
$y = $y + $header_line_gap;

// Column Header 
$pdf->SetFont("", "", 10); //B= bold , I = Italic , U = Underlined
$w = array(90, 50, 80, 60,30); 
$x_init=$x;

// Color and font restoration 
$pdf->SetFillColor(224, 235, 255); 
$pdf->SetTextColor(0); 

for($i = 0; $i < count($columns); $i++){ 
    $pdf->writeHTMLCell($w[$i], $header_h, $x_init, $y,$columns[$i]."\n",0,0,1);
    $x_init = $x_init + $w[$i];

}
 
// Line break - Line6
$row_height = round($pdf->getLastH());
$y = $y + $row_height;

// Color and font restoration 
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
  ob_end_clean();
//Close and output PDF document
$pdf->Output("Report.pdf", "I");//I for testing, D for Download


?>




