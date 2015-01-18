<?php

class OptimizerController extends Controller {
	public function actionIndex() {
		$this -> render('index');
		$this->sortParts(null, null);
		
	}

	// Uncomment the following methods and override them if needed
	/*
	 public function filters()
	 {
	 // return the filter configuration for this controller, e.g.:
	 return array(
	 'inlineFilterName',
	 array(
	 'class'=>'path.to.FilterClass',
	 'propertyName'=>'propertyValue',
	 ),
	 );
	 }

	 public function actions()
	 {
	 // return external action classes, e.g.:
	 return array(
	 'action1'=>'path.to.ActionClass',
	 'action2'=>array(
	 'class'=>'path.to.AnotherActionClass',
	 'propertyName'=>'propertyValue',
	 ),
	 );
	 }
	 */


	//**Define vars
	var $bladeWidth = 5;
	//**Inventory for stock
	var $globalInventory = 10000;

	//**Optimization type
	/*
	 * 0: StartGuillotine=>PanelSaw
	 * 1: StartNested=> CNC
	 * 2: StartMultiStages();=>
	 * c.f. http://www.optimalprograms.com/help/optimization2dx/files/optimization_types.htm
	 * */
	var $optimizationType = 0;

	var $optimizationLevel = 1;

	var $value1 = 50;

	//**INVENTORY
	//**Real size * 100000 to handle decimal in optimizer
	var $sheet1Length = 2438;
	var $sheet1Width = 1219;
	var $sheet2Length = 3048;
	var $sheet2Width = 1219;

	var $panelSurface = array();
	var $sheetProduced = array();
	var $sheetProducedNb = 0;

	var $force4x8 = true;
	var $force4x10 = true;

	var $optimizedArray = array();
	var $totalPanels = 0;
	//Total panels Varia And Chroma

	var $setToOptimize = array();

	var $optLine = -1;

	var $testVar;
	var $imgArray;

	var $debugWriteInputArray;

	var $error;
	var $debug;

	var $cutsArray = array();
	var $cutId = 0;

	var $materialsArray = array();
	var $materialId = 0;

	var $pos = 0;

	var $panelToOptimize = array();

	//**Give my3form_ajax an information if the optimizer succeddd or failed
	var $GeneralOptimizerSucceeded = false;

	//**Romain, 30May2012, var to store totatPanelImg coming form Cut_report
	var $totalPanelImgVC = 0;

	//var $mysheets=array();

	var $panelIdCorrelation = array();

	function loop($mypanels) {
		$tableLength = count($mypanels);

		//** Loop through the panels
		for ($i = 0; $i < $tableLength; $i++) {
			//** First check that panel fit in a sheet
			if ($mypanels[$i]['width'] >= $this -> sheetWidth || $mypanels[$i]['height'] >= $this -> sheetHeight) {
				exit;
			} else {
				$this -> sheetProduced[$this -> sheetProducedNb] = "sheet" . $i;
				$this -> sheetProducedNb++;

			}

		}

	}

	function pricingTable($setInfo, $length, $iSheetNb, $currentSheetId) {
		/*
		 * This function is called for every sheet definition line in the output file
		 */
		$aSimilar = 0;
		//**Create a first entry
		//**Check length, if 2438=>1, if 3048=>2
		//TODO: Convertion
		if ($length > 250000000) {
			$sheetDimension = 2;

		} else {
			$sheetDimension = 1;

		}

		//**Compare current line dimension with the one already in the table
		for ($i = 0; $i <= count($this -> optimizedArray); $i++) {
			if ($this -> optimizedArray[$i]["sheetdimension"] == $sheetDimension && $this -> optimizedArray[$i]["setinfo"] == $setInfo) {
				//**Same dimension, increment the count
				$this -> optimizedArray[$i]["requiredsheets"]++;

				$aSimilar = 1;
			}
		}

		if ($aSimilar == 0) {
			$this -> optLine++;
			//**Create new line
			$this -> optimizedArray[$this -> optLine]["setinfo"] = $setInfo;
			$this -> optimizedArray[$this -> optLine]["sheetdimension"] = $sheetDimension;
			$this -> optimizedArray[$this -> optLine]["productids"] = $this -> setToOptimize[$setInfo]["productids"];
			$this -> optimizedArray[$this -> optLine]["requiredsheets"] = $this -> optimizedArray[$this -> optLine]["requiredsheets"] + 1;
			$this -> optimizedArray[$this -> optLine]["gauge"] = $this -> setToOptimize[$setInfo]["gauge"];
			$this -> optimizedArray[$this -> optLine]["frontfinish"] = $this -> setToOptimize[$setInfo]["frontfinish"];
			$this -> optimizedArray[$this -> optLine]["backfinish"] = $this -> setToOptimize[$setInfo]["backfinish"];
			$this -> optimizedArray[$this -> optLine]["patterndirection"] = $this -> setToOptimize[$setInfo]["patterndirection"];
			$this -> optimizedArray[$this -> optLine]["uv"] = $this -> setToOptimize[$setInfo]["uv"];
			$this -> optimizedArray[$this -> optLine]["discount"] = $this -> setToOptimize[$setInfo]["discount"];

			//**Send first cutloss and cutmethod
			$this -> optimizedArray[$this -> optLine]["cutloss"] = $this -> bladeWidth;

			if ($this -> optimizationType == 0) {
				$this -> optimizedArray[$this -> optLine]["cutmethod"] = "PanelSaw";
			} else if ($this -> optimizationType == 1) {
				$this -> optimizedArray[$this -> optLine]["cutmethod"] = "CNC";
			} else {
				$this -> optimizedArray[$this -> optLine]["cutmethod"] = "Cutting method unknown";
			}

			//**Prepare for sheet info
			$this -> optimizedArray[$this -> optLine]["sheets"][$currentSheetId] = "";

			$aSimilar = 0;
		}

	}

