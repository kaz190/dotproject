<?php  /* FORUMS $Id: view_pdf.php 4800 2007-03-06 00:34:46Z merlinyoda $ */
if (! defined('DP_BASE_DIR')) {
	die('You should not call this file directly.');
}
$AppUI->savePlace();
$sort = dPgetParam($_REQUEST, 'sort', 'asc');
$forum_id = dPgetParam($_REQUEST, 'forum_id', 0);
$message_id = dPgetParam($_REQUEST, 'message_id', 0);
$perms =& $AppUI->acl();

// 20090907 KSen@Itsutsubashi
$project_name = dPgetParam($_REQUEST, 'project_name', 0);
$forum_name = dPgetParam($_REQUEST, 'forum_name', 0);

$forum["project_name"] = $project_name;
$forum["forum_name"] = $forum_name;

if ( ! $perms->checkModuleItem('forums', 'view', $message_id))
	$AppUI->redirect("m=public&a=access_denied");

$q  = new DBQuery;
$q->addTable('forums');
$q->addTable('forum_messages');
$q->addQuery('forum_messages.*,	contact_first_name, contact_last_name, contact_email, user_username,
		forum_moderated, visit_user');
$q->addJoin('forum_visits', 'v', "visit_user = {$AppUI->user_id} AND visit_forum = $forum_id AND visit_message = 				forum_messages.message_id");
$q->addJoin('users', 'u', 'message_author = u.user_id');
$q->addJoin('contacts', 'con', 'contact_id = user_contact');
$q->addWhere("forum_id = message_forum AND (message_id = $message_id OR message_parent = $message_id)");
if (dPgetConfig('forum_descendent_order') || dPgetParam($_REQUEST,'sort',0)) { $q->addOrder("message_date $sort"); }

$messages = $q->loadList();

$x = false;

$date = new CDate();
$pdfdata = array();
$pdfhead = array($AppUI->_('Date'), $AppUI->_('User'), $AppUI->_('Message'));

$new_messages = array();

foreach ($messages as $row) {
        // Find the parent message - the topic.
        if ($row['message_id'] == $message_id)
                $topic = $row['message_title'];
		
	$q  = new DBQuery;
	$q->addTable('forum_messages');
	$q->addTable('users');
	$q->addQuery('DISTINCT contact_email, contact_first_name, contact_last_name, user_username');
	$q->addJoin('contacts', 'con', 'contact_id = user_contact');
	$q->addWhere('users.user_id = '.$row["message_editor"]);
	$editor = $q->loadList();

	$date = intval( $row["message_date"] ) ? new CDate( $row["message_date"] ) : null;

	$pdfdata[] = array($row['message_date'],
		               $row['contact_last_name'] . ' ' . $row['contact_first_name'],
		               $row['message_title'] . '  ' . $row['message_body']);
}

$temp_dir = DP_BASE_DIR.'/files/temp';

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
//var_dump($forum);exit;//$forum_id
//Document Header - Line1
$slen = mb_strlen($AppUI->_('Project').": ". $forum['project_name']
                                           ." " 
                                           .$AppUI->_('Forum').": ".$forum['forum_name']) + $str_pad + 20;

$pdf->writeHTMLCell($header_w + $slen, $header_h,  $x, $y,$AppUI->_('Project').": ". $forum['project_name']
                                                          ." " 
                                                          .$AppUI->_('Forum').": ".$forum['forum_name']);

// (Width,Height,Text,Border,Align,Fill,Line,x,y,reset,stretch,ishtml,autopadding,maxh)

// Line break - Line2
$y = $y + $header_line_gap;

// Title 
$slen = mb_strlen($AppUI->_('Topic').": ". $topic) + $str_pad + 20;
$pdf->writeHTMLCell($header_w + $slen, $header_h, $x, $y,$AppUI->_('Topic').": ". $topic);

// Line break - Line3
$y = $y + $header_line_gap;
$y = $y + $header_line_gap;
$y = $y + $header_line_gap;


// set font
$pdf->SetFont("", "B", 12);

// Column Header 
$w = array(30, 50, 100); 
$x_init = $x;

// Color and font restoration 
$pdf->SetFont("", "B", 10); //B= bold , I = Italic , U = Underlined
$pdf->SetFillColor(224, 235, 255); 
$pdf->SetTextColor(0); 

for($i = 0; $i < count($pdfhead); $i++){ 
    $pdf->writeHTMLCell($w[$i], $header_h, $x_init, $y,$pdfhead[$i],0,0,1);
    $x_init = $x_init + $w[$i];

}

// Line break - Line4
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
    	//$pdf->writeHTMLCell($w[$i], $row_height, $x_init, $y,$rows[$i]."\n",0,0,0);//@@Fix Row Height with max
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
$pdf->Output("Report.pdf", "D");//I for testing, D for Download,

?>

