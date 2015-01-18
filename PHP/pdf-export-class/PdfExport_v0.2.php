<?php
/*
 TUTORIAL
 * http://www.id.uzh.ch/cl/zinfo/fpdf/tutorial/tuto4.htm
 * http://www.id.uzh.ch/cl/zinfo/fpdf/doc/index.htm
 * */

require ($serverDocumentRoot . '/my3form_europe/optimizer/tfpdf/tfpdf.php');
require_once ($serverDocumentRoot . '/my3form_europe/optimizer/PDFMerger/PDFMerger.php');

class FPDF extends tFPDF {

	// Current column
	var $col = 0;
	// Ordinate of column start
	var $y0;

	function Header() {
		// Page header
		global $title;

		//**Romain, 23Oct2012, rebuild the report name, the old one needed fabrication to be in
		$title = "Reports - Project: " . $_SESSION['projectname'] . " (ref. " . $_SESSION['quotenumber'] . ")";

		$this -> SetFont('Arial', 'B', 15);
		$w = $this -> GetStringWidth($title) + 6;

		$this -> SetDrawColor(199, 220, 255);
		$this -> SetFillColor(255, 255, 255);
		$this -> SetLineWidth(0.5);

		$this -> Cell(0, 9, $title, 1, 1, 'C', true);

		$this -> Ln(3);
		// Save ordinate
		$this -> y0 = $this -> GetY();

	}

	function Footer() {

		// Page footer
		$this -> SetY(-15);
		$this -> SetFont('Arial', 'I', 8);
		$this -> SetTextColor(128);
		$alias = "{nb}";
		$this -> Cell(0, 10, 'Page ' . $this -> PageNo()/*.'/'.$alias*/, 0, 0, 'C');
		//FIXME alias doesn't work with utf8

	}

	function SetCol($col) {
		// Set position at a given column
		$this -> col = $col;
		$x = 10 + $col * 65;
		$this -> SetLeftMargin($x);
		$this -> SetX($x);

	}

	function AcceptPageBreak() {
		// Method accepting or not automatic page break
		if ($this -> col < 2) {
			// Go to next column
			$this -> SetCol($this -> col + 1);
			// Set ordinate to top
			$this -> SetY($this -> y0);
			// Keep on page
			return false;
		} else {
			// Go back to first column
			$this -> SetCol(0);
			// Page break
			return true;
		}
	}

	function ChapterTitle($num, $label) {
		// Title
		$font = "DejaVu";
		$this -> SetFont($font, '', 12);
		$this -> SetFillColor(138, 181, 255);
		//(200, 220, 255);
		$this -> Cell(0, 6, "$label", 0, 1, 'L', true);
		$this -> Ln(4);
		// Save ordinate
		$this -> y0 = $this -> GetY();
	}

	function ChapterBody($file) {
		// Read text file
		$txt = $file;
		// Font
		$this -> SetFont('Times', '', 12);
		// Output text in a 6 cm width column
		$this -> MultiCell(60, 5, $txt);
		$this -> Ln();

		// Go back to first column
		$this -> SetCol(0);
	}

	function PrintChapter($num, $title, $file) {
		// Add chapter
		$this -> AddPage('P', 'A4');
		$this -> ChapterTitle($num, $title);
		//$this -> ChapterBody($file);
	}