	function getOptimizedArray() {
		//**Remove the setInfo parameter from array before sending it
		for ($i = 0; $i <= count($this -> optimizedArray); $i++) {
			unset($this -> optimizedArray[$i]["setinfo"]);
		}
		return $this -> optimizedArray;
	}


	function sortParts($mypanels, $debug) {	

		/*
		 * Romain, 25April2013, sort the panel by size ascending (bigger to smaller)
		 * THis could solve the fact that panels with pattern dir cannot be optimize together
		 * Ticket #466081
		 * Demo https://dev.3form.eu/my3form.php?mode=quote&quote=2853&tab=1
		 *  */
		 
		$mypanelsSorted = $this -> sortSizeAscending($mypanels);

		//**Put all panel in a list to optimize, optimized items will be removed from this list.
		for ($i = 0; $i < count($mypanelsSorted); ++$i) {
			$panelToOptimize[$i] = $mypanelsSorted[$i];
		}

		//**Sort
		//$nbLoop = count($panelToOptimize);
		$nbLoopCompare = count($panelToOptimize) - 1;

		$setIndex = 0;

		//**Pick a line and loop the others
		foreach ($panelToOptimize as $key => &$value) {
			$nbLoopCompare = count($panelToOptimize) - 1;

			$optimizeID = 0;

			$currentIndex = $key;
			$currentToCompare = $value;

			//**Add current to current optimizeSet

			//**Format productids
			$temp_arr = array();
			if ($currentToCompare["textureid"] != "") {
				$temp_arr[] = $currentToCompare["textureid"];
			}
			if ($currentToCompare["layer1id"] != "") {
				$temp_arr[] = $currentToCompare["layer1id"];
			}
			if ($currentToCompare["layer2id"] != "") {
				$temp_arr[] = $currentToCompare["layer2id"];
			}
			if ($currentToCompare["layer3id"] != "") {
				$temp_arr[] = $currentToCompare["layer3id"];
			}
			if ($currentToCompare["layer4id"] != "") {
				$temp_arr[] = $currentToCompare["layer4id"];
			}

			if ($currentToCompare["discount"] == "") {
				$currentToCompare["discount"] = "0";
			}

			$productstring = implode("+", $temp_arr);

			//**Make information available for the rest of the script
			$this -> setToOptimize[$setIndex]["textureid"] = $currentToCompare["textureid"];
			$this -> setToOptimize[$setIndex]["productids"] = $productstring;
			$this -> setToOptimize[$setIndex]["gauge"] = $currentToCompare["gauge"];
			$this -> setToOptimize[$setIndex]["frontfinish"] = $currentToCompare["frontfinish"];
			$this -> setToOptimize[$setIndex]["backfinish"] = $currentToCompare["backfinish"];
			$this -> setToOptimize[$setIndex]["patterndirection"] = $currentToCompare["patterndirection"];
			$this -> setToOptimize[$setIndex]["uv"] = $currentToCompare["uv"];
			$this -> setToOptimize[$setIndex]["discount"] = $currentToCompare["discount"];

			//**Place current part in setToOptimize array
			//**Romain, 16-11-2011, Try to create 2 lines for quantity 2 instead of setting quantity
			for ($o = 1; $o <= $currentToCompare["quantity"]; $o++) {
				$this -> setToOptimize[$setIndex][$optimizeID] = $currentToCompare;
				$this -> setToOptimize[$setIndex][$optimizeID]["quantity"] = 1;
				$this -> setToOptimize[$setIndex][$optimizeID]["panelid"] = $this -> setToOptimize[$setIndex][$optimizeID]["lineid"] . ":" . $o;
				$optimizeID++;

				//**Add to the $totalPanels var
				$this -> totalPanels++;
			}

			//**MATERIALS TABLE
			//**Make sure we don't define again a material already defined
			//**In the specific case where material was the same in a previous loop but not put with the set cause had a pattern direction and too big to rotate

			if ($currentToCompare["materialdefined"] != 1) {
				//**Save current material
				$layersPlus = explode("+", $productstring);
				$layersSpace = implode(" ", $layersPlus);
				$productDesc = getSampleName2('', $layersSpace);

				$this -> materialsArray[$this -> materialId]["pline"] = $currentToCompare["pline"];
				$this -> materialsArray[$this -> materialId]["productids"] = $productstring;
				$this -> materialsArray[$this -> materialId]["description"] = $productDesc;
				$this -> materialsArray[$this -> materialId]["gauge"] = $currentToCompare["gauge"];
				$this -> materialsArray[$this -> materialId]["frontfinish"] = $currentToCompare["frontfinish"];
				$this -> materialsArray[$this -> materialId]["backfinish"] = $currentToCompare["backfinish"];
				$this -> materialId++;
			}

			//**Loop the other lines
			for ($j = 0; $j < $nbLoopCompare; ++$j) {

				$nextToCompare = current($panelToOptimize);
				$nextIndex = key($panelToOptimize);

				//** Compare
				$result = $this -> array_difference($currentToCompare, $nextToCompare);

				//** The below set pattern direction as an differenciation criteria
				//** The below DOES NOT set pattern direction as an differenciation criteria
				if ($result["textureid"] || $result["layer1id"] || $result["layer2id"] || $result["layer3id"] || $result["layer4id"] || $result["gauge"] || $result["frontfinish"] || $result["backfinish"] || $result["uv"] || $result["discount"]) {

					//**Can't be combined
					next($panelToOptimize);

				} else {
					//**CAN BE COMBINED

					//**Check orientation / ROTATION
					//**Align pattern direction of next part
					if ($currentToCompare["patterndirection"] != 0 && $currentToCompare["patterndirection"] != 3) {
						//**The part use pattern direction 1 or 2
						if ($currentToCompare["patterndirection"] != $nextToCompare["patterndirection"]) {
							//**The pattern are not the same orientation
							//**Check if the part is too big to rotate
							if ($nextToCompare["length"] >= $this -> sheet1Length || $nextToCompare["length"] >= $this -> sheet1Width || $nextToCompare["length"] >= $this -> sheet2Length || $nextToCompare["length"] >= $this -> sheet2Width || $nextToCompare["width"] >= $this -> sheet1Length || $nextToCompare["width"] >= $this -> sheet1Width || $nextToCompare["width"] >= $this -> sheet2Length || $nextToCompare["width"] >= $this -> sheet2Width) {
								//** a part dimension approach the sheet size limit
								//** DO NOTHING (=leave it in the array), it will be handle in a further set loop
								$panelToOptimize[$nextIndex]["rotated"] = 0;
								//**This is a panel in a material already define, put a marker in order not to define a new material when this one is looped
								$panelToOptimize[$nextIndex]["materialdefined"] = 1;
								//**Go to the next record
								next($panelToOptimize);

							} else {
								//**Invert the dimension to align the part same as the parent part
								$invertedWidth = $nextToCompare["length"];
								$invertedLenght = $nextToCompare["width"];

								$nextToCompare["length"] = $invertedLenght;
								$nextToCompare["width"] = $invertedWidth;
								//**Romain, 2-2-2012, add a rotate boolean to give the info to the drawing script
								$nextToCompare["rotated"] = 1;

								//**Ticket #854632
								for ($o = 1; $o <= $nextToCompare["quantity"]; $o++) {
									//$nextToCompare["quantity"]=1;
									$this -> setToOptimize[$setIndex][$optimizeID] = $nextToCompare;
									$this -> setToOptimize[$setIndex][$optimizeID]["quantity"] = 1;
									$this -> setToOptimize[$setIndex][$optimizeID]["panelid"] = $this -> setToOptimize[$setIndex][$optimizeID]["lineid"] . ":" . $o;

									$optimizeID++;
									//**Add to the $totalPanels var
									$this -> totalPanels++;
								}
								//**Instead of removing, set to done!
								unset($panelToOptimize[$nextIndex]);

							}
						} else {
							//** The pattern dir is the same
							$nextToCompare["rotated"] = 0;
							for ($o = 1; $o <= $nextToCompare["quantity"]; $o++) {
								$this -> setToOptimize[$setIndex][$optimizeID] = $nextToCompare;
								$this -> setToOptimize[$setIndex][$optimizeID]["quantity"] = 1;
								$this -> setToOptimize[$setIndex][$optimizeID]["panelid"] = $this -> setToOptimize[$setIndex][$optimizeID]["lineid"] . ":" . $o;
								$optimizeID++;
								//**Add to the $totalPanels var
								$this -> totalPanels++;
							}
							//**Unset will remove item from the array. Watch out that the amount of loops remain the same though
							//** It will happen to compare an empty array
							unset($panelToOptimize[$nextIndex]);

						}
					} else {
						//**The part can be combined and Pattern direction= 0
						//**Just add to the current optimize set
						for ($o = 1; $o <= $nextToCompare["quantity"]; $o++) {
							$this -> setToOptimize[$setIndex][$optimizeID] = $nextToCompare;
							$this -> setToOptimize[$setIndex][$optimizeID]["quantity"] = 1;
							$this -> setToOptimize[$setIndex][$optimizeID]["panelid"] = $this -> setToOptimize[$setIndex][$optimizeID]["lineid"] . ":" . $o;
							$optimizeID++;
							//**Add to the $totalPanels var
							$this -> totalPanels++;
						}

						unset($panelToOptimize[$nextIndex]);
					}

				}

			}

			//**Current Part has been check against all others, remove it from the list
			unset($panelToOptimize[$currentIndex]);

			//**Reset pointer to the top of table
			reset($panelToOptimize);
			$setIndex++;

		}

		//**Set panelToOptimize globally in order to have it in the debug within my3form_ajax
		$this -> panelToOptimize = $panelToOptimize;
	}

