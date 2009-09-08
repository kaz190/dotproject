<?php
if (!defined('DP_BASE_DIR')){
	die('You should not access this file directly.');
}

require_once DP_BASE_DIR . '/modules/ticketsmith/config.inc.php';
require_once $AppUI->getSystemClass( 'query');

//Ticket Type Associative Mapping 
$ticket_type = array_combine(dPgetSysVal( 'TicketStatus'),$CONFIG["type_names"]);
$ticket_type = array_merge($ticket_type,array("Processing"=>$AppUI->_("Processing")));

$type = dPgetParam($_GET, 'type', '');
$column = dPgetParam($_GET, 'column', 'timestamp');
$direction = dPgetParam($_GET, 'direction', 'DESC');
$q = new DBQuery;
$q->addQuery(array(
	'ticket',
	'author',
	'subject',
	'timestamp',
	'type',
	'assignment',
	'contact_first_name',
	'contact_last_name',
	'activity',
	'priority'
));

$q->addTable('tickets', 'a');
$q->leftJoin('users', 'b', 'a.assignment = b.user_id');
$q->leftJoin('contacts', 'c', 'b.user_id = c.contact_id');
if ($type == 'My') {
	$q->addWhere("type = 'Open'");
	$q->addWhere("(assignment = '$AppUI->user_id' OR assignment = '0')");
} else if ($type != 'All') {
	$q->addWhere("type = '$type'");
}
$q->addWhere("parent = '0'");
$q->addOrder(urlencode($column) . " " . $direction);

$ticketlist = $q->loadHashList('ticket');
if ($err = db_error()) {
	$AppUI->setMsg($err, UI_MSG_ERR);
	$AppUI->redirect();
}

$df = $AppUI->getPref('SHDATEFORMAT');

//@@check
$title = $type . "Tickets";

$pdfdata = array();

//PDF Column Declaration
$columns = array($AppUI->_('Author'),
                 $AppUI->_('Subject'),
                 $AppUI->_('Date'),
                 $AppUI->_('Followup'),
                 $AppUI->_('Status'),
	             $AppUI->_('Priority'),
	             $AppUI->_('Owner'),
);
//var_dump($ticketlist);exit;
foreach ($ticketlist as $ticket) {
	$row =& $pdfdata[];
	$row[] = $ticket['author'];
	$row[] = $ticket['subject'];
	$row[] = date($CONFIG['date_format'], $ticket['timestamp']);

	if ($ticket['activity'])
		$row[] = date($CONFIG['date_format'], $ticket['activity']);
	else
		$row[] = '-';
         //Itsutsubashi-K.Sen-20090814
        //$row[] = $ticket['type'];
	    $row[] = $ticket_type[$ticket['type']];
    	$row[] = $CONFIG['priority_names'][$ticket['priority']];
	    $row[] = $ticket['contact_first_name'] . ' ' . $ticket['contact_last_name'];
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
$pdf->SetTitle($AppUI->_('Project Task Report'));
$pdf->SetSubject($AppUI->_('Report as PDF'));
$pdf->SetKeywords("TCPDF, PDF");

// remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

//set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, 10, PDF_MARGIN_RIGHT);

//set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM-10);

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
$slen = mb_strlen($AppUI->_("$type Tickets")." " .$AppUI->_("Report")) + $str_pad +10;
$pdf->writeHTMLCell($header_w + $slen, $header_h, $x, $y,$AppUI->_("$type Tickets")." " .$AppUI->_("Report"));

// Line break - Line4
$y = $y + $header_line_gap;
$y = $y + $header_line_gap;
$y = $y + $header_line_gap;

// Column Header 

// set font
$pdf->SetFont("", "B", 10); //B= bold , I = Italic , U = Underlined

$w = array(60, 70, 30, 35, 20,20,30); 
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
		$pdf->MultiCell($w[$i], ($header_h * $linecount)+ $header_h, trim($rows[$i])."\n", 0, 'L', 0, 0, $x_init, $y, true);
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
$pdf->Output("Ticket.pdf", "I");

//============================================================+
// END OF FILE                                                 
//============================================================+

?>