	function WriteHTML($html) {
		// HTML parser
		$html = str_replace("\n", ' ', $html);
		$a = preg_split('/<(.*)>/U', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
		foreach ($a as $i => $e) {
			if ($i % 2 == 0) {
				// Text
				if ($this -> HREF)
					$this -> PutLink($this -> HREF, $e);
				else
					$this -> Write(5, $e);
			} else {
				// Tag
				if ($e[0] == '/')
					$this -> CloseTag(strtoupper(substr($e, 1)));
				else {
					// Extract attributes
					$a2 = explode(' ', $e);
					$tag = strtoupper(array_shift($a2));
					$attr = array();
					foreach ($a2 as $v) {
						if (preg_match('/([^=]*)=["\']?([^"\']*)/', $v, $a3))
							$attr[strtoupper($a3[1])] = $a3[2];
					}
					$this -> OpenTag($tag, $attr);
				}
			}
		}
	}

	function OpenTag($tag, $attr) {
		// Opening tag
		if ($tag == 'B' || $tag == 'I' || $tag == 'U')
			$this -> SetStyle($tag, true);
		if ($tag == 'A')
			$this -> HREF = $attr['HREF'];
		if ($tag == 'BR')
			$this -> Ln(3);
	}

	function CloseTag($tag) {
		// Closing tag
		if ($tag == 'B' || $tag == 'I' || $tag == 'U')
			$this -> SetStyle($tag, false);
		if ($tag == 'A')
			$this -> HREF = '';
	}

	function SetStyle($tag, $enable) {
		// Modify style and select corresponding font
		$this -> $tag += ($enable ? 1 : -1);
		$style = '';
		foreach (array('B', 'I', 'U') as $s) {
			if ($this -> $s > 0)
				$style .= $s;
		}
		$this -> SetFont('', $style);
	}

	function PutLink($URL, $txt) {
		// Put a hyperlink
		$this -> SetTextColor(0, 0, 255);
		$this -> SetStyle('U', true);
		$this -> Write(5, $txt, $URL);
		$this -> SetStyle('U', false);
		$this -> SetTextColor(0);
	}

	function productTable($summary, $column3, $column2, $quoteid, $text) {

		$this -> SetFont('', 'B');
		$this -> SetDrawColor(199, 220, 255);
		$this -> SetLineWidth(0.5);

		$this -> SetTextColor(255, 255, 255);
		$this -> SetFillColor(112, 112, 112);
		//Make gray per romain

		//Build the top row
		$this -> Cell(42, 7, $text['rep_quoten'] . " $quoteid", 1, 0, 'C', 1);
		$this -> Ln();

		//$w=array(14,14,14);//,14
		$w = array(21, 21);

		//build the summary header row (3 columns)
		for ($i = 0; $i < sizeof($summary); $i++) {
			$this -> Cell($w[$i], 5, $summary[$i], 1, 0, 'C', 1);
		}

		$this -> Ln();
		$this -> SetFillColor(224, 235, 255);
		$this -> SetTextColor(0, 0, 0);
		$this -> SetFont('');
		$fill = 0;

		//populate the summary header data (3 columns)
		$item = array();
		foreach ($column3 as $item) {
			$this -> Cell($w[0], 5, $item[0], 'LR', 0, 'L', $fill);
			// line 73
			$this -> Cell($w[1], 5, $item[1], 'LR', 0, 'L', $fill);
			// line 74

			$this -> Ln();
			$fill = !$fill;
		}

		$this -> Cell(array_sum($w), 0, '', 'T');
		$this -> Ln();

		$x = $this -> GetX();
		$y = $this -> GetY();

		$width = 21;
		$height = 4;

		$this -> SetY($y);
		//set pointer back to previous values
		$this -> SetX($x);

		$this -> SetWidths(array(21, 21));
		srand(microtime() * 1000000);
		foreach ($column2 as $item => $value) {
			$count = $count1 = $count2 = 0;

			$this -> SetFont('Helvetica', 'B', 7.5);
			$item1 = utf8_decode($value[0]);
			$item2 = utf8_decode($value[1]);
			$count1 = $this -> NbLines($width, $item1);
			$this -> SetFont('Helvetica', '', 7.5);
			$count2 = $this -> NbLines($width, $item2);

			$count = ($count1 >= $count2 ? $count1 : $count2);

			//now process normally, set font back to utf8 truetype font to display all languages correctly
			$this -> SetFont('Dejavu', '', 7);
			$this -> Row(array($value[0], $value[1]), $count);
		}

		$this -> Cell(array_sum($w), 0, '', 'T');
	}

	function summaryTable($data, $text) {

		$this -> SetDrawColor(199, 220, 255);

		$w = array(42);

		$this -> SetFillColor(224, 235, 255);
		$this -> SetTextColor(0, 0, 0);
		$this -> SetFont('');
		$fill = 1;

		$this -> Cell($w[0], 0, '', 'T');
		$this -> Ln();

		$x = $this -> GetX();
		$y = $this -> GetY();

		$width = $w[0];
		$height = 5;

		$this -> SetY($y);
		//set pointer back to previous values
		$this -> SetX($x);

		$item = array();
		foreach ($data as $item) {

			if ($fill == 1) {
				$this -> SetFont('', 'B');
			} else {
				$this -> SetFont('');
			}

			$this -> SetY($y);
			$this -> SetX($x);

			$this -> MultiCell($width, $height, "" . $item[0] . "", 'LR', 'L', $fill);

			$x = $this -> GetX() - $width;
			$y = $this -> GetY() - $height;

			$y += $height;
			$x += $width;
			$this -> SetY($y);
			$this -> SetX($x);

			$fill = !$fill;
		}

		$this -> Cell($width, 0, '', 'T');
	}

	var $widths;
	var $aligns;

	function SetWidths($w) {
		//Set the array of column widths
		$this -> widths = $w;
	}

	function SetAligns($a) {
		//Set the array of column alignments
		$this -> aligns = $a;
	}

	function Row($data, $in_nb = 0, $fill = array()) {
		//Calculate the height of the row
		$nb = $in_nb;
		for ($i = 0; $i < count($data); $i++)
			$nb = max($nb, $this -> NbLines($this -> widths[$i], $data[$i]));
		$h = 4 * $nb;
		//Issue a page break first if needed
		$this -> CheckPageBreak($h);

		//check for fill
		$usefill = 0;
		if (!empty($fill)) {
			$this -> SetFillColor($fill[0], $fill[1], $fill[2]);
			//fill color
			$usefill = 1;
		}

		//Draw the cells of the row
		for ($i = 0; $i < count($data); $i++) {
			if ($i == 0) {
				$this -> SetFont('', 'B');
			} else {
				$this -> SetFont('');
			}

			$w = $this -> widths[$i];
			$a = isset($this -> aligns[$i]) ? $this -> aligns[$i] : 'L';
			//
			//Save the current position
			$x = $this -> GetX();
			$y = $this -> GetY();
			//Draw the border
			$this -> Rect($x, $y, $w, $h);
			//Print the text
			$this -> MultiCell($w, 4, $data[$i], 0, $a, $usefill);
			//$this->MultiCell($width, $height, "".$item[1]."", 'LR', 'L', $fill);
			//Put the position to the right of the cell
			$this -> SetXY($x + $w, $y);
		}
		//Go to the next line
		$this -> Ln($h);
	}

	function CheckPageBreak($h) {
		//If the height h would cause an overflow, add a new page immediately
		if ($this -> GetY() + $h > $this -> PageBreakTrigger)
			$this -> AddPage($this -> CurOrientation);
	}

	function NbLines($w, $txt) {
		//Computes the number of lines a MultiCell of width w will take
		$cw = &$this -> CurrentFont['cw'];
		if ($w == 0)
			$w = $this -> w - $this -> rMargin - $this -> x;
		$wmax = ($w - 2 * $this -> cMargin) * 1000 / $this -> FontSize;
		$s = str_replace("\r", '', $txt);
		$nb = mb_strlen($s);
		if ($nb > 0 and $s[$nb - 1] == "\n")
			$nb--;
		$sep = -1;
		$i = 0;
		$j = 0;
		$l = 0;
		$nl = 1;
		while ($i < $nb) {
			$c = $s[$i];
			if ($c == "\n") {
				$i++;
				$sep = -1;
				$j = $i;
				$l = 0;
				$nl++;
				continue;
			}
			if ($c == ' ')
				$sep = $i;
			$l += $cw[$c];
			if ($l > $wmax) {
				if ($sep == -1) {
					if ($i == $j)
						$i++;
				} else
					$i = $sep + 1;
				$sep = -1;
				$j = $i;
				$l = 0;
				$nl++;
			} else
				$i++;
		}
		return $nl;
	}

}

//get language so translations can be added
$q_pq = db_query("SELECT *, qq.id as qid, qp.id as pid FROM qt_projects qp INNER JOIN qt_quotes qq ON qq.project_id = qp.id WHERE qq.id='$_SESSION[quote]';");
$r_pq = mysql_fetch_assoc($q_pq);

$q_reptext = db_query("SELECT * FROM tmpl_report WHERE lang_id='$r_pq[language_id]'");
$r_reptext = mysql_fetch_assoc($q_reptext);

$pdf = new FPDF();

$pdf -> Header();
$pdf -> SetAuthor('3form');

//Needed for UTF-8
$pdf -> AddFont('DejaVu', '', 'DejaVuSansCondensed.ttf', true);
//
$pdf -> AddFont('DejaVu', 'B', 'DejaVuSansCondensed-Bold.ttf', true);
//
$pdf -> AddFont('DejaVu', 'BI', 'DejaVuSansCondensed-BoldOblique.ttf', true);
//
$pdf -> AddFont('DejaVu', 'I', 'DejaVuSansCondensed-Oblique.ttf', true);
//
$pdf -> SetFont('DejaVu', '', 7);
$font = "DejaVu";
$alias = "{nb}";
$pdf -> AliasNbPages();

//**Only for test

if ($_SESSION) {
	//***********FROM MY QUOTE*************

	//**FIREPHP
	$pathtoFirePHP = "/home/formdrie/domains/3form.eu/public_html/FirePHPCore-0.3.2/lib/FirePHPCore/FirePHP.class.php";
	require_once ($pathtoFirePHP);
	ob_start();
	$firephp = FirePHP::getInstance(true);

	if (count($_SESSION['img_arr']) > 0 && $report['cut'] == 1) {
		$pdf -> PrintChapter(1, 'VARIA/CHROMA', '');
		//**Loop - VARIA/CHROMA - and add to content var
		for ($i = 0; $i < count($_SESSION['img_arr']); $i++) {
			$currentImage = $serverDocumentRoot . $_SESSION['img_arr'][$i][0]['imagepath'];
			$pdf -> Image($currentImage);
			$pdf -> Ln(2);
			$pdf -> SetFillColor(255, 255, 255);

			//**PRODUCT NAMES
			//**Retrieve names
			$layersPlus = explode("+", $_SESSION['img_arr'][$i][0]["productids"]);
			$layersSpace = implode(" ", $layersPlus);
			$productString = getSampleName2('', $layersSpace);
			$productString = ucfirst($productString);

			//**GAUGExWIDTHxLENGTH
			$theGauge = convertPIDField("gauge", $_SESSION['img_arr'][$i][0]["gauge"]);
			$productString .= "\n" . $theGauge . " x " . $_SESSION['img_arr'][$i][0]["sheetlength"] . "mm x " . $_SESSION['img_arr'][$i][0]["sheetwidth"] . "mm";

			$theFF = convertPIDField("finish", $_SESSION['img_arr'][$i][0]["frontfinish"]);
			$productString .= "\n" . $r_reptext['rep_frontfinish'] . ": " . $theFF;

			$theBF = convertPIDField("finish", $_SESSION['img_arr'][$i][0]["backfinish"]);
			$productString .= "\n" . $r_reptext['rep_backfinish'] . ": " . $theBF;

			//TODO Romain, put an if statement here... "if(patterndir == 1){ ..."

			//**UV Protection
			if ($_SESSION['img_glass_arr'][$i]["uv"] == 1) {
				$productString .= "\n<b>" . $r_reptext['rep_uvprotection'] . "</b>";
			}

			$pdf -> SetFontSize(8);
			$pdf -> MultiCell(0, 3, $productString, 0, 1, 'L', true);
			$pdf -> Ln(5);
		}
	}

	if (count($_SESSION['img_glass_arr']) > 0 && $report['cut'] == 1) {
		//**Loop - GLASS - image and add to content var
		//**Start glass with new page

		$pdf -> SetCol(0);

		$pdf -> PrintChapter(2, 'GLASS', 'test2');
		for ($i = 0; $i < count($_SESSION['img_glass_arr']); $i++) {
			$currentImage = $serverDocumentRoot . $_SESSION['img_glass_arr'][$i]['imagepath'];
			$pdf -> Image($currentImage);
			$pdf -> Ln(2);
			$pdf -> SetFillColor(255, 255, 255);
			//**PRODUCT NAMES
			//**Retrieve names
			$layersPlus = explode("+", $_SESSION['img_glass_arr'][$i]["productids"]);
			$layersSpace = implode(" ", $layersPlus);
			$productString = getSampleName2('', $layersSpace);
			$productString = ucfirst($productString);

			//**GAUGExWIDTHxLENGTH
			$theGauge = convertPIDField("gauge", $_SESSION['img_glass_arr'][$i]["gauge"]);
			$productString .= "\n" . $theGauge . " x " . $_SESSION['img_glass_arr'][$i]["sheetlength"] . "mm x " . $_SESSION['img_glass_arr'][$i]["sheetwidth"] . "mm";

			//**UV Protection
			if ($_SESSION['img_glass_arr'][$i]["uv"] == 1) {
				$productString .= "\n<b>" . $r_reptext['rep_uvprotection'] . "</b>";
			}

			$pdf -> MultiCell(0, 5, $productString, 0, 1, 'L', true);
		}

	}

	if ($report['highres'] == 1) {

		$pdf -> SetCol(0);
		//set margins back to default

		require_once ($serverDocumentRoot . '/my3form_europe/qt_pricingclass.php');

		//**get data
		$q_quoteprod = db_query("SELECT id, length, qty FROM qt_quoteproducts WHERE quote_id='$_SESSION[quote]' ORDER BY id ASC;");
		while ($r_quoteprod = mysql_fetch_assoc($q_quoteprod)) {

			//get highres if any
			$hashighres = hashighres($r_quoteprod['id'], $r_quoteprod['qty']);
			if ($hashighres['allowed'] == true) {

				$sheet_height = $r_quoteprod['length'];
				$y_picadjust = ($r_quoteprod['length'] == "2438" ? "60" : "73");
				$y_textadjust = ($r_quoteprod['length'] == "2438" ? "50" : "63");

				$hr_rowcount = 0;
				$firstsubhr = true;

				$q_hr = db_query("SELECT quoteproduct_id, fab_detail_1, fab_detail_2, process_group FROM qt_fab_products WHERE 
					quoteproduct_id='$r_quoteprod[id]' AND active='1' AND fab_category='highres' AND panel_num <= '$r_quoteprod[qty]' ORDER BY fab_detail_1 ASC;");
				while ($r_hr = mysql_fetch_assoc($q_hr)) {

					//** create header
					$processgroup = ($r_hr['process_group'] == 1 && $r_quoteprod[qty] > 1 ? true : false);
					if ($firstsubhr == true) {
						if ($processgroup == false) {
							$pdf -> PrintChapter(1, "$r_reptext[rep_panel] $r_quoteprod[id] $hashighres[prod_name]", '');
							// Header
							$firstsubhr = false;
						} else {
							$pdf -> PrintChapter(1, "$r_reptext[rep_panel] $r_quoteprod[id] $hashighres[prod_name] - $r_reptext[rep_all] $r_quoteprod[qty] $r_reptext[rep_panels] -", '');
							// Header
							$firstsubhr = false;
						}

						//get starting point
						$getx = $pdf -> GetX();
						$gety = $pdf -> GetY();

						$x = $getx;
						$startx = $x;
						$y = $gety;
						$starty = $y;
					}

					if ($hr_rowcount % 6 == 0 && $hr_rowcount > 0) {
						$y = $y + $y_picadjust;
						$x = $startx;
						if ($y > 215) {//start new page

							$pdf -> AddPage();
							$pdf -> SetCol(0);
							//set margins back to default
							$x = $startx;
							$y = $starty;
						}
					}

					//** build images
					$pdf -> Image($serverHost . "/images/highres/individuals/$r_hr[fab_detail_2].jpg", $x, $y, 30, '', 'JPG');

					$hr_rowcount++;

					$pdf -> SetY($y_textadjust + $y);
					$pdf -> SetX($x);
					$pdf -> MultiCell(32, 8, " $r_hr[fab_detail_2]", 0, 'L', 0);
					//LR $r_quoteprod[length]
					$pdf -> SetY($y);

					$x = $x + 32;
				}

			}
		}

	}//end highres

	if ($report['leadtimes'] == 1) {

		$pdf -> SetCol(0);
		//set margins back to default

		require_once ($serverDocumentRoot . '/my3form_europe/qt_pricingclass.php');

		//**get data
		$q_quote = db_query("SELECT * FROM qt_quotes WHERE id='$_SESSION[quote]';");
		$r_quote = mysql_fetch_assoc($q_quote);

		//get currency
		$currencyconvert = 1;
		if ($r_quote['currency_id'] > 0) {
			$q_cu = db_query("SELECT * FROM currency WHERE id='$r_quote[currency_id]';");
			$r_cu = mysql_fetch_assoc($q_cu);
			$currencyconvert = $r_cu['ex_val'];
			$currsymbol = $r_cu['symbol'];
		}

		$get_fabrication = get_fabrication($r_quote['catalog_id'], $currencyconvert, $currsymbol, $_SESSION['lang']);

		$leadtimescheck = "";
		$totalleaddays = 0;
		if ($get_fabrication['pricing'] > 750) {
			$totalleaddays = ((round($get_fabrication['leadtimes']['total_manmin']) + round($get_fabrication['leadtimes']['cnch']) + round($get_fabrication['leadtimes']['ovenh']) + round($get_fabrication['leadtimes']['vacth'])) / 8 / 60) + 10;
		} elseif ($get_fabrication['pricing'] > 250) {
			$totalleaddays = 10;
		} elseif ($get_fabrication['pricing'] > 1) {
			$totalleaddays = 5;
		}
		//**

		//** build table
		$pdf -> PrintChapter(1, "Lead Times", '');
		// Header

		$cw = 47.5;
		//cw = Cell Width

		//set standard variables for font size, width, height
		$fontsizeh1 = 14;
		$fontsizeh2 = 10;
		$fontsize = 7;
		$lineheight = 5;
		//$lineheightsmall = 4;

		$fullwidth = $cw * 4;
		//the full width of the page for header rows
		$halfwidth = $cw * 2;
		//the full width of the page for header rows
		$pdf -> SetFillColor(225, 222, 201);
		//fill color

		$pdf -> SetFont($font, 'B', $fontsize);
		$pdf -> Cell($fullwidth, $lineheight, "Fabrication lead time", 1, 0, 'L', 1);
		////LR
		$pdf -> Ln();

		$pdf -> SetFillColor(206, 201, 166);
		//fill color
		$pdf -> Cell($halfwidth, $lineheight, "Fab. Lead time indication (w. days), conf. req.", 1, 0, 'L', 1);
		////LR
		$pdf -> Cell($halfwidth, $lineheight, round($totalleaddays) . " days", 1, 0, 'L', 1);
		////LR
		$pdf -> Ln();

		//set font back to no bold

		$pdf -> SetFillColor(238, 236, 225);
		//fill color

		//start details box
		$pdf -> Cell($fullwidth, $lineheight, "Details", 'TLR', 0, 'L', 1);
		$pdf -> Ln();

		//start the building of the machine and human buildings
		$getx = $pdf -> GetX();
		$gety = $pdf -> GetY();

		$x = $getx;
		$y = $gety;

		$width = 65;
		$height = 40;
		$leftmargin = 16;

		for ($c = 0; $c < 3; $c++) {

			$pdf -> SetY($y);
			//set pointer back to previous values
			$pdf -> SetX($x);
			if ($c == 0) {

				$pdf -> MultiCell($width, $height, '', 'L', 'L', 1);
				$pdf -> SetY($y);
				//set pointer back to previous values
				$pdf -> SetX($x + 5);

				//create machine table
				$fill = array(0 => "255", 1 => "255", 2 => "255");
				//

				$pdf -> SetWidths(array(45));
				$pdf -> Row(array("Machine"), "", $fill);
				$pdf -> SetX($x + 5);

				$pdf -> SetWidths(array(30, 15));

				$pdf -> SetFont($font, '', $fontsize);
				$pdf -> Row(array("CNC H. (Min)", round($get_fabrication['leadtimes']['cnch'])), "", $fill);
				$pdf -> SetX($x + 5);
				$pdf -> Row(array("Oven H. (Min)", round($get_fabrication['leadtimes']['ovenh'])), "", $fill);
				$pdf -> SetX($x + 5);
				$pdf -> Row(array("Vac.t. H. (Min)", round($get_fabrication['leadtimes']['vacth'])), "", $fill);
				$pdf -> SetX($x + 5);
				$pdf -> Row(array("Line Bend H. (Min)", round($get_fabrication['leadtimes']['linebendh'])), "", $fill);
				$pdf -> SetX($x + 5);
				$pdf -> Row(array("Saw H. (Min)", round($get_fabrication['leadtimes']['sawh'])), "", $fill);
				$pdf -> SetX($x + 5);
				$pdf -> Row(array("Frees. H. (Min)", round($get_fabrication['leadtimes']['freesh'])), "", $fill);
				$pdf -> SetX($x + 5);
				$pdf -> Row(array("Handtools H. (Min)", round($get_fabrication['leadtimes']['handtools'])), "", $fill);
				$pdf -> SetX($x + 5);

				$pdf -> SetFont($font, 'B', $fontsize);
				$pdf -> Row(array("Total in Minutes", round($get_fabrication['leadtimes']['total_machine'])), "", $fill);

			} elseif ($c == 1) {

				$pdf -> SetFillColor(238, 236, 225);
				//fill color
				$width = ($width - 2.5) * 2;
				$pdf -> MultiCell($width, $height, "", 'R', 'L', 1);

				$pdf -> SetY($y);
				//set pointer back to previous values
				$pdf -> SetX($x);

				//create man table
				$fill = array(0 => "255", 1 => "255", 2 => "255");
				//

				$pdf -> SetWidths(array(80));
				$pdf -> Row(array("Human"), "", $fill);
				$pdf -> SetX($x);

				$pdf -> SetWidths(array(40, 40));

				$pdf -> SetFont($font, '', $fontsize);
				$pdf -> Row(array("CNC F. ManMin", round($get_fabrication['leadtimes']['cncf_manmin'])), "", $fill);
				$pdf -> SetX($x);
				$pdf -> Row(array("Compl. F. ManMin", round($get_fabrication['leadtimes']['complf_manmin'])), "", $fill);
				$pdf -> SetX($x);
				$pdf -> Row(array("Lite F. ManMin", round($get_fabrication['leadtimes']['litef_manmin'])), "", $fill);
				$pdf -> SetX($x);

				$pdf -> SetFont($font, 'B', $fontsize);
				$pdf -> Row(array("Total ManMin", round($get_fabrication['leadtimes']['total_manmin'])), "", $fill);
				$pdf -> SetX($x);

			} elseif ($c == 2) {

				$pdf -> MultiCell($width, $height, "", '', 'L', '');

				$a = $pdf -> GetY();
				$b = $pdf -> GetX();

				$pdf -> SetY($a);
				$pdf -> SetX($b);
			}

			$pdf -> SetY($y);
			//set pointer back to previous values
			$pdf -> SetX($x);
			$x = $pdf -> GetX() + $width;
			$y = $pdf -> GetY();

		}

		$y += $height;
		$pdf -> SetY($y);

		$pdf -> Cell(190, $lineheight, "", 'T', 0, 'L', '');
		//**

	}

	//print fabrication report
	if ($report['fab'] == 1) {

		$pdf -> SetCol(0);
		//set margins back to default
		$pdf -> SetAutoPageBreak(false, -45);
		//this is important to get the 4 box layout to go to the bottom of the page near the page count

		require_once ($serverDocumentRoot . '/my3form_europe/qt_pricingclass.php');

		//build logo and address/phone info
		$q_corpaddr = db_query("SELECT u.*, ua.*, u.phone as uphone FROM users u INNER JOIN useraddr ua ON ua.uid = u.uid where u.uid ='76'");
		//corporate account
		$r_corpaddr = mysql_fetch_assoc($q_corpaddr);
		$logoaddress = "<br>" . nl2br($r_corpaddr['addr']) . "<br>The Netherlands" . "<br>$r_corpaddr[uphone]" . "<br>$r_corpaddr[fax]" . "<br>Web www.3form.eu" . $nbtest;

		//**LOOP FABULOUS FABRICATION
		$otherfab_arr = array();
		$drawingnum = "001";
		$drawingnumtotal = "000";
		/*****************************************
		 * drawingnum total
		 *****************************************/
		//** get drawingnum total, which is only possible by looping through everything twice
		$q_sh = db_query("SELECT * FROM qt_quoteproducts WHERE quote_id='$_SESSION[quote]' AND fabactive='1' ORDER BY date_added;");
		while ($r_sh = mysql_fetch_assoc($q_sh)) {

			//** get all fab
			$q_fab = db_query("SELECT * FROM qt_fab_products WHERE quoteproduct_id ='$r_sh[id]' AND active = '1' AND 
				(fab_category = 'otherfab' OR panel_num <= '$r_sh[qty]') AND fab_category != 'highres' ORDER BY panel_id ASC");
			//remove otherfab?
			// panel_id <='$panel_id_limit' OR
			$count_fab = mysql_num_rows($q_fab);
			//make sure there is at least one fabrication

			$processgroup = true;
			//$uniqueprod =

			//**Loop all fab lines
			if ($count_fab > 0) {//there is fabrication, so proceed
				while ($r_fab = mysql_fetch_assoc($q_fab)) {

					$fabdesr = getfabdata($r_fab, $r_sh, $fabdesr, $r_pq['language_id']);

					if ($r_fab['process_group'] == "0") {//process individually
						$processgroup = false;
					}
				}

				if (!isset($fabdesr['projectquote']) && !isset($fabdesr['quotename']) && !isset($fabdesr['quote_number'])) {
					continue;
				}//move to next because it's empty

				$loopcount = ($processgroup == false ? 1 : $r_sh['qty']);
				//all sheets are process group, set loopcount to qty
				while ($loopcount <= $r_sh['qty']) {
					$drawingnumtotal++;
					$loopcount++;
					$drawingnumtotal = str_pad($drawingnumtotal, 3, "0", STR_PAD_LEFT);
					//pad drawing number with leading zeros
				}
			}
		}

		//** run through the fabrication and display
		/*****************************************
		 * LOOP ALL QUOTE LINES
		 *****************************************/
		$fabdesr = array();
		//$fabreport =
		$q_sh = db_query("SELECT * FROM qt_quoteproducts WHERE quote_id='$_SESSION[quote]' AND fabactive='1' ORDER BY date_added;");

		while ($r_sh = mysql_fetch_assoc($q_sh)) {

			$pdf -> SetCol(0);
			//set margins back to default

			$sheetlength = $r_sh['length'];
			$sheetwidth = $r_sh['width'];

			$q_fab = db_query("SELECT * FROM qt_fab_products WHERE quoteproduct_id ='$r_sh[id]' AND active = '1' AND 
				(fab_category = 'otherfab' or panel_num <= '$r_sh[qty]') AND fab_category != 'highres' ORDER BY panel_id ASC");
			//remove otherfab?
			$count_fab = mysql_num_rows($q_fab);
			//make sure there is at least one fabrication

			$q_pq = db_query("SELECT *, qq.id as qid, qp.id as pid FROM qt_projects qp INNER JOIN qt_quotes qq ON qq.project_id = qp.id WHERE qq.id='$_SESSION[quote]';");
			$r_pq = mysql_fetch_assoc($q_pq);

			$processgroup = true;
			//$uniqueprod =
			$haslines = false;
			$counter = 0;
			$fabdesr = array();

			if ($count_fab > 0) {//there is fabrication, so proceed
				while ($r_fab = mysql_fetch_assoc($q_fab)) {

					$fabdesr = getfabdata($r_fab, $r_sh, $fabdesr, $r_pq['language_id']);
					if ($r_fab['process_group'] == "0") {//process individually
						$processgroup = false;
					}

					//add other fab at end of report
					if (!empty($fabdesr['otherfab_descr'])) {
						$otherfab_arr[] = $fabdesr['otherfab_descr'];
						$fabdesr['otherfab_descr'] = array();
					}
				}

				$firephp -> warn($fabdesr, "fabdesr Array $r_sh[id]");

				if (!isset($fabdesr['projectquote']) && !isset($fabdesr['quotename']) && !isset($fabdesr['quote_number'])) {
					continue;
				}//move to next because it's empty

				$loopcount = ($processgroup == false ? 1 : $r_sh['qty']);
				//all sheets are process group, set loopcount to qty
				/* DOC
				 * The below is duplicating the fab at index [0] to the different panels [1], [2]...
				 * This allow process group to be defined the same for every panel
				 * */

				while ($loopcount <= $r_sh['qty']) {
					$firephp -> warn($r_sh[id], "---------------------");
					$firephp -> warn($loopcount, "#181 loopcount");
					$firephp -> warn($processgroup, "#181 processgroup");
					if ($processgroup == true) {
						$loopcount = 0;
					} else {//add process group to process individually
						if (!empty($fabdesr[0])) {

							foreach ($fabdesr[0] as $key => $value) {
								$fabdesr[$loopcount][$key] = $fabdesr[0][$key];
							}
						}
					}

					$firephp -> warn($fabdesr, "fabdesr Array $r_sh[id] after process group");
					$firephp -> warn(gettype($fabdesr[1]['toprow']), "TOPROW ARRAY COPIED TO 1");

					//get quote project data
					$q_pq = db_query("SELECT *, qq.id as qid, qp.id as pid FROM qt_projects qp 
						INNER JOIN qt_quotes qq ON qq.project_id = qp.id WHERE qq.id='$r_sh[quote_id]';");
					$r_pq = mysql_fetch_assoc($q_pq);

					//get panel
					$panelnum = $allpanels = "";
					if ($processgroup == false) {
						$panelnum = "$loopcount";
						$fabheader = $fabdesr['text']['rep_panel'] . " $r_sh[id]:$loopcount - " . ucwords($fabdesr['prod_name']) . " " . $fabdesr['text']['rep_fabrication'] . "";
						$pdf -> PrintChapter(1, $fabheader, '');
						// Header
					} else {
						$panelnum = $fabdesr['text']['rep_all'];
						//"1-".$r_sh['qty']; //." ".iconv("UTF-8", "ISO-8859-1",$fabdesr['text']['rep_panels'])
						$allpanels = ($r_sh['qty'] > 1 ? "- " . $fabdesr['text']['rep_all'] . " $r_sh[qty] " . $fabdesr['text']['rep_panels'] . " -" : "");
						$fabheader = $fabdesr['text']['rep_panel'] . " $r_sh[id] $allpanels " . ucwords($fabdesr['prod_name']) . " " . $fabdesr['text']['rep_fabrication'] . "";
						$pdf -> PrintChapter(1, $fabheader, '');
						// Header
					}

					//build master image
					$masterimage = $holesstring = "";
					$holesarr = $_SESSION['fab_arr'][$fabdesr[$loopcount]['holeid']];
					//add the holes
					if (!empty($holesarr)) {
						foreach ($holesarr as $key => $value) {
							// draw the white ellipse ( resource $image , int $cx , int $cy , int $width , int $height , int $color )
							$holesstring .= "$value[left],$value[top],$value[size];";
							//$key=

						}
					}

					$ran = rand(1000, 9999);
					$masterimage = "my3form_europe/vsample/10/
						fab:master-l:$sheetlength-w:$sheetwidth-a:" . $fabdesr[$loopcount]['align'] . "-arr:" . $fabdesr[$loopcount]['holeid'] . "-r:$ran" . $fabdesr[$loopcount]['b1'] . $fabdesr[$loopcount]['b2'] . "-lang:$r_pq[language_id]-hs:$holesstring.png";

					//get layer make up
					$layers_arr = array();
					$layers_arr = getlayers($r_sh['texture'], $r_sh['product_layup']);

					$product = $frontpc = $backpc = $texture = $fabrics = $pline = "";
					$parts_arr = array("frontpc" => "color", "backpc" => "color", "product" => "product", "fabrics" => "product");
					$parts_arr['texture'] = "texture";
					foreach ($parts_arr as $type => $column) {
						if ($layers_arr[$type] != "") {
							$allproducts = explode(" ", $layers_arr[$type]);
							$pid_names = array();
							foreach ($allproducts as $key => $prodid) {
								$q_prodname = db_query("SELECT name from products where id = '$prodid'");
								$r_prodname = mysql_fetch_assoc($q_prodname);
								$pid_names[] = trim(swapunderscores($r_prodname['name']));
							}
							${$type} = implode(", ", $pid_names);
						}
					}

					//get pline name
					$q_pline = db_query("SELECT name from prodgroups where id = '$r_sh[pline]'");
					$r_pline = mysql_fetch_assoc($q_pline);
					$pline = ucwords($r_pline['name']);

					//set standard variables for font size, width, height
					$fontsizeh1 = 14;
					$fontsizeh2 = 10;
					$fontsize = 7;
					$lineheight = 5;
					$lineheightsmall = 4;

					$cw = 42;
					//cw = Cell Width
					$firstcell = 22;
					//first cell, smaller than the rest
					$cellsplit = $cw / 4;
					//used for holes
					$fullwidth = $cw * 4 + $firstcell;
					//the full width of the page for header rows
					$pdf -> SetFillColor(138, 181, 255);
					//fill color

					//build ABCD if edge finishes, edge details, or milling is used
					if (!empty($fabdesr[$loopcount]['fabed1']) || !empty($fabdesr[$loopcount]['fabef1']) || !empty($fabdesr[$loopcount]['fabmilling1'])) {

						//set ABCD row
						$pdf -> SetFont($font, '', $fontsize);
						$pdf -> Cell($firstcell, $lineheight, "", 'TLR', 0, 'L', '');
						$pdf -> Cell($cw, $lineheight, "A", 'TLR', 0, 'C', '');
						$pdf -> Cell($cw, $lineheight, "B", 'TLR', 0, 'C', '');
						$pdf -> Cell($cw, $lineheight, "C", 'TLR', 0, 'C', '');
						$pdf -> Cell($cw, $lineheight, "D", 'TLR', 0, 'C', '');
						$pdf -> Ln();

						//edge finishes
						if (!empty($fabdesr[$loopcount]['fabef1'])) {

							//set title row edge finishes
							$pdf -> SetFont($font, 'B', $fontsize);
							$pdf -> Cell($fullwidth, $lineheight, $fabdesr['text']['rep_edgefinishes'], 1, 0, 'L', 1);
							////LR
							$pdf -> Ln();

							//set font back to no bold
							$pdf -> SetFont($font, '', $fontsize);

							//finish row
							$pdf -> Cell($firstcell, $lineheight, "", 1, 0, 'L', '');
							$pdf -> Cell($cw, $lineheight, $fabdesr[$loopcount]['fabef1'], 1, 0, 'L', '');
							$pdf -> Cell($cw, $lineheight, $fabdesr[$loopcount]['fabef2'], 1, 0, 'L', '');
							$pdf -> Cell($cw, $lineheight, $fabdesr[$loopcount]['fabef3'], 1, 0, 'L', '');
							$pdf -> Cell($cw, $lineheight, $fabdesr[$loopcount]['fabef4'], 1, 0, 'L', '');
							$pdf -> Ln();
						}

						//edge details
						if (!empty($fabdesr[$loopcount]['fabed1'])) {

							//set title row edge details
							$pdf -> SetFont($font, 'B', $fontsize);
							$pdf -> Cell($fullwidth, $lineheight, $fabdesr['text']['rep_edgedetails'], 1, 0, 'L', 1);
							////LR
							$pdf -> Ln();

							//set font back to no bold
							$pdf -> SetFont($font, '', $fontsize);

							//Front row
							$pdf -> Cell($firstcell, $lineheight, $fabdesr['text']['rep_front'], 'TLR', 0, 'L', '');
							$pdf -> Cell($cw, $lineheight, $fabdesr[$loopcount]['fabed1'] . " " . $fabdesr[$loopcount]['fabeddepth1'], 'TLR', 0, 'L', '');
							$pdf -> Cell($cw, $lineheight, $fabdesr[$loopcount]['fabed2'] . " " . $fabdesr[$loopcount]['fabeddepth2'], 'TLR', 0, 'L', '');
							$pdf -> Cell($cw, $lineheight, $fabdesr[$loopcount]['fabed3'] . " " . $fabdesr[$loopcount]['fabeddepth3'], 'TLR', 0, 'L', '');
							$pdf -> Cell($cw, $lineheight, $fabdesr[$loopcount]['fabed4'] . " " . $fabdesr[$loopcount]['fabeddepth4'], 'TLR', 0, 'L', '');
							$pdf -> Ln();

							//Back row
							$pdf -> Cell($firstcell, $lineheight, $fabdesr['text']['rep_back'], 1, 0, 'L', '');
							$pdf -> Cell($cw, $lineheight, $fabdesr[$loopcount]['fabed5'] . " " . $fabdesr[$loopcount]['fabeddepth5'], 1, 0, 'L', '');
							$pdf -> Cell($cw, $lineheight, $fabdesr[$loopcount]['fabed6'] . " " . $fabdesr[$loopcount]['fabeddepth6'], 1, 0, 'L', '');
							$pdf -> Cell($cw, $lineheight, $fabdesr[$loopcount]['fabed7'] . " " . $fabdesr[$loopcount]['fabeddepth7'], 1, 0, 'L', '');
							$pdf -> Cell($cw, $lineheight, $fabdesr[$loopcount]['fabed8'] . " " . $fabdesr[$loopcount]['fabeddepth8'], 1, 0, 'L', '');
							$pdf -> Ln();
						}

						//milling
						if (!empty($fabdesr[$loopcount]['fabmilling1'])) {

							//set title row milling
							$pdf -> SetFont($font, 'B', $fontsize);
							$pdf -> Cell($fullwidth, $lineheight, $fabdesr['text']['rep_milling'], 1, 0, 'L', 1);
							////LR
							$pdf -> Ln();

							//set font back to no bold
							$pdf -> SetFont($font, '', $fontsize);

							//finish row
							$pdf -> Cell($firstcell, $lineheight, "", 1, 0, 'L', '');
							$pdf -> Cell($cw, $lineheight, $fabdesr[$loopcount]['fabmilling1'], 1, 0, 'L', '');
							$pdf -> Cell($cw, $lineheight, $fabdesr[$loopcount]['fabmilling2'], 1, 0, 'L', '');
							$pdf -> Cell($cw, $lineheight, $fabdesr[$loopcount]['fabmilling3'], 1, 0, 'L', '');
							$pdf -> Cell($cw, $lineheight, $fabdesr[$loopcount]['fabmilling4'], 1, 0, 'L', '');
							$pdf -> Ln();
						}

						$pdf -> Ln();
					}

					$pdf -> Ln(0);

					//Holes

					//**#181, some holes are processed globally, analyse fabdesr[0]

					if (isset($fabdesr[$loopcount]['toprow'])) {
						$firephp -> warn($loopcount, "#181 loopcount before toprow");
						$firephp -> warn($fabdesr[$loopcount]['fabef2'], "#181 fabef2");
						$firephp -> warn($fabdesr[$loopcount]['toprow'], "#181 TOPROW $loopcount");
						define('DIAMETER', chr(248));
						//"&#216;");//create diameter symbol since &Oslash; doesn't work outside html  //chr(248)

						//set hole table
						$pdf -> SetFont($font, 'B', $fontsize);
						$pdf -> Cell($firstcell, $lineheight, $fabdesr['text']['rep_hole'], 'TLR', 0, 'L', 1);

						$pdf -> SetFont($font, '', $fontsize);

						foreach ($fabdesr[$loopcount]['toprow'] as $key => $holedet) {
							$pdf -> Cell($cellsplit, $lineheight, $holedet, 'TLR', 0, 'L', 1);
						}
						$pdf -> Ln();
						//second column
						$pdf -> Cell($firstcell, $lineheight, "X (mm):", 'TLR', 0, 'L', '');
						foreach ($fabdesr[$loopcount]['xrow'] as $key => $holedet) {
							$pdf -> Cell($cellsplit, $lineheight, $holedet, 'TLR', 0, 'L', '');
						}
						$pdf -> Ln();
						//third column
						$pdf -> Cell($firstcell, $lineheight, "Y (mm):", 'TLR', 0, 'L', '');
						foreach ($fabdesr[$loopcount]['yrow'] as $key => $holedet) {

							$pdf -> Cell($cellsplit, $lineheight, $holedet, 'TLR', 0, 'L', '');
						}
						$pdf -> Ln();
						//Fourth column
						$pdf -> Cell($firstcell, $lineheight, $fabdesr['text']['rep_diameter'] . " (mm):", 1, 0, 'L', '');
						//
						foreach ($fabdesr[$loopcount]['drow'] as $key => $holedet) {
							$pdf -> Cell($cellsplit, $lineheight, $holedet, 1, 0, 'L', '');
						}
						$pdf -> Ln();
						$pdf -> Ln();
					}

					//get line bends
					if (!empty($fabdesr[$loopcount]['line_descr'])) {

						$pdf -> SetFont($font, 'B', $fontsize);
						$pdf -> Cell($firstcell, $lineheight, $fabdesr['text']['rep_linebends'], 1, 0, 'L', 1);
						$pdf -> SetFont($font, '', $fontsize);
						$pdf -> MultiCell($cw * 4, $lineheight, $fabdesr[$loopcount]['line_descr'], 1, 'L', '');
						//LR
						$pdf -> Ln();
					}

					//get shapes
					if (!empty($fabdesr[$loopcount]['shape_descr'])) {

						$pdf -> SetFont($font, 'B', $fontsize);
						$pdf -> Cell($firstcell, $lineheight, $fabdesr['text']['rep_shapes'], 1, 0, 'L', 1);
						$pdf -> SetFont($font, '', $fontsize);
						$pdf -> MultiCell($cw * 4, $lineheight, $fabdesr[$loopcount]['shape_descr'], 1, 'L', '');
						//LR
						$pdf -> Ln();
					}

					//Add Fade data
					$fadedesc1 = $fadedesc2 = $shapedesc = $fade = "";
					if ($fabdesr[$loopcount]['fadeimg'] != "") {
						$fade = $fabdesr['text']['rep_fade'];
						$fadedesc1 = $fabdesr[$loopcount]['fadedescr1'];
						//.' Width: '.$sheetwidth." imgwidth".$imgwidth
						$fadedesc2 = $fabdesr[$loopcount]['fadedescr2'];
						//.$imglength
					}
					if (!empty($fabdesr[$loopcount]['shapespath'])) {
						$shapedesc = $fabdesr[$loopcount]['shape_descr'];
						$shapedesc = preg_replace('#\(.*?\)#s', '', $shapedesc);
					}

					$pdf -> SetY(-149);

					//build three columns for possible images
					$pdf -> SetFont($font, '', $fontsize);
					$pdf -> Cell(65, $lineheightsmall, $fabdesr['text']['rep_sheetdetail'], 'TLR', 0, 'L', '');
					//
					$pdf -> Cell(60, $lineheightsmall, $fade, 'TLR', 0, 'L', '');
					//$fadedesc1." ".$fadedesc2
					$pdf -> Cell(65, $lineheightsmall, $fabdesr[$loopcount]['sideimgview'] . $shapedesc, 'TLR', 0, 'L', '');

					$pdf -> Ln();

					$getx = $pdf -> GetX();
					$gety = $pdf -> GetY();

					$x = $getx;
					$y = $gety;

					$width = 65;
					$height = 65;
					$leftmargin = 16;

					for ($c = 0; $c < 4; $c++) {

						$pdf -> SetY($y);
						//set pointer back to previous values
						$pdf -> SetX($x);
						if ($c == 0) {

							$imgwidth = 37.5;

							$pdf -> MultiCell($width, $height, '', 'L', 'L', '');
							$pdf -> SetY($y);
							//set pointer back to previous values
							$pdf -> SetX($x);

							$pdf -> MultiCell($width, $height, $pdf -> Image($serverHost . "/$masterimage", $x + 4, $y, $imgwidth, '', 'PNG'), '', 'L', '');

						} elseif ($c == 1) {

							$fadeimg = "";
							if ($fabdesr[$loopcount]['fadeimg'] != "") {

								//adjust width/length of image based on width/length of sheet
								$imglength = $sheetlength / 60;
								$imgwidth = $sheetwidth / 60;

								$fadeimg = $pdf -> Image($fabdesr[$loopcount]['fadeimg'], $x + 4, $y + 1, $imgwidth, '', 'PNG');
								//"http://dev.3form.eu/".

								//Display start and end fade text and measurements
								$a = $pdf -> GetY();
								$b = $pdf -> GetX();

								$pdf -> SetY($a + 21);
								$pdf -> SetX($b);

								$pdf -> MultiCell($width, $height, $fadedesc1, '', 'L', '');

								$pdf -> SetY($a + 24);
								$pdf -> SetX($b);

								$pdf -> MultiCell($width, $height, $fadedesc2, '', 'L', '');

								$pdf -> SetY($a);
								$pdf -> SetX($b);
							}

							$width = $width - 5;
							$pdf -> MultiCell($width, $height, $fadeimg, 'LR', 'L', '');

						} elseif ($c == 2) {

							$vgroove = $nonvgroove = $sideimg = $imgdescr = $shapesimg = $nonvgroovtext = $vgroovtext = $sideimgdesc = "";
							$yadjust = -3;
							$grooveadjust = 75;
							if ($fabdesr[$loopcount]['vgroove'] != "") {
								$vgroove = $pdf -> Image($serverHost . "/" . $fabdesr[$loopcount]['vgroove'], $x + 37, $y + $yadjust, 25, '', 'PNG');

								$yadjust = $yadjust + 20;
								$vgroovtext = $fabdesr[$loopcount]['vgroovedesc'];
								//$pdf->MultiCell($width,$height,,'','L','');
							}
							if ($fabdesr[$loopcount]['nonvgroove'] != "") {
								$nonvgroove = $pdf -> Image($serverHost . "/" . $fabdesr[$loopcount]['nonvgroove'], $x + 37, $y + $yadjust, 25, '', 'PNG');

								$nonvgroovtext = $fabdesr[$loopcount]['nonvgroovedesc'];
								//$pdf->MultiCell($width,$height,,'','L','');
							}
							if ($fabdesr[$loopcount]['sideimg'] != "") {
								$sideimg = $pdf -> Image($fabdesr[$loopcount]['sideimg'], $x + 4, $y + 1, 25, '', 'PNG');
								//"http://dev.3form.eu/".
								$imgdescr = "* => " . $fabdesr['text']['rep_front'];
								$sideimgdesc = $fabdesr[$loopcount]['imgdescr'];
								//
							}

							//lines and shapes do not exist at same time, so show shapes if exists in lines box
							if (!empty($fabdesr[$loopcount]['shapespath'])) {
								$shapesimg = $pdf -> Image($serverHost . "/" . $fabdesr[$loopcount]['shapespath'], $x + 4, $y + 3, 58, '', 'JPG');

							}

							$pdf -> MultiCell($width, $height, $shapesimg . $sideimg . //.$fabdesr[$loopcount]['sideimg']
							$pdf -> SetY($y + 21) . $pdf -> SetX(135) . $imgdescr .
							//groove image
							$vgroove . $nonvgroove, '', 'L', '');

							$a = $pdf -> GetY();
							$b = $pdf -> GetX();

							$pdf -> SetY($a - $grooveadjust);
							$pdf -> SetX($b - 48);

							$pdf -> Cell(28, 5, " $vgroovtext", 0, 0, 'L', 0);

							if ($fabdesr[$loopcount]['vgroove'] != "") {//in case of vgroove, then
								$grooveadjust = $grooveadjust - 20;
							}

							$pdf -> SetY($a - $grooveadjust);
							$pdf -> SetX($b - 48);
							$pdf -> Cell(28, 5, " $nonvgroovtext", 0, 0, 'L', 0);

						} elseif ($c == 3) {
							$pdf -> SetX($x + 5);
							$pdf -> MultiCell($width, $height, '', 'L', 'L', '');
						}
						$pdf -> SetY($y);
						//set pointer back to previous values
						$pdf -> SetX($x);
						$x = $pdf -> GetX() + $width;
						$y = $pdf -> GetY();
						//$pdf->MultiCell($width,$height,$r."-".$c." ".$x.",".$y,1,'L');
					}

					$y += $height;
					//	$x=$leftmargin;
					$pdf -> SetY($y);

					$pdf -> Cell(190, $lineheight, "", 'T', 0, 'L', '');
					//					$pdf->Ln(0);

					$pdf -> SetY(-80);
					//THis number puts the last for boxes at bottom, and must match up with $pdf->SetAutoPageBreak (false, -45);80

					$getx = $pdf -> GetX();
					$gety = $pdf -> GetY();

					$x = $getx;
					$y = $gety;

					$width = 47.5;
					$height = 65;
					$leftmargin = 16;

					for ($c = 0; $c < 5; $c++) {

						$pdf -> SetY($y);
						//set pointer back to previous values
						$pdf -> SetX($x);
						if ($c == 0) {

							//build left line
							$pdf -> MultiCell($width, $height, '', 'L', 'L', '');
							//
							$pdf -> SetY($y);
							//set pointer back to previous values
							$pdf -> SetX($x);

							//build notes and signature
							$notes = $fabdesr['text']['rep_notes'] . ":<br>1. " . $fabdesr['text']['rep_notes1'] . //1. DO NOT SCALE FROM DRAWINGS.
							"<br>2. " . $fabdesr['text']['rep_notes2'] . //2. ALL DIMENSIONS ARE IN MILLIMETERS.
							"<br>3. " . $fabdesr['text']['rep_notes3'] . //3. ANY SHEET DESIGNS ARE INDICATIVE ONLY AND <br> NOT PRECISELY REPRESENTATIVE OF THE SHEET <br> THAT WILL BE SUPPLIED.
							"<br>4. " . $fabdesr['text']['rep_notes4'] . //4. WHERE APPLICABLE, CHECK ALL DIMENSIONS ON <br> SITE PRIOR TO COMMENCING CONSTRUCTION.
							"<br>5. " . $fabdesr['text']['rep_notes5'] . //5. REPORT ALL CONFLICTS OF INFORMATION <br> AND/OR DESIGN ISSUES TO 3FORM.
							"<br>6. " . $fabdesr['text']['rep_notes6'];
							//6. IF IN DOUBT CALL 3FORM. ";

							$signaturebox = "<br>" . $fabdesr['text']['rep_customername'] . "<br>" . $fabdesr['text']['rep_date'] . "<br>" . $fabdesr['text']['rep_signature'] . ":<br><br></b>";

							$pdf -> MultiCell($width, $height, $pdf -> SetFont($font, '', 5) . $pdf -> WriteHTML($notes) . $pdf -> SetFont($font, '', $fontsize) . $pdf -> WriteHTML($signaturebox), '', 'L', '');
							//

						} elseif ($c == 1) {

							//build left line
							$pdf -> MultiCell($width, $height, '', 'L', 'L', '');
							//
							$pdf -> SetY($y);
							//set pointer back to previous values
							$pdf -> SetX($x);

							$summary = array($fabdesr['text']['rep_sheet'], $fabdesr['text']['rep_panels']);
							//this used to be three columns, but was changed to two. variables remain the same name though
							$j = $i = 0;
							$column3[$j][$i] = $fabdesr['shid'];
							$i = 1;
							$column3[$j][$i] = $panelnum;
							//$fabdesr['shid'].":".

							$m = $n = 0;
							$column2[$m][$n] = $fabdesr['text']['rep_product'];
							$n = 1;
							$column2[$m][$n] = $fabdesr['pline'];

							$m = 1;
							$n = 0;
							$column2[$m][$n] = $fabdesr['text']['rep_interlayer'];
							$n = 1;
							$column2[$m][$n] = $fabdesr['product'];

							$m = 2;
							$n = 0;
							$column2[$m][$n] = $fabdesr['text']['rep_colouronfront'];
							$n = 1;
							$column2[$m][$n] = $fabdesr['frontpc'];

							$m = 3;
							$n = 0;
							$column2[$m][$n] = $fabdesr['text']['rep_colouronback'];
							$n = 1;
							$column2[$m][$n] = $fabdesr['backpc'];

							$m = 4;
							$n = 0;
							$column2[$m][$n] = $fabdesr['text']['rep_gauge'];
							$n = 1;
							$column2[$m][$n] = $fabdesr['gauge'];
							//

							$m = 5;
							$n = 0;
							$column2[$m][$n] = $fabdesr['text']['rep_ffandtexture'];
							$n = 1;
							$column2[$m][$n] = $fabdesr['ff'] . " " . $fabdesr['texture'];
							//"$x, $y";

							$m = 6;
							$n = 0;
							$column2[$m][$n] = $fabdesr['text']['rep_bf'];
							$n = 1;
							$column2[$m][$n] = $fabdesr['bf'];

							$m = 7;
							$n = 0;
							$column2[$m][$n] = $fabdesr['text']['rep_extralayer'];
							$n = 1;
							$column2[$m][$n] = $fabdesr['fabrics'];
							//." and extra prod text"

							$pdf -> MultiCell($width, $height, $pdf -> SetLeftMargin(60) . $pdf -> SetY($y + 2) . $pdf -> productTable($summary, $column3, $column2, $fabdesr['quote_number'], $fabdesr['text']) . //
							$pdf -> SetLeftMargin(10), '', 'L', '');
							//LR on first

						} elseif ($c == 2) {

							//build left line
							$pdf -> MultiCell($width, $height, '', 'L', 'L', '');
							//
							$pdf -> SetY($y);
							//set pointer back to previous values
							$pdf -> SetX($x);

							//$logoimg = $pdf->Image("http://dev.3form.eu/images/header/3form_logo.gif",$x+1,$y+1,35,'','GIF');
							$logoimg = $pdf -> Image($serverHost . "/images/header/3form_logo.gif", $x + 1, $y + 1, 35, '', 'GIF');

							$pdf -> SetFont($font, '', $fontsize);
							$pdf -> MultiCell($width, $height, $logoimg . $pdf -> SetY($y + 4) . $pdf -> SetLeftMargin(105) . $pdf -> WriteHTML($logoaddress) . $pdf -> SetLeftMargin(10), '', 'L', '');

						} elseif ($c == 3) {

							//build left line
							$pdf -> MultiCell($width, $height, '', 'L', 'L', '');
							//
							$pdf -> SetY($y);
							//set pointer back to previous values
							$pdf -> SetX($x);

							$column = array();
							$a = $b = 0;
							$column[$a][$b] = $fabdesr['text']['rep_project'];
							$a = 1;
							$column[$a][$b] = $fabdesr['projectquote'];
							//." and a longer name for project"
							$a = 2;
							$column[$a][$b] = $fabdesr['text']['rep_title'];
							$a = 3;
							$column[$a][$b] = $fabdesr['quotename'];
							//." and a longer name for title"

							$a = 4;
							$column[$a][$b] = $fabdesr['text']['rep_date'];
							$a = 5;
							$column[$a][$b] = date("d.m.Y");
							$a = 6;
							$column[$a][$b] = $fabdesr['text']['rep_drawingno'] . ":";
							$a = 7;
							$column[$a][$b] = "$drawingnum / $drawingnumtotal";
							//$x $y";
							$a = 8;
							$column[$a][$b] = $fabdesr['text']['rep_createdby'];
							$a = 9;
							$column[$a][$b] = $fabdesr['createdby'];
							//$createdby;

							$pdf -> MultiCell($width, $height, $pdf -> SetLeftMargin(155) . $pdf -> SetY($y + 2) . $pdf -> summaryTable($column, $fabdesr['text']) . //($summary,$column3,$column2){
							$pdf -> SetLeftMargin(10), '', 'L', '');
							//LR on first

						} elseif ($c == 4) {
							//$pdf->SetX($x+5);
							$pdf -> MultiCell($width, $height, '', 'L', 'L', '');

						}

						$pdf -> SetY($y);
						//set pointer back to previous values
						$pdf -> SetX($x);
						$x = $pdf -> GetX() + $width;
						$y = $pdf -> GetY();
					}
					$pdf -> SetCol(0);
					//set margins back to default
					$y += $height;
					$pdf -> SetY($y);
					$pdf -> SetX($getx);
					$pdf -> SetLeftMargin(10);

					$pdf -> Cell(190, $lineheight, "", 'T', 0, 'L', '');

					if ($processgroup == true) { $loopcount = $r_sh['qty'];
					}
					$counter++;
					$loopcount++;
					$drawingnum++;
					$drawingnum = str_pad($drawingnum, 3, "0", STR_PAD_LEFT);
					//pad drawing number with leading zeros
				}

			}
		}//end fabrication while loop

		//add other fab
		if (!empty($otherfab_arr)) {//
			$pdf -> SetCol(0);
			//set margins back to default
			$pdf -> PrintChapter(1, $fabdesr['text']['rep_otherfabrication'], '');

			foreach ($otherfab_arr as $fabnum => $fabvalue) {

				$key = array_keys($fabvalue);
				//pull out the key (which is the sheet number plus other fabrication number)
				$value = array_values($fabvalue);
				//pull out the text

				//display each other fab item
				$pdf -> SetFont($font, 'B', $fontsize);
				$pdf -> Cell($firstcell, $lineheight, "$key[0]", 1, 0, 'L', 1);
				$pdf -> SetFont($font, '', $fontsize);
				$pdf -> MultiCell($cw * 4, $lineheight, $value[0], 1, 'L', '');
				//iconv("UTF-8", "ISO-8859-1", $value[0])
				$pdf -> Ln();
			}
		}

		//TODO move this so that it also includes the merged file in the email?
		if ($report['fabemail'] == 1 || $fabdesr['fabemail'] == 1) {

			$q_pq = db_query("SELECT *, qq.id as qid, qp.id as pid FROM qt_projects qp 
				INNER JOIN qt_quotes qq ON qq.project_id = qp.id WHERE qq.id='$_SESSION[quote]';");
			$r_pq = mysql_fetch_assoc($q_pq);

			$fabdesr['projectquote'] = "$r_pq[project_name] (ref. $r_pq[quote_number])";
			$fabdesr['quotename'] = $r_pq['quote_name'];
			$fabdesr['quote_number'] = $r_pq['quote_number'];

			$q_username = db_query("SELECT fname, lname, mname FROM users where uid='" . $r_pq['assigned_css_uid'] . "'");
			//user account
			$r_username = mysql_fetch_assoc($q_username);
			$createdby = $r_username['fname'] . " " . $r_username['mname'] . " " . $r_username['lname'];

			$report['fabemailtext'] = ($report['fabemailtext'] != "" ? "<b>$createdby Notes:</b><br /><i>" . nl2br(stripslashes($report['fabemailtext'])) . "</i><br /><br />" : "");

			// email stuff (change data below)
			global $DEVSERVER;
			$to = ($DEVSERVER ? $_SESSION['username'] : "fabrication@3form.eu, guinther@3form.eu, romain@3form.eu");
			$from = "ict@3form.eu";
			$subject = "MyQuote - Fabrication Report review for quote $fabdesr[projectquote]";
			$message = "<font face='helvetica'><p>Dear Fabrication Department,<br /><br />
				$createdby is requesting your attention for the quote below concerning fabrication:<br /><br />
				<a href='https://$report[server]" . "/my3form.php?mode=quote&quote=" . $_SESSION[quote] . "&tab=1'>
				https://$report[server]" . "/my3form.php?mode=quote&quote=" . $_SESSION[quote] . "&tab=1</a><br /><br />" . $report['fabemailtext'] . "The fabrication report is enclosed to this e-mail for reference." . "<br /><br /> Regards, <br /> <br /> MyQuote Delivery System</p></font>";
			//<pre>".print_r($fabdesr,1)."</pre>

			// a random hash will be necessary to send mixed content
			$separator = md5(time());

			// carriage return type (we use a PHP end of line constant)
			$eol = PHP_EOL;
			$myquote = sanitize_filename($_SESSION['projectquotefulldesc']);
			$filename = "report_$myquote.pdf";

			// encode data (puts attachment in proper format)
			$pdfdoc = $pdf -> Output("", "S");
			$attachment = chunk_split(base64_encode($pdfdoc));

			// main header (multipart mandatory)
			$headers = "From: " . $from . $eol;
			$headers .= "MIME-Version: 1.0" . $eol;
			$headers .= "Content-Type: multipart/mixed; boundary=\"" . $separator . "\"" . $eol . $eol;
			$headers .= "Content-Transfer-Encoding: 7bit" . $eol;
			$headers .= "This is a MIME encoded message." . $eol . $eol;

			// message
			$headers .= "--" . $separator . $eol;
			$headers .= "Content-Type: text/html; charset=\"utf-8\"" . $eol;
			//iso-8859-1
			$headers .= "Content-Transfer-Encoding: 8bit" . $eol . $eol;
			$headers .= $message . $eol . $eol;

			// attachment
			$headers .= "--" . $separator . $eol;
			$headers .= "Content-Type: application/octet-stream; name=\"" . $filename . "\"" . $eol;
			$headers .= "Content-Transfer-Encoding: base64" . $eol;
			$headers .= "Content-Disposition: attachment" . $eol . $eol;
			$headers .= $attachment . $eol . $eol;
			$headers .= "--" . $separator . "--";

			// send message
			mail($to, $subject, "", $headers);
			//

		}

	}

} else {
	//***********From Test page***********
	$pdf -> PrintChapter(1, 'VARIA/CHROMA 2', '');

}

//**Export PDF
if ($_SESSION) {
	//**below use $pdfname created in  my3form_ajax on function optimizerExportPDF()
	$filepath = $_SESSION[optimizer_images_repository] . $pdfname;
	$_SESSION[cutreport_pdf] = $filepath;
} else {
	$filepath = "cutreport.pdf";
	echo "<a href='" . "/my3form_europe/optimizer/test_pdf/" . $filepath . "'>PDF EXPORTED</a>";

}

//Put out PDF as per user input (specific reports and merged pdfs)
$pdf -> Output($filepath, "F");

$path = "my3form_europe/optimizer/output/" . $_SESSION['quote'] . "/";
$pdfname = $report['pdfname'];
$completepdf = $path . $pdfname;

//read in any other pdfs to merge
$pdf_arr = array();
$pdf_arr[] = $pdfname;
//add the pdf report being created
$dh = opendir($path);
while (($file = readdir($dh)) !== false) {
	if (preg_match("/pdf/", $file)) {
		$pdf_arr[] = $file;
	}
}
closedir($dh);

//the report being created might already exist, so make sure the array is unique
$pdf_arr = array_unique($pdf_arr);

$pdfm = new PDFMerger;

foreach ($pdf_arr as $pdfnum => $pdffile) {
	//skip lead times pdf report
	if ($report['leadtimes'] == 1 && preg_match("/Leadtimes/", $pdffile)) {
		$merge = "";
		break;
	} elseif (preg_match("/Leadtimes/", $pdffile)) {
		continue;
	}

	//merge all pdfs
	$merge = $merge + $pdfm -> addPDF($path . $pdffile, 'all');
}

if ($merge != "") {
	$fileString = $pdfm -> merge('string', $merge);

	file_put_contents($filepath, $fileString);
}

//delete all pdf files except the one that is created.  It must be reachable by link.
foreach ($pdf_arr as $file) {
	if (preg_match("/.pdf/", $file) && !preg_match("/$pdfname/", $file)) {
		unlink($path . $file);
		//
	}
}
?>