	function optimizeSets($setToOptimize, $materialswanted, $debug) {
		global $serverDocumentRoot;
		//**Loop the sets and optimize!
		for ($i = 0; $i < count($setToOptimize); ++$i) {
			//**romain, 21-11-2011, make input/output files linked to quote number
			$setInputFileName = $serverDocumentRoot . "/my3form_europe/optimizer/output/" . $_SESSION['quote'] . "/set" . $i . "in.txt";
			$setOutputFileName = $serverDocumentRoot . "/my3form_europe/optimizer/output/" . $_SESSION['quote'] . "/set" . $i . "out.txt";

			$setInfo = $i;
			$setOutputImgIndex = "set" . $i;
			if ($debug == 1) {
				echo "<br><h2><b>Input file name=</b>" . $setInputFileName . "<br></h2>";
			}

			//**Romain, 20July2012, Try to solve issue #231040
			if ($materialswanted[$i] == "" or $materialswanted[$i] == null) {
				//**When a same material is separated in 2 set during sortParts (material direction is not the same for instance), the second set misses the $materialwanted value,
				//**As a solution for that, I use the value of the first set (0)
				$materialswanted[$i] = $materialswanted[0];
			}

			//**Input file and output created for every set
			$this -> writeInput($setToOptimize[$i], $setInputFileName, $setOutputFileName, $materialswanted[$i], 0);
			// TODO: Turn on
			$this -> readOutputAndDraw($setOutputFileName, $setOutputImgIndex, $setInfo, 0);

		}

		return $this -> optimizedArray;

	}

	function array_difference($old_array, $new_array) {
		foreach ($new_array as $k => $l) {
			if ($old_array[$k] != $l) {
				if ($l != "") {
					$r[$k] = $l;
				} else {
					$r[$k] = $old_array[$k];
				}
			}
		}

		//adding deleted values
		foreach ($old_array as $k => $l) {
			if (!$new_array[$k] && $new_array[$k] != "") {
				$r[$k] = $l;
			}
		}
		return $r;
	}

	function writeInput($setToOptimize, $setInputFileName, $setOutputFileName, $materialswanted, $debug) {
		/*	This function is called for every set of parts	*/
		/*
		 $setToOptimize is here a selection $setToOptimize[$i] coming from optimizeSets
		 $materialswanted is here a selection $materialswanted [$i] coming from optimizeSets
		 */
		$demandPieces = 0;

		$fh = fopen($setInputFileName, 'w') or die("Can't open file");

		//**Line1: Number of demand pieces
		for ($i = 0; $i < count($setToOptimize); ++$i) {
			//**Romain, 16-11-2011, Remove because quantity is handled on setToOptiize array
			$demandPieces += (1 * $setToOptimize[$i]["quantity"]);
		}
		$demandPieces .= "\n";

		fwrite($fh, $demandPieces);

		//**Line2-n: Parts info
		//**Loop the table
		$partID = 0;

		//**Pattern direction
		if ($setToOptimize["patterndirection"] != 0) {
			$canRotate = 0;
		} else {
			$canRotate = 1;
		}

		for ($i = 0; $i < count($setToOptimize); ++$i) {

			//**Handle quantity. Add a line in the text file for each single piece.
			//**Romain,16-11-2011, Remove this loop because now the quantity is already duplicated in setToOptimize array
			for ($j = 1; $j <= $setToOptimize[$i]["quantity"]; ++$j) {

				//**Convertion
				//**Romain, 30July2012, USe the real line id instead of the count var
				//**Original version
				$parts = $this -> convertSize($setToOptimize[$i]["length"]) . " " . $this -> convertSize($setToOptimize[$i]["width"]) . " $canRotate $partID 1" . "\n";

				fwrite($fh, $parts);
				$partID++;
			}

		}

		//**Inventory

		$inventory = $this -> globalInventory . "\n";
	
		if (($materialswanted['fourbyeight'] == "checked") && ($materialswanted['fourbyten'] == "checked")) {
			for ($n = 0; $n < $this -> globalInventory / 2; $n++) {
				$inventory .= "243800000 121900000 0 0 0 0 0 0 0\n";
			}
			for ($n = 0; $n < $this -> globalInventory / 2; $n++) {
				$inventory .= "304800000 121900000 0 0 0 0 1 0 0\n";
			}

		} else if (($materialswanted['fourbyeight'] == "checked") && ($materialswanted['fourbyten'] == "undefined")) {
			for ($n = 0; $n < $this -> globalInventory; $n++) {
				$inventory .= "243800000 121900000 0 0 0 0 0 0 0\n";
			}

		} else if (($materialswanted['fourbyeight'] == "undefined") && ($materialswanted['fourbyten'] == "checked")) {
			for ($n = 0; $n < $this -> globalInventory; $n++) {
				$inventory .= "304800000 121900000 0 0 0 0 1 0 0\n";
			}
		} else {
			for ($n = 0; $n < $this -> globalInventory / 2; $n++) {
				$inventory .= "243800000 121900000 0 0 0 0 0 0 0\n";
			}
			for ($n = 0; $n < $this -> globalInventory / 2; $n++) {
				$inventory .= "304800000 121900000 0 0 0 0 1 0 0\n";
			}
		}

	
		fwrite($fh, $inventory);

		fclose($fh);

		$theOptimizer = $serverDocumentRoot . "/my3form_europe/optimizer/compilation2/test_cut2dx";

		//**Convert blade
		$bladeWidth = $this -> bladeWidth * 100000;

		//**Exec command

		$command = "$theOptimizer '$setInputFileName' '$setOutputFileName' $this->optimizationType $this->optimizationLevel $bladeWidth";
		//TODO: Check if the exec command fail
		if (!exec($command, $output, $return_var)) {
			$firephp -> warn("!!!! ERROR WITH OPTIMIZER");
		} else {
			$firephp -> warn($output, "OPTIMIZER OUTPUT");
			$firephp -> warn($return_var, "OPTIMIZER RETURN VAR");
		}

		//**Debug. Put in the returned variable the value to check.
		$this -> debugWriteInputArray = $materialswanted;
		return $this -> debugWriteInputArray;
	}

	function readOutputAndDraw($setOutputFileName, $setOutputImgIndex, $setInfo, $debug) {

		/*Is called in a loop for each output file*/

		//******** Setup *******
		$myFile = $setOutputFileName;

		//** Read into an array
		$f = fopen($myFile, "r");

		//******** Explore file *******
		if ($f) {
			$sheetNb = fscanf($f, "%d\n");

			//**Loop EACH SHEET info line of output file
			//**Romain, 17/10/2011, change loop start nb

			for ($iSheetNb = 0; $iSheetNb < $sheetNb[0]; $iSheetNb++) {

				$sheetInfo = fscanf($f, "%d %d %d %d %d %d %d %d %d %d\n");

				//**Call CutReport class
				$CutReport = new CutReport();
				$CutReport -> buildCanvas($sheetInfo[1], $sheetInfo[2]);

				if ($sheetInfo) {
					list($repository_index, $length, $width, $TrimTop, $TrimLeft, $TrimBottom, $TrimRight, $repository_id, $priority, $num_holes) = $sheetInfo;

					//**Define current sheet id
					$currentSheetId = $iSheetNb . $this -> genRandomString();

					//**Define base info of optimizedArray
					$this -> pricingTable($setInfo, $length, $iSheetNb, $currentSheetId);

					//** Number parts
					$tbPartNb = fscanf($f, "%d\n");

					//** Part info
					if ($tbPartNb[0] > 0) {
						//**LOOP EACH PANEL
						for ($i = 1; $i <= $tbPartNb[0]; $i++) {
							$tbParts = fscanf($f, "%d %d %d %d %d\n");

							//**Add part ids
							//**define Part length/width
							//**romain, 08august2012, intervert length/width
							//**This is what's needed for production spreadsheet
							$optimizedLength = ($tbParts[3] - $tbParts[1]) / 100000;
							$optimizedWidth = ($tbParts[4] - $tbParts[2]) / 100000;
							//**Replace by the setToOptimize values for that we use the input length/width, not the optimized once

							$currentPartId = $tbParts[0];
							$pLength = $this -> setToOptimize[$setInfo][$currentPartId]["length"];
							$pWidth = $this -> setToOptimize[$setInfo][$currentPartId]["width"];
							;

							//**Below the part id from the optimizer output file
							$lengthSheetScaled = $length / 100000;
							$widthSheetScaled = $width / 100000;

							//**Check full sheet
							$fullSheet = false;
							if (($lengthSheetScaled == $pLength && $widthSheetScaled == $pWidth) || ($lengthSheetScaled == $pWidth && $widthSheetScaled == $pLength)) {
								$fullSheet = true;
							}

							//**Cutloos and cutmethod
							//**IF 1 part, set cutID=>FS, cutloos=>-1.0, cutmethod=>unoptimized
							if ($tbPartNb[0] == 1 && $fullSheet == true) {
								$this -> optimizedArray[$this -> optLine]["cutmethod"] = "unoptimized";
								$this -> optimizedArray[$this -> optLine]["cutloss"] = "-1.0";
							}

							//**EXISTING CUT - Check if used in $cuts table already
							$newCut = true;

							if (count($this -> cutsArray) != 0) {
								for ($m = 0; $m <= count($this -> cutsArray); $m++) {

									//**Romain, 16-11-2011, Change to make sure that if length/width
									if ($pWidth == $this -> cutsArray[$m]["width"] && $pLength == $this -> cutsArray[$m]["length"]) {

										//**Same cut size, Check sheet id
										//**REMOVE after Jarom ask different cut id for different material, not different sheet

										//**Same cut size, check product ids
										if ($this -> cutsArray[$m]["productids"] == $this -> optimizedArray[$this -> optLine]["productids"]) {

											//**SAME SIZE, SAME MATERIAL=> USE THE CURRENT CUT ID
											//**Romain,11-10-2011, check also gauge, UV, FF, BF, discount, patterndirection on Jarom's request to affect the same cut id

											if (($this -> cutsArray[$m]["gauge"] == $this -> optimizedArray[$this -> optLine]["gauge"]) && ($this -> cutsArray[$m]["uv"] == $this -> optimizedArray[$this -> optLine]["uv"]) && ($this -> cutsArray[$m]["frontfinish"] == $this -> optimizedArray[$this -> optLine]["frontfinish"]) && ($this -> cutsArray[$m]["backfinish"] == $this -> optimizedArray[$this -> optLine]["backfinish"]) && ($this -> cutsArray[$m]["discount"] == $this -> optimizedArray[$this -> optLine]["discount"]) && ($this -> cutsArray[$m]["patterndirection"] == $this -> setToOptimize[$setInfo][$currentPartId]["patterndirection"])) {

												//**Set cut info to optimized array
												$currentCut = $this -> cutsArray[$m]["name"];
												//**Cutloos and cutmethod
												//**IF 1 part, set cutID=>FS
												if ($tbPartNb[0] == 1 && $fullSheet == true) {
													$currentCut = "FS";

												}

												$this -> optimizedArray[$this -> optLine]["sheets"][$currentSheetId][$currentPartId]["partid"] = $tbParts[0];
												$this -> optimizedArray[$this -> optLine]["sheets"][$currentSheetId][$currentPartId]["cutid"] = $currentCut;
												$this -> optimizedArray[$this -> optLine]["sheets"][$currentSheetId][$currentPartId]["length"] = $pLength;
												$this -> optimizedArray[$this -> optLine]["sheets"][$currentSheetId][$currentPartId]["width"] = $pWidth;
												$this -> optimizedArray[$this -> optLine]["sheets"][$currentSheetId][$currentPartId]["optimizedlength"] = $optimizedLength;
												$this -> optimizedArray[$this -> optLine]["sheets"][$currentSheetId][$currentPartId]["optimizedwidth"] = $optimizedWidth;

												$this -> optimizedArray[$this -> optLine]["sheets"][$currentSheetId][$currentPartId]["patterndirection"] = $this -> setToOptimize[$setInfo][$currentPartId]["patterndirection"];
												$this -> optimizedArray[$this -> optLine]["sheets"][$currentSheetId][$currentPartId]["rotated"] = $this -> setToOptimize[$setInfo][$currentPartId]["rotated"];
												$this -> optimizedArray[$this -> optLine]["sheets"][$currentSheetId][$currentPartId]["panelid"] = $this -> setToOptimize[$setInfo][$currentPartId]["panelid"];
												$newCut = false;
											}
										}

									}

								}
							}
							//**NEW CUT

							if ($newCut == true) {
								//**Cutloos and cutmethod
								//**IF 1 part, set cutID=>FS
								if ($tbPartNb[0] == 1 && $fullSheet == true) {
									$currentCut = "FS";

								} else {
									$this -> cutId++;
									//**TODO: Define "POS" instead of "C". Is that causing an issue in SO/SAP?
									$currentCut = "C" . $this -> cutId;

									//**Insert new cut type in cut array
									//**Note:FS is not registered in cutsArray cause size can be different
									$this -> cutsArray[$this -> cutId - 1]["name"] = $currentCut;
									$this -> cutsArray[$this -> cutId - 1]["sheetid"] = $currentSheetId;
									$this -> cutsArray[$this -> cutId - 1]["productids"] = $this -> optimizedArray[$this -> optLine]["productids"];
									$this -> cutsArray[$this -> cutId - 1]["gauge"] = $this -> optimizedArray[$this -> optLine]["gauge"];
									$this -> cutsArray[$this -> cutId - 1]["uv"] = $this -> optimizedArray[$this -> optLine]["uv"];
									$this -> cutsArray[$this -> cutId - 1]["frontfinish"] = $this -> optimizedArray[$this -> optLine]["frontfinish"];
									$this -> cutsArray[$this -> cutId - 1]["backfinish"] = $this -> optimizedArray[$this -> optLine]["backfinish"];
									$this -> cutsArray[$this -> cutId - 1]["length"] = $pLength;
									$this -> cutsArray[$this -> cutId - 1]["width"] = $pWidth;
									$this -> cutsArray[$this -> cutId - 1]["discount"] = $this -> optimizedArray[$this -> optLine]["discount"];
									$this -> cutsArray[$this -> cutId - 1]["patterndirection"] = $this -> setToOptimize[$setInfo][$currentPartId]["patterndirection"];

									//$CutIndex = key($this -> cutsArray);
								}

								$this -> optimizedArray[$this -> optLine]["sheets"][$currentSheetId][$currentPartId]["partid"] = $tbParts[0];
								$this -> optimizedArray[$this -> optLine]["sheets"][$currentSheetId][$currentPartId]["cutid"] = $currentCut;
								$this -> optimizedArray[$this -> optLine]["sheets"][$currentSheetId][$currentPartId]["length"] = $pLength;
								$this -> optimizedArray[$this -> optLine]["sheets"][$currentSheetId][$currentPartId]["width"] = $pWidth;
								$this -> optimizedArray[$this -> optLine]["sheets"][$currentSheetId][$currentPartId]["optimizedlength"] = $optimizedLength;
								$this -> optimizedArray[$this -> optLine]["sheets"][$currentSheetId][$currentPartId]["optimizedwidth"] = $optimizedWidth;
								$this -> optimizedArray[$this -> optLine]["sheets"][$currentSheetId][$currentPartId]["patterndirection"] = $this -> setToOptimize[$setInfo][$currentPartId]["patterndirection"];
								$this -> optimizedArray[$this -> optLine]["sheets"][$currentSheetId][$currentPartId]["rotated"] = $this -> setToOptimize[$setInfo][$currentPartId]["rotated"];
								$this -> optimizedArray[$this -> optLine]["sheets"][$currentSheetId][$currentPartId]["panelid"] = $this -> setToOptimize[$setInfo][$currentPartId]["panelid"];
							}

							//**Create a part in the image
							//**second parameter takes the value pattern direction of the first part
							$CutReport -> addPart($tbParts, $this -> optimizedArray[$this -> optLine]["sheets"][$currentSheetId][$currentPartId]["patterndirection"], $currentCut, $this -> optimizedArray[$this -> optLine]["sheets"][$currentSheetId][$currentPartId]["rotated"], $this -> optimizedArray[$this -> optLine]["sheets"][$currentSheetId][$currentPartId]["panelid"], $currentPartId);

						}

					}

					//** Number of cuts
					$tbCutNb = fscanf($f, "%d");

					//** Cuts info
					if ($tbCutNb[0] > 0) {
						for ($i = 1; $i <= $tbCutNb[0]; $i++) {
							$tbCutInfo = fscanf($f, "%d %d %d %d %d\n");
						}
					}

					//** Number of Waste
					$tbWasteNb = fscanf($f, "%d\n");

					if ($tbWasteNb[0] > 0) {
						for ($i = 1; $i <= $tbWasteNb[0]; $i++) {
							//**Waste info
							$tbWasteInfo = fscanf($f, "%d %d %d %d\n");

						}
					}

				}

				//**Output image
				$currentProductIds = $this -> setToOptimize[$setInfo]["productids"];
				$currentGauge = $this -> setToOptimize[$setInfo]["gauge"];
				$currentSheetLength = $lengthSheetScaled;
				$currentSheetWidth = $widthSheetScaled;
				$currentFF = $this -> setToOptimize[$setInfo]["frontfinish"];
				$currentBF = $this -> setToOptimize[$setInfo]["backfinish"];
				$textureId = $this -> setToOptimize[$setInfo]["textureid"];
				$uv = $this -> setToOptimize[$setInfo]["uv"];

				$iImageIndex = "sheet" . $iSheetNb;

				$CutReport -> outputImage($setOutputImgIndex, $iImageIndex, $currentSheetId, $textureId, $currentProductIds, $currentGauge, $currentSheetLength, $currentSheetWidth, $currentFF, $currentBF, $uv);
				//**Give access to cutreport within Optimizer class
				$this -> imgArray[] = $CutReport -> imgArray;

				//**Romain, 30May2012, store totatPanelImg coming form Cut_report
				//**Use += because cutReport class is called multiple times for every output file.
				$this -> totalPanelImgVC += $CutReport -> totalPanelImgVC;

			}

			//**Info for my3form_ajax.php
			$this -> GeneralOptimizerSucceeded = true;

			return $this -> cutsArray;

		}

	}

	function sortAsc() {
		sort($this -> panelWidth, SORT_NUMERIC);
	}

	function sortDesc() {
		rsort($this -> panelWidth, SORT_NUMERIC);

	}

	function shuffle() {
		shuffle($this -> $panelWidth);
	}

	function test($myText, $debug) {
		$this -> testVar = $myText;
		return $this -> testVar;
	}

	function convertSize($int) {
		$converted = number_format($int, 5, '.', '');

		$converted = number_format($converted, 5, '', '');
		return $converted;
	}

	function errorMsg($msg) {
		$this -> error = $msg;
		return $this -> error;
	}

	function genRandomString() {
		$length = 5;
		$characters = "0123456789";
		for ($p = 0; $p < $length; $p++) {
			$string .= $characters[mt_rand(0, strlen($characters) - 1)];
		}
		return $string;
	}

	function createQuoteImgRepository() {
		global $firephp;
		global $serverDocumentRoot;
		$imgAbsolutePath = $serverDocumentRoot . "/my3form_europe/optimizer/output/" . $_SESSION['quote'] . "/";
		$imgRelativePath = "/my3form_europe/optimizer/output/" . $_SESSION['quote'] . "/";
		$firephp -> warn($imgAbsolutePath, "Output location for MyQuote");
		//**Create folder, repository for the current quote images
		@mkdir($imgAbsolutePath, 0777);
		chmod($imgAbsolutePath, 0777);
		$_SESSION['optimizer_images_repository'] = $imgAbsolutePath;
	}

	function sortSizeAscending($mypanels) {
		/*
		 * Sort panels by ascending size (bigger to smaller) for better optimization
		 *
		 */
		global $firephp;
		$BiggestDimension = array();

		//**Find biggest value between length/width
		foreach ($mypanels as $key => $value) {
			//**Put this biggest value in an array with ref to the original array
			//$BiggestDimension[$key]['lineid']=$value['lineid'];

			if ($value['length'] > $value['width']) {
				$BiggestDimension[$key]['dim'] = $value['length'];
			} else {
				$BiggestDimension[$key]['dim'] = $value['width'];
			}
			$BiggestDimension[$key]['lineid'] = $value['lineid'];
			$BiggestDimension[$key]['refdata'] = $value;
		}

		//**Sort the final array
		$firephp -> warn($BiggestDimension, "Biggest Dim");
		// Comparison function
		function cmp($a, $b) {
			if ($a == $b) {
				return 0;
			}
			return ($a < $b) ? -1 : 1;
		}

		uasort($BiggestDimension, 'cmp');
		$BiggestDimension = array_reverse($BiggestDimension);

		//**Loop the sorted array to rebuild mypanel but with bigger to smaller size
		$mypanelsSorted = array();
		foreach ($BiggestDimension as $k => $v) {
			$mypanelsSorted[$k] = $v['refdata'];

		}
		return $mypanelsSorted;

	}

}
