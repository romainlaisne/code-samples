<?php

$absolutePath = $_SERVER['DOCUMENT_ROOT'] . "/my3form_europe/";
//$relativePath = "/my3form_europe/classes/";
require_once ($absolutePath . "qt_fabfuncs.php");
require_once ($absolutePath . "qt_pricingclass.php");

//
class CreateXml {
	/*
	 * DOCUMENTATION
	 * 13Oct2013 by Romain (romainlaisne@gmail.com)
	 * 
	 ****** OUTPUT *******
	 * This class create 2 xml output, icepack.xml and simplefab.xml
	 * 
	 * icepack.xml
	 * used for the import in SuperOffice
	 * contains layup info, cut info, price info
	 * 
	 * simplefab.xml
	 * used to export data from MyQuote to SAP (via csv and excel upload to planning team)
	 * contains layup info, cut info, price info, production leadtimes
	 * 
	 ****** PROCESS *******
	 * The script loops through the lines set in MyQuote.
	 * Then it select the layup in qt_layup for one of the item and loop through the PETG sheets indicated in the fields
	 * Front_petg_1, front_petg_2, petg_1, petg_2, back_petg_1, back_petg_2
	 * 
	 * 
	 ****** DB TABLES ******
	 * This function uses the qt_layup and qt_raw_materials tables to build the xmls
	 * 
	 */


    public $premiumfinishesarr = array();
    public $sheetpanels_arr = array();
    public $sheetuid_value = 0;

    public function build_xml($in_xml, $in_quote, $in_tempnum) {
		
		global $firephp;
        $firephp->warn($_SESSION['to_export_icepack'],"TO EXPORT ICEPACK");
        $variafabricationnotes = $chromafabricationnotes = $glassfabricationnotes = "";

        $doc = new DOMDocument('1.0', 'UTF-8');
        //Note: I put UTF-8 instead of ISO-8859-1 that ICE uses
        $doc -> formatOutput = true;

        $start = $doc -> createElement("report");
        if ($in_xml == "ICEpack.xml") { $start -> setAttribute('id', mktime());
        }//only on icepack.xml
        $doc -> appendChild($start);

        $i = $doc -> createElement("IceVersion");
        $i -> appendChild($doc -> createTextNode("jjh:280612"));
        $start -> appendChild($i);

        if ($in_xml == "SimpleFab.xml") {

            //get sapsoldtoshipto for header of xml
            $q_sapstst = db_query("SELECT sap_soldto, sap_shipto FROM qt_quotes WHERE id='$in_quote'");
            $r_sapstst = mysql_fetch_assoc($q_sapstst);

            $sapsoldto = ($r_sapstst['sap_soldto'] != "" ? $r_sapstst['sap_soldto'] : "");
            $sapshipto = ($r_sapstst['sap_shipto'] != "" ? $r_sapstst['sap_shipto'] : "");

            $j = $doc -> createElement("sapSoldto");
            $j -> appendChild($doc -> createTextNode("$sapsoldto"));
            $start -> appendChild($j);

            $k = $doc -> createElement("sapShipto");
            $k -> appendChild($doc -> createTextNode("$sapshipto"));
            $start -> appendChild($k);
        }

        if ($in_xml == "ICEpack.xml") {
            $r = $doc -> createElement("sheets");
            $start -> appendChild($r);
        }//only on icepack.xml

        if ($in_xml == "ICEpack.xml") {
            $cycle = $_SESSION['to_export_icepack'];
            //cycle through each sheet group
        } else {

            //cycle through each sheet.  Each sheet must be pulled out of sheet group and cycled through
            $new_arr = array();
            //$sheets_arr =

            foreach ($_SESSION['to_export_icepack'] as $sheetgroupid => $sheetgroup_arr) {//

                foreach ($sheetgroup_arr as $sheetfield => $sheetvalue) {

                    if ($sheetfield == "sheets") {//&& $sheetgroup_arr['pline'] != 3
                        foreach ($sheetvalue as $sheetid => $sheet_arr) {

                            foreach ($sheet_arr as $panelkey => $panelvalue) {
                                $this -> sheetpanels_arr[$sheetid][$panelkey] = $panelvalue;
                            }

                            $new_arr[$sheetid] = $sheetgroup_arr;
                            unset($new_arr[$sheetid]['sheets']);
                        }
                    }
                    //				elseif ($sheetgroup_arr['pline'] == 3  && $sheetfield == "qty") {
                    //
                    //					for($x=0;$x<$sheetvalue;$x++){
                    //						$id = "$sheetgroupid$x";
                    //						$new_arr[$id] = $sheetgroup_arr;
                    //					}
                    //				}
                }
            }

            $cycle = $new_arr;
        }

        //$firephp->warn($cycle, "CYCLE for XML");
        foreach ($cycle as $index => $quoteproduct) {
			$firephp->warn($quoteproduct, "PRODUCT LOOPED");
            $this -> sheetuid_value = $index;

            if ($index === "misc") {
                continue;
            }

            if ($in_xml == "SimpleFab.xml") {
                $r = $doc -> createElement("sheetGroup");
                $start -> appendChild($r);

                $sguid = $doc -> createElement("sheetGroupUID");
                $sguid -> appendChild($doc -> createTextNode($quoteproduct['sheetUID']));
                //misnamed
                $r -> appendChild($sguid);
            }//only on simplefab.xml

            if ($in_xml == "ICEpack.xml") {
                $s = $doc -> createElement("material");
            }

            $b = $doc -> createElement("sheet");
            if ($in_xml == "ICEpack.xml") { $b -> setAttribute('description', ucwords($quoteproduct['sheet']));
            }

            if ($in_xml == "SimpleFab.xml") {//this is also used by icepack.xml, but icepack.xml places it at the end
                $sheetid = $doc -> createElement("sheetUID");
                $sheetid -> appendChild($doc -> createTextNode($index));
                $b -> appendChild($sheetid);
            }

            $bfd = $doc -> createElement("BackFinishDescription");
            $bfd -> appendChild($doc -> createTextNode(ucwords($quoteproduct['BackFinishDescription'])));
            $b -> appendChild($bfd);

            $descr = $doc -> createElement("Description");
            $descr -> appendChild($doc -> createTextNode(ucwords($quoteproduct['Description'])));
            $b -> appendChild($descr);

            $fn = $doc -> createElement("FinishName");
            $fn -> appendChild($doc -> createTextNode($quoteproduct['FinishName']));
            //empty
            $b -> appendChild($fn);

            $ffd = $doc -> createElement("FrontFinishDescription");
            $ffd -> appendChild($doc -> createTextNode(ucwords($quoteproduct['FrontFinishDescription'])));
            $b -> appendChild($ffd);

            $gauge = $doc -> createElement("Gauge");
            $gauge -> appendChild($doc -> createTextNode($quoteproduct['Gauge']));
            $b -> appendChild($gauge);

            $ht = $doc -> createElement("Height");
            $ht -> appendChild($doc -> createTextNode($quoteproduct['Height']));
            $b -> appendChild($ht);

            $md = $doc -> createElement("Material_Description");
            $md -> appendChild($doc -> createTextNode(ucwords($quoteproduct['Material_Description'])));
            $b -> appendChild($md);

            $pn = $doc -> createElement("PartNumber");
            $pn -> appendChild($doc -> createTextNode($quoteproduct['PartNumber']));
            $b -> appendChild($pn);

            $cl = $doc -> createElement("TFM_Cut_Loss");
            $cl -> appendChild($doc -> createTextNode($quoteproduct['TFM_Cut_Loss']));
            $b -> appendChild($cl);

            $cm = $doc -> createElement("TFM_Cut_Method");
            $cm -> appendChild($doc -> createTextNode($quoteproduct['TFM_Cut_Method']));
            $b -> appendChild($cm);

            //only put this xml field in if it exists
            if (array_key_exists("TFM_SheetMaterialDirection", $quoteproduct)) {
                $smd = $doc -> createElement("TFM_SheetMaterialDirection");
                $smd -> appendChild($doc -> createTextNode($quoteproduct['TFM_SheetMaterialDirection']));
                $b -> appendChild($smd);
            }

            //add chroma/glass specific xml fields
            if ($quoteproduct['pline'] == "2") {//chroma

                $chromasct = $doc -> createElement("TFM_Sheet_Cast_Type");
                $chromasct -> appendChild($doc -> createTextNode($quoteproduct['TFM_Sheet_Cast_Type']));
                $b -> appendChild($chromasct);

            } elseif ($quoteproduct['pline'] == "3") {//glass

                $glassgauge = $doc -> createElement("TFM_Sheet_PouredGlass_Gauge");
                $glassgauge -> appendChild($doc -> createTextNode($quoteproduct['TFM_Sheet_PouredGlass_Gauge']));
                $b -> appendChild($glassgauge);

                $glasstype = $doc -> createElement("TFM_Sheet_PouredGlass_Type");
                $glasstype -> appendChild($doc -> createTextNode($quoteproduct['TFM_Sheet_PouredGlass_Type']));
                $b -> appendChild($glasstype);
            }

            $uvp = $doc -> createElement("UV_Protection");
            $uvp -> appendChild($doc -> createTextNode($quoteproduct['UV_Protection']));
            $b -> appendChild($uvp);

            $ut = $doc -> createElement("UserTag");
            $ut -> appendChild($doc -> createTextNode($quoteproduct['UserTag']));
            //filled with prod ids Aug 7, 2012 used to find sheetgroup difficulty...
            $b -> appendChild($ut);

            $width = $doc -> createElement("Width");
            $width -> appendChild($doc -> createTextNode($quoteproduct['Width']));
            $b -> appendChild($width);

            $xd = $doc -> createElement("XDimension");
            $xd -> appendChild($doc -> createTextNode($quoteproduct['XDimension']));
            $b -> appendChild($xd);

            //chroma and glass get Y dimension (gauge), but Varia does not
            if ($quoteproduct['pline'] == "2" || $quoteproduct['pline'] == "3") {
                $xy = $doc -> createElement("YDimension");
                $xy -> appendChild($doc -> createTextNode($quoteproduct['YDimension']));
                $b -> appendChild($xy);
            }

            $xz = $doc -> createElement("ZDimension");
            $xz -> appendChild($doc -> createTextNode($quoteproduct['ZDimension']));
            $b -> appendChild($xz);

            if ($in_xml == "ICEpack.xml") {
                $tbp = $doc -> createElement("totalBucketPrice");
                $tbp -> appendChild($doc -> createTextNode($quoteproduct['totalBucketPrice']));
                $b -> appendChild($tbp);
            }

            $size = $doc -> createElement("size");
            $size -> appendChild($doc -> createTextNode($quoteproduct['size']));
            $b -> appendChild($size);

            $uvp2 = $doc -> createElement("uvProtection");
            $uvp2 -> appendChild($doc -> createTextNode($quoteproduct['uvProtection']));
            $b -> appendChild($uvp2);

            $pn2 = $doc -> createElement("partNumber");
            $pn2 -> appendChild($doc -> createTextNode($quoteproduct['partNumber']));
            $b -> appendChild($pn2);

            if ($in_xml == "ICEpack.xml") {
                $smdd = $doc -> createElement("sheetMaterialDirectionDesc");
                $smdd -> appendChild($doc -> createTextNode($quoteproduct['sheetMaterialDirectionDesc']));
                $b -> appendChild($smdd);
            }

            $qi = $doc -> createElement("quoteImage");
            //image link as found from the website
            $qi -> appendChild($doc -> createTextNode($quoteproduct['quoteImage']));
            $b -> appendChild($qi);

            $si = $doc -> createElement("swatchImage");
            //image name from zip file
            $si -> appendChild($doc -> createTextNode($quoteproduct['swatchImage']));
            $b -> appendChild($si);

            $sifs = $doc -> createElement("swatchImageFullSize");
            $sifs -> appendChild($doc -> createTextNode($quoteproduct['swatchImageFullSize']));
            $b -> appendChild($sifs);

            if ($in_xml == "SimpleFab.xml") {//just a repeat of swatchimage?
                $sifs = $doc -> createElement("cutImage");
                $sifs -> appendChild($doc -> createTextNode($quoteproduct['swatchImage']));
                $b -> appendChild($sifs);
            }

            $pc = $doc -> createElement("productCode");
            $pc -> appendChild($doc -> createTextNode($quoteproduct['productCode']));
            $b -> appendChild($pc);

            $uprice = $doc -> createElement("unitPrice");
            $uprice -> appendChild($doc -> createTextNode($quoteproduct['unitPrice']));
            $b -> appendChild($uprice);

            if ($quoteproduct['pline'] == "1") {//Varia

                //layers
                $layers = $doc -> createElement("layers");
                $b -> appendChild($layers);

                //LAYER 1 - CUSTOM FRONT FINISH: (premium)
                $layer1 = $doc -> createElement("layer");
                $layers -> appendChild($layer1);

                $el = $doc -> createElement("element");
                $layer1 -> appendChild($el);

                $customFrontFinish = $doc -> createElement("customFrontFinish");
                $el -> appendChild($customFrontFinish);

                $this -> premiumfinishesarr = getspecialfinishes("premium");
                if (in_array($quoteproduct['ff_code'], $this -> premiumfinishesarr)) {
                    $customFrontFinishtext = $quoteproduct['FrontFinishDescription'];
                } else {
                    $customFrontFinishtext = "None";
                }

                $cusff = $doc -> createElement("id");
                $cusff -> appendChild($doc -> createTextNode($customFrontFinishtext));
                $customFrontFinish -> appendChild($cusff);

                //LAYER 2: STANDARD FRONT FINISH
                $layer2 = $doc -> createElement("layer");
                $layers -> appendChild($layer2);

                $el = $doc -> createElement("element");
                $layer2 -> appendChild($el);

                $standardFrontFinish = $doc -> createElement("standardFrontFinish");
                $el -> appendChild($standardFrontFinish);

                if (in_array($quoteproduct['ff_code'], $this -> premiumfinishesarr)) {
                    //get correct standard finish based on premium finish default
                    $defaultpff == "";
                    if ($quoteproduct['ff_code'] == "08") {//sfx frost > patina
                        $defaultpff = "449";
                        $pffid = "patina";
                    } elseif ($quoteproduct['ff_code'] == "09") {//markerboard plus > matte
                        $defaultpff = "446";
                        //should this be supermatte instead of matte?
                        $pffid = "matte";
                    } elseif ($quoteproduct['ff_code'] == "11") {//renewable patina > patina
                        $defaultpff = "449";
                        $pffid = "patina";
                    }
                    //add liquid finishes here if added to website. liquid finishes have "sandstone" as default, id 448

                    $q_rm = db_query("SELECT * FROM qt_raw_materials WHERE id='$defaultpff'");
                    //447 is patent paper
                    $r_rm = mysql_fetch_assoc($q_rm);

                    $id = $doc -> createElement("id");
                    $id -> appendChild($doc -> createTextNode($pffid));
                    $standardFrontFinish -> appendChild($id);

                    $mfgmat = $doc -> createElement("MfgMaterial");
                    $standardFrontFinish -> appendChild($mfgmat);

                    $descr = $doc -> createElement("description");
                    $descr -> appendChild($doc -> createTextNode($r_rm['description']));
                    $mfgmat -> appendChild($descr);

                    $pn = $doc -> createElement("partNumber");
                    $pn -> appendChild($doc -> createTextNode($r_rm['partnumber']));
                    $mfgmat -> appendChild($pn);

                    $qty = $doc -> createElement("quantity");
                    $qty -> appendChild($doc -> createTextNode($r_rm['qty']));
                    $mfgmat -> appendChild($qty);

                } elseif ($quoteproduct['texture_code'] != "") {

                    if ($quoteproduct['texture_code'] == "97" || $quoteproduct['texture_code'] == "98") {//corro exception
                        $texturefinish = "448";
                        //sandstone is used for corro
                    } else {
                        $texturefinish = "446";
                        //default texture finish = TEX PAP MATTE 51 inch (Finish Paper Matte)
                    }

                    //get finish paper matte
                    $q_rm1 = db_query("SELECT * FROM qt_raw_materials WHERE id='$texturefinish'");
                    $r_rm1 = mysql_fetch_assoc($q_rm1);

                    //get id
                    //$q_texturename = db_query("SELECT val FROM pid_key WHERE field = 'texture' AND code = '$quoteproduct[texture_code]'");
                    $q_texturename = db_query("SELECT name FROM products WHERE id = '$quoteproduct[texture_code]'");
                    $r_texturename = mysql_fetch_assoc($q_texturename);
                    $idtext = $r_texturename['name'] . " emboss";

                    $id = $doc -> createElement("id");
                    //texture name Emboss
                    $id -> appendChild($doc -> createTextNode("$idtext"));
                    $standardFrontFinish -> appendChild($id);

                    $mfgmat1 = $doc -> createElement("MfgMaterial");
                    $standardFrontFinish -> appendChild($mfgmat1);

                    $descr1 = $doc -> createElement("description");
                    $descr1 -> appendChild($doc -> createTextNode($r_rm1['description']));
                    $mfgmat1 -> appendChild($descr1);

                    $pn1 = $doc -> createElement("partNumber");
                    $pn1 -> appendChild($doc -> createTextNode($r_rm1['partnumber']));
                    $mfgmat1 -> appendChild($pn1);

                    $qty1 = $doc -> createElement("quantity");
                    $qty1 -> appendChild($doc -> createTextNode($r_rm1['qty']));
                    $mfgmat1 -> appendChild($qty1);

                    //If Corro, then add in special Corro FInish Fabric Parchment
                    if ($quoteproduct['texture_code'] == "97" || $quoteproduct['texture_code'] == "98") {//Corro Mezzo = 97; COrro mini = 98 (not active...)

                        $q_voile = db_query("SELECT * FROM qt_raw_materials WHERE partnumber='0-51-124' AND sheet_size_id='$quoteproduct[sheetdimension]'");
                        $r_voile = mysql_fetch_assoc($q_voile);

                        $mfgmatv = $doc -> createElement("MfgMaterial");
                        $standardFrontFinish -> appendChild($mfgmatv);

                        $descrv = $doc -> createElement("description");
                        $descrv -> appendChild($doc -> createTextNode($r_voile['description']));
                        $mfgmatv -> appendChild($descrv);

                        $pnv = $doc -> createElement("partNumber");
                        $pnv -> appendChild($doc -> createTextNode($r_voile['partnumber']));
                        $mfgmatv -> appendChild($pnv);

                        $qtyv = $doc -> createElement("quantity");
                        $qtyv -> appendChild($doc -> createTextNode($r_voile['qty']));
                        $mfgmatv -> appendChild($qtyv);
                    }

                    //get soft coat
                    $q_rm2 = db_query("SELECT * FROM qt_raw_materials WHERE partnumber='0-20-200' AND sheet_size_id='$quoteproduct[sheetdimension]'");
                    //SOFT COAT
                    $r_rm2 = mysql_fetch_assoc($q_rm2);

                    $mfgmat2 = $doc -> createElement("MfgMaterial");
                    $standardFrontFinish -> appendChild($mfgmat2);

                    $descr2 = $doc -> createElement("description");
                    $descr2 -> appendChild($doc -> createTextNode($r_rm2['description']));
                    $mfgmat2 -> appendChild($descr2);

                    $pn2 = $doc -> createElement("partNumber");
                    $pn2 -> appendChild($doc -> createTextNode($r_rm2['partnumber']));
                    $mfgmat2 -> appendChild($pn2);

                    $qty2 = $doc -> createElement("quantity");
                    $qty2 -> appendChild($doc -> createTextNode($r_rm2['qty']));
                    $mfgmat2 -> appendChild($qty2);

                } else {

                    //get raw materials for selected finish
                    $q_rm = db_query("SELECT * FROM qt_raw_materials WHERE product_id='$quoteproduct[ff_code]' AND element_type = 'finish'");
                    $r_rm = mysql_fetch_assoc($q_rm);

                    $id = $doc -> createElement("id");
                    $id -> appendChild($doc -> createTextNode($quoteproduct['FrontFinishDescription']));
                    $standardFrontFinish -> appendChild($id);

                    $mfgmat = $doc -> createElement("MfgMaterial");
                    $standardFrontFinish -> appendChild($mfgmat);

                    $descr = $doc -> createElement("description");
                    $descr -> appendChild($doc -> createTextNode($r_rm['description']));
                    //
                    $mfgmat -> appendChild($descr);

                    $pn = $doc -> createElement("partNumber");
                    $pn -> appendChild($doc -> createTextNode($r_rm['partnumber']));
                    $mfgmat -> appendChild($pn);

                    $qty = $doc -> createElement("quantity");
                    $qty -> appendChild($doc -> createTextNode($r_rm['qty']));
                    $mfgmat -> appendChild($qty);
                }

                $getlayers_arr = array();
                //$firephp->warn($quoteproduct['texture_code'], "TEXTURE CODE");
                $getlayers_arr = getlayers($quoteproduct['texture_code'], $quoteproduct['product_codes']);
                //$getlayers_arr = $this->getlayers($quoteproduct['texture_code'], $quoteproduct['product_codes']);
                $firephp->warn($getlayers_arr,"LAYER ARRAY");
                $index = 0;

                //LAYER 3 - FRONT PURE COLOR: (must have pure color, then some other product, or pure colors go on back)
                //$firephp->warn ($getlayers_arr['hasfrontpc'], "HAS FRONT PC");
                if ($getlayers_arr['hasfrontpc'] == true) {
                    $layer3 = $doc -> createElement("layer");
                    $layers -> appendChild($layer3);

                    $pcfid = $doc -> createElement("id");
                    $pcfid -> appendChild($doc -> createTextNode("TFM_Layer_PureColorFrontType"));
                    $layer3 -> appendChild($pcfid);

                    $pcfindex = $doc -> createElement("index");
                    $pcfindex -> appendChild($doc -> createTextNode($index));
                    $layer3 -> appendChild($pcfindex);

                    $el3 = $doc -> createElement("element");
                    $layer3 -> appendChild($el3);

                    //turn space deliminated films into array
                    $frontpc_arr = explode(" ", $getlayers_arr['frontpc']);
                    foreach ($frontpc_arr as $indexpc => $key) {

                        //get colors
                        $q_prodname = db_query("SELECT name FROM products WHERE id='$key'");
                        $r_prodname = mysql_fetch_assoc($q_prodname);

                        //get pidcode to get qt_raw_materials
                        $pid_code = convertPIDField("color", "", $r_prodname['name']);

                        //raw materials
                        //mail("ict@3form.eu", "pid_code in qt_xmlclass", $pid_code);
                        $q_qrm = db_query("SELECT * FROM qt_raw_materials WHERE product_id='$pid_code' 
						AND sheet_size_id='$quoteproduct[sheetdimension]' AND element_type='color'");
                        $r_qrm = mysql_fetch_assoc($q_qrm);

                        //add to xml
                        $pcmat = $doc -> createElement("pureColorMaterial");
                        $el3 -> appendChild($pcmat);

                        $descrpcm = $doc -> createElement("description");
                        $descrpcm -> appendChild($doc -> createTextNode($r_qrm['description']));
                        //
                        $pcmat -> appendChild($descrpcm);

                        $pnpcm = $doc -> createElement("partNumber");
                        $pnpcm -> appendChild($doc -> createTextNode($r_qrm['partnumber']));
                        $pcmat -> appendChild($pnpcm);

                        $qtypcm = $doc -> createElement("quantity");
                        $qtypcm -> appendChild($doc -> createTextNode($r_qrm['qty']));
                        $pcmat -> appendChild($qtypcm);
                    }

                    $index++;
                }

                //LAYER 4 - FRONTFACE - TFM_FRONTFACE_VARIATYPE
                $prod_family = $gname = "";

                //get family, gname for family/prodgroups exceptions
                if ($getlayers_arr['product'] != "") {
                    $q_prodfam = db_query("SELECT family, gname FROM products WHERE id='$getlayers_arr[product]'");
                    $r_prodfam = mysql_fetch_assoc($q_prodfam);

                    $prod_family = $r_prodfam['family'];
                    $gname = $r_prodfam['gname'];
                    //used for graphics
                }

                if ($prod_family == "hollywood") {
                    $hollywoodlayer = 0;
                }

                $layer4 = $doc -> createElement("layer");
                $layers -> appendChild($layer4);

                $faceid = $doc -> createElement("id");
                $faceid -> appendChild($doc -> createTextNode("TFM_FrontFace_VariaType"));
                $layer4 -> appendChild($faceid);

                $faceindex = $doc -> createElement("index");
                $faceindex -> appendChild($doc -> createTextNode($index));
                $layer4 -> appendChild($faceindex);

                $el4 = $doc -> createElement("element");
                $layer4 -> appendChild($el4);

                $variamold = $doc -> createElement("variaMold");
                $el4 -> appendChild($variamold);

                if ($quoteproduct['texture_code'] != "") {

                    //convert to pidkey
                    $pid_code = $this -> convert_to_pidkey("texture", $quoteproduct['texture_code']);
                    //get mold (i.e. texture) raw materials
                    $moldquery = "SELECT * FROM qt_raw_materials WHERE product_id='$pid_code' 
					AND sheet_size_id='$quoteproduct[sheetdimension]' AND element_type='texture'";
                    $q_mold = db_query($moldquery);
                    //get UV
                    $r_mold = mysql_fetch_assoc($q_mold);

                    $molddescr = $doc -> createElement("description");
                    $molddescr -> appendChild($doc -> createTextNode($r_mold['description']));
                    //
                    $variamold -> appendChild($molddescr);

                    $moldpn = $doc -> createElement("partNumber");
                    $moldpn -> appendChild($doc -> createTextNode($r_mold['partnumber']));
                    $variamold -> appendChild($moldpn);

                    $moldqty = $doc -> createElement("quantity");
                    $moldqty -> appendChild($doc -> createTextNode($r_mold['qty']));
                    $variamold -> appendChild($moldqty);
                } else {

                    //set blank defaults
                    $molddescr = $doc -> createElement("description");
                    $molddescr -> appendChild($doc -> createTextNode("No Mold"));
                    //
                    $variamold -> appendChild($molddescr);

                    $moldpn = $doc -> createElement("partNumber");
                    $moldpn -> appendChild($doc -> createTextNode("NoPartNumber"));
                    $variamold -> appendChild($moldpn);

                    $moldqty = $doc -> createElement("quantity");
                    $moldqty -> appendChild($doc -> createTextNode("0.0"));
                    $variamold -> appendChild($moldqty);
                }

                //add Front UV protection
                if ($quoteproduct['UV_Protection'] == "Protected") {
                    $uvface = $doc -> createElement("element");
                    $layer4 -> appendChild($uvface);

                    $uvfacepetg = $doc -> createElement("PETG");
                    $uvface -> appendChild($uvfacepetg);

                    //raw materials
                    $q_uvf = db_query("SELECT * FROM qt_raw_materials WHERE partnumber='0-10-001' 
					AND sheet_size_id='$quoteproduct[sheetdimension]' AND element_type='uv'");
                    //get UV
                    $r_uvf = mysql_fetch_assoc($q_uvf);

                    $uvfacegauge = $doc -> createElement("gauge");
                    $uvfacegauge -> appendChild($doc -> createTextNode($r_uvf['extra']));
                    //
                    $uvfacepetg -> appendChild($uvfacegauge);

                    $uvfacedescr = $doc -> createElement("description");
                    $uvfacedescr -> appendChild($doc -> createTextNode($r_uvf['description']));
                    //
                    $uvfacepetg -> appendChild($uvfacedescr);

                    $uvfacepn = $doc -> createElement("partNumber");
                    $uvfacepn -> appendChild($doc -> createTextNode($r_uvf['partnumber']));
                    $uvfacepetg -> appendChild($uvfacepn);

                    $uvfaceqty = $doc -> createElement("quantity");
                    $uvfaceqty -> appendChild($doc -> createTextNode($r_uvf['qty']));
                    $uvfacepetg -> appendChild($uvfaceqty);
                }

                //add first layer PETG
                $uv = ($quoteproduct['UV_Protection'] == "Protected" ? "1" : "0");
                $num_fabrics = $getlayers_arr['num_fabrics'];
                //If there is a product, count all fabrics.  If the texture is Corro, count all fabrics Texture exception).  Otherwise, don't double count so subtract one.
                if ($getlayers_arr['product'] == "" && $getlayers_arr['num_fabrics'] > 0 && ($quoteproduct['texture_code'] != "97" && $quoteproduct['texture_code'] != "98")) {
                    $num_fabrics = $getlayers_arr['num_fabrics'] - 1;
                    //fabrics do not count themselves on the first fabric if there is more than one
                }
                //			else{
                //				$num_fabrics = $getlayers_arr['num_fabrics'];
                //			}
                $layup_query = "SELECT * FROM qt_layup WHERE layup_id='$getlayers_arr[layupid]' AND uv='$uv' AND num_add_fabrics = '$num_fabrics'
				AND num_main_interlayer='$getlayers_arr[num_layers]' AND num_purecolor='$getlayers_arr[num_colors]' AND gauge='$quoteproduct[gauge_code]'";
				$firephp->warn($layup_query,"LAYUP QUERY");
                $q_petglayers = db_query($layup_query);
                //TODO material density? product density
                $r_petglayers = mysql_fetch_assoc($q_petglayers);

                //this is how to make the current sheet.  a result of -1 means it is not used
                //$front_petg1 = $r_petglayers['front_petg_1'];

                $petg_arr = array("front_petg1" => $r_petglayers['front_petg_1'], "front_petg2" => $r_petglayers['front_petg_2'], "petg1" => $r_petglayers['petg_1'], "petg2" => $r_petglayers['petg_2'], "back_petg1" => $r_petglayers['back_petg_1'], "back_petg2" => $r_petglayers['back_petg_2']);

                //remove non-sheets from off the front of array, at most this is the first two "front" petg sheets
                if ($petg_arr['front_petg1'] == "-1") {

                    $throwaway1 = array_shift($petg_arr);

                    if ($petg_arr['front_petg2'] == "-1") {
                        $throwaway2 = array_shift($petg_arr);
                    }
                }

                //check to see if graphics and if the first layer is not set.  if this is true, then the petg1 layer is graphics
                //graphic is a product that acts like a PETG (or is it the other way around?), so it can't be double used as both
                if ($gname == "8" && !isset($petg_arr['front_petg1']) && $prod_family != "strata") {//8 = gname graphics
                    $pidkey_prod = $this -> convert_to_pidkey("product", $getlayers_arr['graphic']);
                    $graphicquery = "SELECT * FROM qt_raw_materials WHERE sheet_size_id='$quoteproduct[sheetdimension]' AND product_id='$pidkey_prod'";
                    $q_petgf1 = db_query($graphicquery);

                    //remove first petg sheet since it won't be used
                    $first = array_shift($petg_arr);
                } else {
                    //get first petg sheet for use
                    $first = array_shift($petg_arr);

                    $q_petgf1 = db_query("SELECT * FROM qt_raw_materials WHERE sheet_size_id='$quoteproduct[sheetdimension]' AND element_type='petg_$first'");
                    //$front_petg1
                }

                //convert petg size to rawmaterials
                //**#252, this PETG is extra when reflec is used
                //if ($prod_family != "reflect"){
                    $r_petgf1 = mysql_fetch_assoc($q_petgf1);
    
                    //Front_petg 1
                    $fpetg1el = $doc -> createElement("element");
                    $layer4 -> appendChild($fpetg1el);
    
                    $fpetg1 = $doc -> createElement("PETG");
                    $fpetg1el -> appendChild($fpetg1);
    
                    $fpetg1gauge = $doc -> createElement("gauge");
                    $fpetg1gauge -> appendChild($doc -> createTextNode($r_petgf1['extra']));
                    //
                    $fpetg1 -> appendChild($fpetg1gauge);
    
                    $fpetg1descr = $doc -> createElement("description");
                    $fpetg1descr -> appendChild($doc -> createTextNode($r_petgf1['description']));
                    //." $graphicquery $gname $petg_arr[front_petg_1]"   ." : ".$layup_query
                    $fpetg1 -> appendChild($fpetg1descr);
    
                    $fpetg1pn = $doc -> createElement("partNumber");
                    $fpetg1pn -> appendChild($doc -> createTextNode($r_petgf1['partnumber']));
                    //." t1:$throwaway1 t2:$throwaway2 f:$first"
                    $fpetg1 -> appendChild($fpetg1pn);
    
                    $fpetg1qty = $doc -> createElement("quantity");
                    $fpetg1qty -> appendChild($doc -> createTextNode($r_petgf1['qty']));
                    $fpetg1 -> appendChild($fpetg1qty);
    
                    $index++;
                    //increment index number
                //}

                //LAYERS 5,6,7 - TFM_LAYER_VARIATYPE
                $layerpetg = "";

                //get rid of -1, meaning that sheet is not used.  only cycle through actual petg sheets
                $actual_petg_arr = array();
                foreach ($petg_arr as $key => $value) {
                    if ($value == "-1") {// -1 means it is not used, so remove
                        continue;
                    } else {
                        $actual_petg_arr[$key] = $value;
                    }
                }

                $buildorder_arr = explode(" ", $getlayers_arr['productbuildorder']);
                //get the layers and populate the xml in correct order

                $layercount = 0;
				$firephp->warn($actual_petg_arr,"PETG ARRAY");
                foreach ($actual_petg_arr as $layer => $petgsheet) {
                	$firephp->warn($layer."-".$petgsheet,"PETG ARRAY LOOPED");
                    //				if($petgsheet == "-1"){// -1 means it is not used, so move to the next layer
                    //					continue;
                    //				}

                    //build current layer
                    $layerpetg = $doc -> createElement("layer");
                    $layers -> appendChild($layerpetg);

                    $petgid = $doc -> createElement("id");
                    $petgid -> appendChild($doc -> createTextNode("TFM_Layer_VariaType"));
                    $layerpetg -> appendChild($petgid);

                    $petgindex = $doc -> createElement("index");
                    $petgindex -> appendChild($doc -> createTextNode($index));
                    $layerpetg -> appendChild($petgindex);

					if (count($buildorder_arr) > 0 && $buildorder_arr[0] != "" && $prod_family != "wood" && $prod_family != "dichroic" && $prod_family != "hollywood") {
                    	
                        //Graphic exception for first two layers - Printed PETG is always PETG1
                        if ($gname == "8" && ($layer == "front_petg2" || $layer == "petg1" || $layer == "petg2") && $prod_family != "strata") {//8 = gname graphics
                            //do nothing
                        } else {//proceed normally

                            //get current product and convert to pid
                            $thislayerprod = array_shift($buildorder_arr);
                            $pidkey_prod = $this -> convert_to_pidkey("product", $thislayerprod);

                            //get rawmaterials	//TODO it's easier to find this query for testing with this TODO here
                            $rawmatquery = "SELECT * FROM qt_raw_materials WHERE sheet_size_id='$quoteproduct[sheetdimension]' AND product_id='$pidkey_prod'";
                            $q_variamat = db_query($rawmatquery);
                            $r_variamat = mysql_fetch_assoc($q_variamat);

                            if ($gname == "23") {//highres

                                //for simplefab, find specific hr raw material id
                                if ($in_xml == "SimpleFab.xml") {

                                    foreach ($this->sheetpanels_arr as $outerarraykey => $innerarray) {

                                        if ($outerarraykey == $this -> sheetuid_value) {

                                            foreach ($innerarray as $panelid => $panelvalues) {

                                                //$r_variamat['partnumber'] = $panelvalues['panelid'];

                                                $panelid_arr = explode(":", $panelvalues['panelid']);
                                                $quoteprodid = $panelid_arr[0];
                                                $panel = $panelid_arr[1];

                                                $hrquery = "SELECT id, process_group, fab_detail_2, panel_id FROM qt_fab_products WHERE quoteproduct_id='$quoteprodid' 
												AND fab_category='highres' AND active='1'";
                                                $q_hr = db_query($hrquery);
                                                while ($r_hr = mysql_fetch_assoc($q_hr)) {

                                                    //first find out if group processed
                                                    $processgroup = ($r_hr['process_group'] == "1" ? true : false);

                                                    //panel bump to compensate for 1 = 0 on panel_id
                                                    //$panelbump = ($panel) + 1;
                                                    $panelbump = $panel;
                                                    //300712 - Romain updated panel to start with 1.  it's no longer base 0.

                                                    //then find specific fab_detail_2 (either "0" or quoteprodid + 1)
                                                    if ($processgroup == true && $r_hr['panel_id'] == $quoteprodid . "_0") {
                                                        $r_variamat['partnumber'] = $r_hr['fab_detail_2'];
                                                        break;
                                                    } elseif ($processgroup == false && $r_hr['panel_id'] == $quoteprodid . "_$panelbump") {
                                                        $r_variamat['partnumber'] = $r_hr['fab_detail_2'];
                                                        //." . $r_hr[id] => ".$panelvalues['panelid']
                                                        break;
                                                    }

                                                }

                                            }
                                        }
                                    }

                                    //$quoteproduct['FinishName']

                                } else {//for icepack.xml, put in note to go to simplefab.  HR raw materials are not 1:1, so they don't work in icepack.xml.
                                    $r_variamat['partnumber'] = "See Simplefab.xml";
                                }
                            }
							//**#252, Reflect specifics, add EVA around it
							if ($r_variamat['description']=="reflect"){
								
	                            //get current product and convert to pid
	                            $thislayerprod = array_shift($buildorder_arr);
	                            $pidkey_prod = $this -> convert_to_pidkey("product", $thislayerprod);
	
	                            //get rawmaterials for tpu //jjh 12/12/11 Switched TPU for EVA per Manuela van Boxel
	                            $firephp->warn("PUT EVA FILM");
	                            $q_variaspecialtpu = db_query("SELECT * FROM qt_raw_materials WHERE
								sheet_size_id='$quoteproduct[sheetdimension]' AND partnumber='4-10-024'");
	                            //4-10-024 is EVA, not TPU
	                            $r_variaspecialtpu = mysql_fetch_assoc($q_variaspecialtpu);
	
	                            //TPU layer 1
	                            $prodeltpu1 = $doc -> createElement("element");
	                            $layerpetg -> appendChild($prodeltpu1);
	
	                            $variaspecialtpu1 = $doc -> createElement("variaMaterialInvisible");
	                            $prodeltpu1 -> appendChild($variaspecialtpu1);
	
	                            $variaspecialdescrtpu1 = $doc -> createElement("description");
	                            $variaspecialdescrtpu1 -> appendChild($doc -> createTextNode($r_variaspecialtpu['description']));
	                            $variaspecialtpu1 -> appendChild($variaspecialdescrtpu1);
	
	                            $variaspecialpntpu1 = $doc -> createElement("partNumber");
	                            $variaspecialpntpu1 -> appendChild($doc -> createTextNode($r_variaspecialtpu['partnumber']));
	                            $variaspecialtpu1 -> appendChild($variaspecialpntpu1);
	
	                            $variaspecialqtytpu1 = $doc -> createElement("quantity");
	                            $variaspecialqtytpu1 -> appendChild($doc -> createTextNode($r_variaspecialtpu['qty']));
	                            $variaspecialtpu1 -> appendChild($variaspecialqtytpu1);
	
	                            //get rawmaterials for special layer
	                            /*$q_variaspecial = db_query("SELECT * FROM qt_raw_materials WHERE 
								sheet_size_id='$quoteproduct[sheetdimension]' AND product_id='$pidkey_prod'");
	                            $r_variaspecial = mysql_fetch_assoc($q_variaspecial);*/
	
	                            //product layer
	                            $prodelspecial = $doc -> createElement("element");
	                            $layerpetg -> appendChild($prodelspecial);
	
	                            $variaspecialmat = $doc -> createElement("variaMaterial");
	                            $prodelspecial -> appendChild($variaspecialmat);
	
	                            $variaspecialdescr = $doc -> createElement("description");
	                            $variaspecialdescr -> appendChild($doc -> createTextNode($r_variamat['description']));
	                            $variaspecialmat -> appendChild($variaspecialdescr);
	
	                            $variaspecialpn = $doc -> createElement("partNumber");
	                            $variaspecialpn -> appendChild($doc -> createTextNode($r_variamat['partnumber']));
	                            $variaspecialmat -> appendChild($variaspecialpn);
	
	                            $variaspecialqty = $doc -> createElement("quantity");
	                            $variaspecialqty -> appendChild($doc -> createTextNode($r_variamat['qty']));
	                            $variaspecialmat -> appendChild($variaspecialqty);
	
	                            //TPU layer 2
	                            $prodeltpu2 = $doc -> createElement("element");
	                            $layerpetg -> appendChild($prodeltpu2);
	
	                            $variaspecialtpu2 = $doc -> createElement("variaMaterialInvisible");
	                            $prodeltpu2 -> appendChild($variaspecialtpu2);
	
	                            $variaspecialdescrtpu2 = $doc -> createElement("description");
	                            $variaspecialdescrtpu2 -> appendChild($doc -> createTextNode($r_variaspecialtpu['description']));
	                            $variaspecialtpu2 -> appendChild($variaspecialdescrtpu2);
	
	                            $variaspecialpntpu2 = $doc -> createElement("partNumber");
	                            $variaspecialpntpu2 -> appendChild($doc -> createTextNode($r_variaspecialtpu['partnumber']));
	                            $variaspecialtpu2 -> appendChild($variaspecialpntpu2);
	
	                            $variaspecialqtytpu2 = $doc -> createElement("quantity");
	                            $variaspecialqtytpu2 -> appendChild($doc -> createTextNode($r_variaspecialtpu['qty']));
	                            $variaspecialtpu2 -> appendChild($variaspecialqtytpu2);
                        		
							}else{
							
							
	                            //product layer
	                            $prodel = $doc -> createElement("element");
	                            $layerpetg -> appendChild($prodel);
	
	                            //some products have more than one part number, so cycle through parts (95% or so only have one part)
	                            $partnumber = explode(",", $r_variamat['partnumber']);
	                            $qty = explode(",", $r_variamat['qty']);
	                            $count = 0;
	                            while (count($partnumber) > $count) {
	                                $variamat = $doc -> createElement("variaMaterial");
	                                $prodel -> appendChild($variamat);
	
	                                $variadescr = $doc -> createElement("description");
	                                $variadescr -> appendChild($doc -> createTextNode($r_variamat['description']));
	                                //. " : $rawmatquery"
	                                $variamat -> appendChild($variadescr);
	
	                                $variapn = $doc -> createElement("partNumber");
	                                $variapn -> appendChild($doc -> createTextNode($partnumber[$count]));
	                                //$r_variamat['partnumber']
	                                $variamat -> appendChild($variapn);
	
	                                $variaqty = $doc -> createElement("quantity");
	                                $variaqty -> appendChild($doc -> createTextNode($qty[$count]));
	                                //$r_variamat['qty']
	                                $variamat -> appendChild($variaqty);
	
	                                $count++;
	                            }
                            }

                            //invisible material
                           //if ($getlayers_arr['product'] != "330" && $getlayers_arr['product'] != "331"){
                               //**Bamboo ring light/dark are pre-glued on BBA, so no need to add BBA
                                
                                if ($getlayers_arr['invisiblemat'] != "" && $getlayers_arr['invisibleqty'] > 0 && $thislayerprod == $getlayers_arr['product']) {
                                    for ($x = 1; $x <= $getlayers_arr['invisibleqty']; $x++) {
    
                                        //get rawmaterials
                                        $q_invmat = db_query("SELECT * FROM qt_raw_materials WHERE 
    									sheet_size_id='$quoteproduct[sheetdimension]' AND product_id='$getlayers_arr[invisiblemat]'");
                                        $r_invmat = mysql_fetch_assoc($q_invmat);
    
                                        //product layer
                                        $invel = $doc -> createElement("element");
                                        $layerpetg -> appendChild($invel);
    
                                        $invmat = $doc -> createElement("variaMaterialInvisible");
                                        $invel -> appendChild($invmat);
    
                                        $invdescr = $doc -> createElement("description");
                                        $invdescr -> appendChild($doc -> createTextNode($r_invmat['description']));
                                        $invmat -> appendChild($invdescr);
    
                                        $invpn = $doc -> createElement("partNumber");
                                        $invpn -> appendChild($doc -> createTextNode($r_invmat['partnumber']));
                                        $invmat -> appendChild($invpn);
    
                                        $invqty = $doc -> createElement("quantity");
                                        $invqty -> appendChild($doc -> createTextNode($r_invmat['qty']));
                                        $invmat -> appendChild($invqty);
                                    }
                                }
                            //}

                            //CAPIZ and Weave after BBA. This is an exception to normal layup which puts fabrics between petg layers
                            if ($getlayers_arr['hasfabric'] && $prod_family == "capiz") {

                                //get current product and convert to pid
                                $thislayerprod = array_shift($buildorder_arr);
                                $pidkey_prod = $this -> convert_to_pidkey("product", $thislayerprod);

                                //get rawmaterials
                                $q_capizweave = db_query("SELECT * FROM qt_raw_materials WHERE 
								sheet_size_id='$quoteproduct[sheetdimension]' AND product_id='$pidkey_prod'");
                                $r_capizweave = mysql_fetch_assoc($q_capizweave);

                                //product layer
                                $prodelcw = $doc -> createElement("element");
                                $layerpetg -> appendChild($prodelcw);

                                $variamatcw = $doc -> createElement("variaMaterial");
                                $prodelcw -> appendChild($variamatcw);

                                $variadescrcw = $doc -> createElement("description");
                                $variadescrcw -> appendChild($doc -> createTextNode($r_capizweave['description']));
                                $variamatcw -> appendChild($variadescrcw);

                                $variapncw = $doc -> createElement("partNumber");
                                $variapncw -> appendChild($doc -> createTextNode($r_capizweave['partnumber']));
                                $variamatcw -> appendChild($variapncw);

                                $variaqtycw = $doc -> createElement("quantity");
                                $variaqtycw -> appendChild($doc -> createTextNode($r_capizweave['qty']));
                                $variamatcw -> appendChild($variaqtycw);
                            }
                        }

                    } elseif ($prod_family == "wood" || $prod_family == "dichroic") {//perhaps this can be added to above.  add tpu to invisible_mat in products
						/*
						 * This section adds EVA to the layup 
						 */
						 
                        //if($firstlayer == false && $putspeciallayer == false){
                        if (count($actual_petg_arr) < 2 || $layercount % 2 == 1) {

                            //get current product and convert to pid
                            $thislayerprod = array_shift($buildorder_arr);
                            $pidkey_prod = $this -> convert_to_pidkey("product", $thislayerprod);

                            //get rawmaterials for tpu //jjh 12/12/11 Switched TPU for EVA per Manuela van Boxel
                            $firephp->warn("PUT EVA FILM");
                            $q_variaspecialtpu = db_query("SELECT * FROM qt_raw_materials WHERE
							sheet_size_id='$quoteproduct[sheetdimension]' AND partnumber='4-10-024'");
                            //4-10-024 is EVA, not TPU
                            $r_variaspecialtpu = mysql_fetch_assoc($q_variaspecialtpu);

                            //TPU layer 1
                            $prodeltpu1 = $doc -> createElement("element");
                            $layerpetg -> appendChild($prodeltpu1);

                            $variaspecialtpu1 = $doc -> createElement("variaMaterialInvisible");
                            $prodeltpu1 -> appendChild($variaspecialtpu1);

                            $variaspecialdescrtpu1 = $doc -> createElement("description");
                            $variaspecialdescrtpu1 -> appendChild($doc -> createTextNode($r_variaspecialtpu['description']));
                            $variaspecialtpu1 -> appendChild($variaspecialdescrtpu1);

                            $variaspecialpntpu1 = $doc -> createElement("partNumber");
                            $variaspecialpntpu1 -> appendChild($doc -> createTextNode($r_variaspecialtpu['partnumber']));
                            $variaspecialtpu1 -> appendChild($variaspecialpntpu1);

                            $variaspecialqtytpu1 = $doc -> createElement("quantity");
                            $variaspecialqtytpu1 -> appendChild($doc -> createTextNode($r_variaspecialtpu['qty']));
                            $variaspecialtpu1 -> appendChild($variaspecialqtytpu1);

                            //get rawmaterials for special layer
                            $q_variaspecial = db_query("SELECT * FROM qt_raw_materials WHERE 
							sheet_size_id='$quoteproduct[sheetdimension]' AND product_id='$pidkey_prod'");
                            $r_variaspecial = mysql_fetch_assoc($q_variaspecial);

                            //product layer
                            $prodelspecial = $doc -> createElement("element");
                            $layerpetg -> appendChild($prodelspecial);

                            $variaspecialmat = $doc -> createElement("variaMaterial");
                            $prodelspecial -> appendChild($variaspecialmat);

                            $variaspecialdescr = $doc -> createElement("description");
                            $variaspecialdescr -> appendChild($doc -> createTextNode($r_variaspecial['description']));
                            $variaspecialmat -> appendChild($variaspecialdescr);

                            $variaspecialpn = $doc -> createElement("partNumber");
                            $variaspecialpn -> appendChild($doc -> createTextNode($r_variaspecial['partnumber']));
                            $variaspecialmat -> appendChild($variaspecialpn);

                            $variaspecialqty = $doc -> createElement("quantity");
                            $variaspecialqty -> appendChild($doc -> createTextNode($r_variaspecial['qty']));
                            $variaspecialmat -> appendChild($variaspecialqty);

                            //TPU layer 2
                            $prodeltpu2 = $doc -> createElement("element");
                            $layerpetg -> appendChild($prodeltpu2);

                            $variaspecialtpu2 = $doc -> createElement("variaMaterialInvisible");
                            $prodeltpu2 -> appendChild($variaspecialtpu2);

                            $variaspecialdescrtpu2 = $doc -> createElement("description");
                            $variaspecialdescrtpu2 -> appendChild($doc -> createTextNode($r_variaspecialtpu['description']));
                            $variaspecialtpu2 -> appendChild($variaspecialdescrtpu2);

                            $variaspecialpntpu2 = $doc -> createElement("partNumber");
                            $variaspecialpntpu2 -> appendChild($doc -> createTextNode($r_variaspecialtpu['partnumber']));
                            $variaspecialtpu2 -> appendChild($variaspecialpntpu2);

                            $variaspecialqtytpu2 = $doc -> createElement("quantity");
                            $variaspecialqtytpu2 -> appendChild($doc -> createTextNode($r_variaspecialtpu['qty']));
                            $variaspecialtpu2 -> appendChild($variaspecialqtytpu2);
                        }

                    } elseif ($prod_family == "hollywood") {

                        //all three layers are hollywood
                        $thislayerprod = $getlayers_arr['product'];

                        if ($hollywoodlayer == 0 || $hollywoodlayer == 2) {
                            //get current product and convert to pid

                            $pidkey_prod = $this -> convert_to_pidkey("product", $thislayerprod);

                            //get rawmaterials
                            $q_variamat = db_query("SELECT * FROM qt_raw_materials WHERE 
							sheet_size_id='$quoteproduct[sheetdimension]' AND product_id='$pidkey_prod'");
                            $r_variamat = mysql_fetch_assoc($q_variamat);

                            //product layer
                            $prodel = $doc -> createElement("element");
                            $layerpetg -> appendChild($prodel);

                            //some products have more than one part number, so cycle through parts (95% or so only have one part)
                            $partnumber = explode(",", $r_variamat['partnumber']);
                            $qty = explode(",", $r_variamat['qty']);
                            $count = 0;
                            while (count($partnumber) > $count) {
                                $variamat = $doc -> createElement("variaMaterial");
                                $prodel -> appendChild($variamat);

                                $variadescr = $doc -> createElement("description");
                                $variadescr -> appendChild($doc -> createTextNode($r_variamat['description']));
                                $variamat -> appendChild($variadescr);

                                $variapn = $doc -> createElement("partNumber");
                                $variapn -> appendChild($doc -> createTextNode($partnumber[$count]));
                                //$r_variamat['partnumber']
                                $variamat -> appendChild($variapn);

                                $variaqty = $doc -> createElement("quantity");
                                $variaqty -> appendChild($doc -> createTextNode($qty[$count]));
                                //$r_variamat['qty']
                                $variamat -> appendChild($variaqty);

                                $count++;
                            }
                        } elseif ($hollywoodlayer == 1) {

                            $pidkey_prod = $this -> convert_to_pidkey("product", $thislayerprod);

                            $pidkey_prod = $pidkey_prod . "b";
                            //get middle layer exception

                            //get rawmaterials
                            $q_variamat = db_query("SELECT * FROM qt_raw_materials WHERE 
							sheet_size_id='$quoteproduct[sheetdimension]' AND product_id='$pidkey_prod'");
                            $r_variamat = mysql_fetch_assoc($q_variamat);

                            //product layer
                            $prodel = $doc -> createElement("element");
                            $layerpetg -> appendChild($prodel);

                            //some products have more than one part number, so cycle through parts (95% or so only have one part)
                            $partnumber = explode(",", $r_variamat['partnumber']);
                            $qty = explode(",", $r_variamat['qty']);
                            $count = 0;
                            while (count($partnumber) > $count) {
                                $variamat = $doc -> createElement("variaMaterial");
                                $prodel -> appendChild($variamat);

                                $variadescr = $doc -> createElement("description");
                                $variadescr -> appendChild($doc -> createTextNode($r_variamat['description']));
                                $variamat -> appendChild($variadescr);

                                $variapn = $doc -> createElement("partNumber");
                                $variapn -> appendChild($doc -> createTextNode($partnumber[$count]));
                                //$r_variamat['partnumber']
                                $variamat -> appendChild($variapn);

                                $variaqty = $doc -> createElement("quantity");
                                $variaqty -> appendChild($doc -> createTextNode($qty[$count]));
                                //$r_variamat['qty']
                                $variamat -> appendChild($variaqty);

                                $count++;
                            }

                            //if there is a fabric, it replaced the BBA.  Otherwise, put in BBA
                            if ($getlayers_arr['hasfabric']) {

                                $pidkey_prod = $this -> convert_to_pidkey("product", $getlayers_arr['fabrics']);

                                //get rawmaterials
                                $q_variamat = db_query("SELECT * FROM qt_raw_materials WHERE 
								sheet_size_id='$quoteproduct[sheetdimension]' AND product_id='$pidkey_prod'");
                                $r_variamat = mysql_fetch_assoc($q_variamat);

                                //product layer
                                $prodel = $doc -> createElement("element");
                                $layerpetg -> appendChild($prodel);

                                //add to xml
                                $variamat = $doc -> createElement("variaMaterial");
                                $prodel -> appendChild($variamat);

                                $variadescr = $doc -> createElement("description");
                                $variadescr -> appendChild($doc -> createTextNode($r_variamat['description']));
                                $variamat -> appendChild($variadescr);

                                $variapn = $doc -> createElement("partNumber");
                                $variapn -> appendChild($doc -> createTextNode($r_variamat['partnumber']));
                                //
                                $variamat -> appendChild($variapn);

                                $variaqty = $doc -> createElement("quantity");
                                $variaqty -> appendChild($doc -> createTextNode($r_variamat['qty']));
                                //
                                $variamat -> appendChild($variaqty);

                            /*} else if ($getlayers_arr['product'] == "330" || $getlayers_arr['product'] == "331"){
                                //**Bamboo ring light/dark are pre-glued on BBA, so no need to add BBA
                                //**Do nothing    
                            }*/}else {

                                //get invisible materials, BBA
                                $q_invmat = db_query("SELECT * FROM qt_raw_materials WHERE 
								sheet_size_id='$quoteproduct[sheetdimension]' AND product_id='$getlayers_arr[invisiblemat]'");
                                $r_invmat = mysql_fetch_assoc($q_invmat);

                                $invel = $doc -> createElement("element");
                                $layerpetg -> appendChild($invel);

                                $invmat = $doc -> createElement("variaMaterialInvisible");
                                $invel -> appendChild($invmat);

                                $invdescr = $doc -> createElement("description");
                                $invdescr -> appendChild($doc -> createTextNode($r_invmat['description']));
                                $invmat -> appendChild($invdescr);

                                $invpn = $doc -> createElement("partNumber");
                                $invpn -> appendChild($doc -> createTextNode($r_invmat['partnumber']));
                                $invmat -> appendChild($invpn);

                                $invqty = $doc -> createElement("quantity");
                                $invqty -> appendChild($doc -> createTextNode($r_invmat['qty']));
                                $invmat -> appendChild($invqty);
                            }

                        }

                        $hollywoodlayer++;
                    }

                    //get Graphic PETG or regular PETG
                    if ($gname == "8" && $layer == "petg1" && $prod_family != "strata") {//8 = gname graphics
                        $pidkey_prod = $this -> convert_to_pidkey("product", $getlayers_arr['graphic']);
                        $graphicquery = "SELECT * FROM qt_raw_materials WHERE sheet_size_id='$quoteproduct[sheetdimension]' AND product_id='$pidkey_prod'";
                        $q_petg = db_query($graphicquery);
                    } else {
                        $q_petg = db_query("SELECT * FROM qt_raw_materials WHERE sheet_size_id='$quoteproduct[sheetdimension]' AND element_type='petg_$petgsheet'");
                    }

                    if ($prod_family != "strata" && $quoteproduct['product_codes'] != "190") {//Strata Agate is an exception within an exception - it has no back

                        //convert petg size to rawmaterials
                        $r_petg = mysql_fetch_assoc($q_petg);

                        //PETG
                        $petgel = $doc -> createElement("element");
                        $layerpetg -> appendChild($petgel);

                        $petg = $doc -> createElement("PETG");
                        $petgel -> appendChild($petg);

                        $petggauge = $doc -> createElement("gauge");
                        $petggauge -> appendChild($doc -> createTextNode($r_petg['extra']));
                        $petg -> appendChild($petggauge);

                        $petgdescr = $doc -> createElement("description");
                        $petgdescr -> appendChild($doc -> createTextNode($r_petg['description']));
                        $petg -> appendChild($petgdescr);

                        $petgpn = $doc -> createElement("partNumber");
                        $petgpn -> appendChild($doc -> createTextNode($r_petg['partnumber']));
                        $petg -> appendChild($petgpn);

                        $petgqty = $doc -> createElement("quantity");
                        $petgqty -> appendChild($doc -> createTextNode($r_petg['qty']));
                        $petg -> appendChild($petgqty);
                    }

                    $index++;
                    $layercount++;
                }

                //add UV protection
                if ($getlayers_arr['hasbackpc'] == false && $quoteproduct['UV_Protection'] == "Protected") {
                    $uvback = $doc -> createElement("element");
                    //**Romain, 10April2013, Ticket #881665 ,try to solve issue mentioned by Sylvia
                    //$layerpetg -> appendChild($uvback);
                    if (!$layerpetg){
                    	$layerpetg = $doc -> createElement("layer");
                    	$layerpetg -> appendChild($uvback);
					}else{
						$layerpetg -> appendChild($uvback);
					}

                    $uvpetg = $doc -> createElement("PETG");
                    $uvback -> appendChild($uvpetg);

                    //raw materials
                    $q_uvb = db_query("SELECT * FROM qt_raw_materials WHERE partnumber='0-10-001' 
					AND sheet_size_id='$quoteproduct[sheetdimension]' AND element_type='uv'");
                    //get UV
                    $r_uvb = mysql_fetch_assoc($q_uvb);

                    $uvbackgauge = $doc -> createElement("gauge");
                    $uvbackgauge -> appendChild($doc -> createTextNode($r_uvb['extra']));
                    //
                    $uvpetg -> appendChild($uvbackgauge);

                    $uvbackdescr = $doc -> createElement("description");
                    $uvbackdescr -> appendChild($doc -> createTextNode($r_uvb['description']));
                    //
                    $uvpetg -> appendChild($uvbackdescr);

                    $uvbackpn = $doc -> createElement("partNumber");
                    $uvbackpn -> appendChild($doc -> createTextNode($r_uvb['partnumber']));
                    $uvpetg -> appendChild($uvbackpn);

                    $uvbackqty = $doc -> createElement("quantity");
                    $uvbackqty -> appendChild($doc -> createTextNode($r_uvb['qty']));
                    $uvpetg -> appendChild($uvbackqty);
                }

                //special invisible material //add second layer of bba if birch (note: only for birch, birch pulse, and birch grove!!)
                if ($getlayers_arr['product'] == "299" || $getlayers_arr['product'] == "323" || $getlayers_arr['product'] == "599") {
                    for ($x = 1; $x <= $getlayers_arr['invisibleqty']; $x++) {

                        //get rawmaterials
                        $q_invmatextra = db_query("SELECT * FROM qt_raw_materials WHERE 
					sheet_size_id='$quoteproduct[sheetdimension]' AND product_id='$getlayers_arr[invisiblemat]'");
                        $r_invmatextra = mysql_fetch_assoc($q_invmatextra);

                        //product layer
                        $invelextra = $doc -> createElement("element");
                        $layerpetg -> appendChild($invelextra);

                        $invmatextra = $doc -> createElement("variaMaterialInvisible");
                        $invelextra -> appendChild($invmatextra);

                        $invdescrextra = $doc -> createElement("description");
                        $invdescrextra -> appendChild($doc -> createTextNode($r_invmat['description']));
                        $invmatextra -> appendChild($invdescrextra);

                        $invpnextra = $doc -> createElement("partNumber");
                        $invpnextra -> appendChild($doc -> createTextNode($r_invmat['partnumber']));
                        $invmatextra -> appendChild($invpnextra);

                        $invqtyextra = $doc -> createElement("quantity");
                        $invqtyextra -> appendChild($doc -> createTextNode($r_invmat['qty']));
                        $invmatextra -> appendChild($invqtyextra);
                    }
                }

                //LAYER 8 - PURE COLORS BACK: (default location for pure colors)
                if ($getlayers_arr['hasbackpc'] == true) {
                    $layer8 = $doc -> createElement("layer");
                    $layers -> appendChild($layer8);

                    $pcbid = $doc -> createElement("id");
                    $pcbid -> appendChild($doc -> createTextNode("TFM_Layer_PureColorBackType"));
                    $layer8 -> appendChild($pcbid);

                    $pcbindex = $doc -> createElement("index");
                    $pcbindex -> appendChild($doc -> createTextNode($index));
                    $layer8 -> appendChild($pcbindex);

                    $el8 = $doc -> createElement("element");
                    $layer8 -> appendChild($el8);

                    //turn space deliminated films into array
                    $backpc_arr = explode(" ", $getlayers_arr['backpc']);
                    foreach ($backpc_arr as $index => $value) {

                        //convert to pidkey
                        $bpid_code = $this -> convert_to_pidkey("color", $value);

                        //raw materials
                        $q_bqrm = db_query("SELECT * FROM qt_raw_materials WHERE product_id='$bpid_code' 
						AND sheet_size_id='$quoteproduct[sheetdimension]' AND element_type='color'");
                        $r_bqrm = mysql_fetch_assoc($q_bqrm);

                        //add to xml
                        $pcbmat = $doc -> createElement("pureColorMaterial");
                        $el8 -> appendChild($pcbmat);

                        $descrbpcm = $doc -> createElement("description");
                        $descrbpcm -> appendChild($doc -> createTextNode($r_bqrm['description']));
                        //
                        $pcbmat -> appendChild($descrbpcm);

                        $pnbpcm = $doc -> createElement("partNumber");
                        $pnbpcm -> appendChild($doc -> createTextNode($r_bqrm['partnumber']));
                        $pcbmat -> appendChild($pnbpcm);

                        $qtybpcm = $doc -> createElement("quantity");
                        $qtybpcm -> appendChild($doc -> createTextNode($r_bqrm['qty']));
                        //
                        $pcbmat -> appendChild($qtybpcm);
                    }

                    //add UV protection
                    if ($quoteproduct['UV_Protection'] == "Protected") {
                        $uvback = $doc -> createElement("element");
                        $layer8 -> appendChild($uvback);

                        $uvpetg = $doc -> createElement("PETG");
                        $uvback -> appendChild($uvpetg);

                        //raw materials
                        $q_uvb = db_query("SELECT * FROM qt_raw_materials WHERE partnumber='0-10-001' 
						AND sheet_size_id='$quoteproduct[sheetdimension]' AND element_type='uv'");
                        //get UV
                        $r_uvb = mysql_fetch_assoc($q_uvb);

                        $uvbackgauge = $doc -> createElement("gauge");
                        $uvbackgauge -> appendChild($doc -> createTextNode($r_uvb['extra']));
                        //
                        $uvpetg -> appendChild($uvbackgauge);

                        $uvbackdescr = $doc -> createElement("description");
                        $uvbackdescr -> appendChild($doc -> createTextNode($r_uvb['description']));
                        //
                        $uvpetg -> appendChild($uvbackdescr);

                        $uvbackpn = $doc -> createElement("partNumber");
                        $uvbackpn -> appendChild($doc -> createTextNode($r_uvb['partnumber']));
                        $uvpetg -> appendChild($uvbackpn);

                        $uvbackqty = $doc -> createElement("quantity");
                        $uvbackqty -> appendChild($doc -> createTextNode($r_uvb['qty']));
                        $uvpetg -> appendChild($uvbackqty);
                    }

                    $index++;
                }

                //LAYER 9 - STANDARD BACK FINISH
                $layer9 = $doc -> createElement("layer");
                $layers -> appendChild($layer9);

                $el = $doc -> createElement("element");
                $layer9 -> appendChild($el);

                $standardBackFinish = $doc -> createElement("standardBackFinish");
                $el -> appendChild($standardBackFinish);

                //get default backfinish if premium finish is used
                $bfid = "";
                $where = "";
                if (in_array($quoteproduct['bf_code'], $this -> premiumfinishesarr)) {
                    //get correct standard finish based on premium finish default
                    $defaultpff == "";
                    if ($quoteproduct['bf_code'] == "08") {//sfx frost > patina
                        $where = "id='449'";
                        $bfid = "patina";
                    } elseif ($quoteproduct['bf_code'] == "09") {//markerboard plus > matte
                        $where = "id='446'";
                        //should this be supermatte instead of matte?
                        $bfid = "matte";
                    } elseif ($quoteproduct['bf_code'] == "11") {//renewable patina > patina
                        $where = "id='449'";
                        $bfid = "patina";
                    }
                    //add liquid finishes here if added to website. liquid finishes have "sandstone" as default, id 448
                } else {//standard finish
                    $bfid = $quoteproduct['BackFinishDescription'];
                    $where = "product_id='$quoteproduct[bf_code]'";
                }

                //If Corro, then add in special Corro FInish Fabric Parchment on back finish
                if ($quoteproduct['texture_code'] == "97" || $quoteproduct['texture_code'] == "98") {//Corro Mezzo = 97; COrro mini = 98 (not active...)

                    //set id of standard finish
                    $idbf = $doc -> createElement("id");
                    $idbf -> appendChild($doc -> createTextNode("corro_mezzo emboss"));
                    $standardBackFinish -> appendChild($idbf);

                    //get soft coat
                    $q_rmsc = db_query("SELECT * FROM qt_raw_materials WHERE partnumber='0-20-200' AND sheet_size_id='$quoteproduct[sheetdimension]'");
                    //SOFT COAT
                    $r_rmsc = mysql_fetch_assoc($q_rmsc);

                    $mfgmatsc = $doc -> createElement("MfgMaterial");
                    $standardBackFinish -> appendChild($mfgmatsc);

                    $descrsc = $doc -> createElement("description");
                    $descrsc -> appendChild($doc -> createTextNode($r_rmsc['description']));
                    $mfgmatsc -> appendChild($descrsc);

                    $pnsc = $doc -> createElement("partNumber");
                    $pnsc -> appendChild($doc -> createTextNode($r_rmsc['partnumber']));
                    $mfgmatsc -> appendChild($pnsc);

                    $qtysc = $doc -> createElement("quantity");
                    $qtysc -> appendChild($doc -> createTextNode($r_rmsc['qty']));
                    $mfgmatsc -> appendChild($qtysc);

                    //get Voile fabric
                    $q_voilebf = db_query("SELECT * FROM qt_raw_materials WHERE partnumber='0-51-124' AND sheet_size_id='$quoteproduct[sheetdimension]'");
                    $r_voilebf = mysql_fetch_assoc($q_voilebf);

                    $mfgmatvbf = $doc -> createElement("MfgMaterial");
                    $standardBackFinish -> appendChild($mfgmatvbf);

                    $descrvbf = $doc -> createElement("description");
                    $descrvbf -> appendChild($doc -> createTextNode($r_voilebf['description']));
                    $mfgmatvbf -> appendChild($descrvbf);

                    $pnvbf = $doc -> createElement("partNumber");
                    $pnvbf -> appendChild($doc -> createTextNode($r_voilebf['partnumber']));
                    $mfgmatvbf -> appendChild($pnvbf);

                    $qtyvbf = $doc -> createElement("quantity");
                    $qtyvbf -> appendChild($doc -> createTextNode($r_voilebf['qty']));
                    $mfgmatvbf -> appendChild($qtyvbf);

                    //set the finish to sandstone
                    $where = "product_id='04'";

                } else {
                    //set id of standard finish
                    $idbf = $doc -> createElement("id");
                    $idbf -> appendChild($doc -> createTextNode($bfid));
                    $standardBackFinish -> appendChild($idbf);
                }

                //get raw materials for selected back finish
                $q_rmsbf = db_query("SELECT * FROM qt_raw_materials WHERE $where AND element_type='finish'");
                $r_rmsbf = mysql_fetch_assoc($q_rmsbf);

                $mfgmatbf = $doc -> createElement("MfgMaterial");
                $standardBackFinish -> appendChild($mfgmatbf);

                $descrbf = $doc -> createElement("description");
                $descrbf -> appendChild($doc -> createTextNode($r_rmsbf['description']));
                $mfgmatbf -> appendChild($descrbf);

                $pnbf = $doc -> createElement("partNumber");
                $pnbf -> appendChild($doc -> createTextNode($r_rmsbf['partnumber']));
                $mfgmatbf -> appendChild($pnbf);

                $qtybf = $doc -> createElement("quantity");
                $qtybf -> appendChild($doc -> createTextNode($r_rmsbf['qty']));
                $mfgmatbf -> appendChild($qtybf);

                //LAYER 10 - CUSTOM BACK FINISH: (premium)
                $layer10 = $doc -> createElement("layer");
                $layers -> appendChild($layer10);

                $el = $doc -> createElement("element");
                $layer10 -> appendChild($el);

                $customBackFinish = $doc -> createElement("customBackFinish");
                $el -> appendChild($customBackFinish);

                $this -> premiumfinishesarr = getspecialfinishes("premium");
                if (in_array($quoteproduct['bf_code'], $this -> premiumfinishesarr)) {
                    $customBackFinishtext = $quoteproduct['BackFinishDescription'];
                } else {
                    $customBackFinishtext = "None";
                }

                $cusbf = $doc -> createElement("id");
                $cusbf -> appendChild($doc -> createTextNode($customBackFinishtext));
                $customBackFinish -> appendChild($cusbf);

            } elseif ($quoteproduct['pline'] == "2") {

                //add chroma specific fields
                $chromacolorstyle = $doc -> createElement("chromaColorStyle");
                $chromacolorstyle -> appendChild($doc -> createTextNode($quoteproduct['chromaColorStyle']));
                $b -> appendChild($chromacolorstyle);

                $chromacolor = $doc -> createElement("chromaColor");
                $chromacolor -> appendChild($doc -> createTextNode($quoteproduct['chromaColor']));
                $b -> appendChild($chromacolor);

                $chromaff = $doc -> createElement("chromaFrontFinish");
                $chromaff -> appendChild($doc -> createTextNode($quoteproduct['chromaFrontFinish']));
                $b -> appendChild($chromaff);

                $chromabf = $doc -> createElement("chromaBackFinish");
                $chromabf -> appendChild($doc -> createTextNode($quoteproduct['chromaBackFinish']));
                $b -> appendChild($chromabf);

                //Layers
                $layers = $doc -> createElement("layers");
                $b -> appendChild($layers);

                $hasreflect = false;
                $chroma_colors = explode(" ", $quoteproduct['product_codes']);

                foreach ($chroma_colors as $value) {
                    $idconvert = $this -> convert_to_pidkey("color", $value);

                    if ($idconvert == "537") {//product_id from qt_raw_materials table
                        $hasreflect = true;
                        $quoteproduct['Gauge'] = $quoteproduct['Gauge'] - 3.1;
                        //remove reflect addition so correct acyrlic size can be found
                    }//537 = reflect
                }

                //**Romain, #187, add Chroma front finish
                $q_rm = db_query("SELECT * FROM qt_raw_materials WHERE id='446'");
                //446 is TEX PAP MATTE 51 inch 
                $r_rm = mysql_fetch_assoc($q_rm);
                
                $layerchromaff = $doc -> createElement("layer");
                $layers -> appendChild($layerchromaff);

                $el = $doc -> createElement("element");
                $layerchromaff -> appendChild($el);

                $standardFrontFinish = $doc -> createElement("standardFrontFinish");
                $el -> appendChild($standardFrontFinish);
                
                
                switch ($quoteproduct['ff_code']){
                    case "13"://nappa
                        $id = $doc -> createElement("id");
                        $id -> appendChild($doc -> createTextNode("TEX PAP MATTE"));
                        $standardFrontFinish -> appendChild($id);
                        
                        $mfgmat = $doc -> createElement("MfgMaterial");
                        $standardFrontFinish -> appendChild($mfgmat);
        
                        $descr = $doc -> createElement("description");
                        $descr -> appendChild($doc -> createTextNode("Nappa"));
                        $mfgmat -> appendChild($descr);
        
                        $pn = $doc -> createElement("partNumber");
                        $pn -> appendChild($doc -> createTextNode("4-10-031"));
                        $mfgmat -> appendChild($pn);
        
                        $qty = $doc -> createElement("quantity");
                        $qty -> appendChild($doc -> createTextNode($r_rm['qty']));
                        $mfgmat -> appendChild($qty);
                        break;
                        
                    case "14"://grid
                        $id = $doc -> createElement("id");
                        $id -> appendChild($doc -> createTextNode("TEX PAP MATTE"));
                        $standardFrontFinish -> appendChild($id);
                        
                        $mfgmat = $doc -> createElement("MfgMaterial");
                        $standardFrontFinish -> appendChild($mfgmat);
        
                        $descr = $doc -> createElement("description");
                        $descr -> appendChild($doc -> createTextNode("Grid"));
                        $mfgmat -> appendChild($descr);
        
                        $pn = $doc -> createElement("partNumber");
                        $pn -> appendChild($doc -> createTextNode("4-10-033"));
                        $mfgmat -> appendChild($pn);
        
                        $qty = $doc -> createElement("quantity");
                        $qty -> appendChild($doc -> createTextNode($r_rm['qty']));
                        $mfgmat -> appendChild($qty);
                        break;    
                        
                    case "15"://grain
                        $id = $doc -> createElement("id");
                        $id -> appendChild($doc -> createTextNode("TEX PAP MATTE"));
                        $standardFrontFinish -> appendChild($id);
                        
                        $mfgmat = $doc -> createElement("MfgMaterial");
                        $standardFrontFinish -> appendChild($mfgmat);
        
                        $descr = $doc -> createElement("description");
                        $descr -> appendChild($doc -> createTextNode("Grain"));
                        $mfgmat -> appendChild($descr);
        
                        $pn = $doc -> createElement("partNumber");
                        $pn -> appendChild($doc -> createTextNode("4-10-030"));
                        $mfgmat -> appendChild($pn);
        
                        $qty = $doc -> createElement("quantity");
                        $qty -> appendChild($doc -> createTextNode($r_rm['qty']));
                        $mfgmat -> appendChild($qty);
                        break;
                     
                    case "16"://transit
                        $id = $doc -> createElement("id");
                        $id -> appendChild($doc -> createTextNode("TEX PAP MATTE"));
                        $standardFrontFinish -> appendChild($id);
                        
                        $mfgmat = $doc -> createElement("MfgMaterial");
                        $standardFrontFinish -> appendChild($mfgmat);
        
                        $descr = $doc -> createElement("description");
                        $descr -> appendChild($doc -> createTextNode("Transit"));
                        $mfgmat -> appendChild($descr);
        
                        $pn = $doc -> createElement("partNumber");
                        $pn -> appendChild($doc -> createTextNode("4-10-032"));
                        $mfgmat -> appendChild($pn);
        
                        $qty = $doc -> createElement("quantity");
                        $qty -> appendChild($doc -> createTextNode($r_rm['qty']));
                        $mfgmat -> appendChild($qty);
                        break; 
                    case "17"://velvet
                        $id = $doc -> createElement("id");
                        $id -> appendChild($doc -> createTextNode("TEX PAP MATTE"));
                        $standardFrontFinish -> appendChild($id);
                        
                        $mfgmat = $doc -> createElement("MfgMaterial");
                        $standardFrontFinish -> appendChild($mfgmat);
        
                        $descr = $doc -> createElement("description");
                        $descr -> appendChild($doc -> createTextNode("Velvet"));
                        $mfgmat -> appendChild($descr);
        
                        $pn = $doc -> createElement("partNumber");
                        $pn -> appendChild($doc -> createTextNode("4-10-035"));
                        $mfgmat -> appendChild($pn);
        
                        $qty = $doc -> createElement("quantity");
                        $qty -> appendChild($doc -> createTextNode($r_rm['qty']));
                        $mfgmat -> appendChild($qty);
                        break; 
                        
                    case "18"://brush
                        $id = $doc -> createElement("id");
                        $id -> appendChild($doc -> createTextNode("TEX PAP MATTE"));
                        $standardFrontFinish -> appendChild($id);
                        
                        $mfgmat = $doc -> createElement("MfgMaterial");
                        $standardFrontFinish -> appendChild($mfgmat);
        
                        $descr = $doc -> createElement("description");
                        $descr -> appendChild($doc -> createTextNode("Brush"));
                        $mfgmat -> appendChild($descr);
        
                        $pn = $doc -> createElement("partNumber");
                        $pn -> appendChild($doc -> createTextNode("4-10-034"));
                        $mfgmat -> appendChild($pn);
        
                        $qty = $doc -> createElement("quantity");
                        $qty -> appendChild($doc -> createTextNode($r_rm['qty']));
                        $mfgmat -> appendChild($qty);
                        break;              
                    default:
                        $id = $doc -> createElement("id");
                        $id -> appendChild($doc -> createTextNode("TEX PAP MATTE"));
                        $standardFrontFinish -> appendChild($id);
                        
                        $mfgmat = $doc -> createElement("MfgMaterial");
                        $standardFrontFinish -> appendChild($mfgmat);
        
                        $descr = $doc -> createElement("description");
                        $descr -> appendChild($doc -> createTextNode($r_rm['description']));
                        $mfgmat -> appendChild($descr);
        
                        $pn = $doc -> createElement("partNumber");
                        $pn -> appendChild($doc -> createTextNode($r_rm['partnumber']));
                        $mfgmat -> appendChild($pn);
        
                        $qty = $doc -> createElement("quantity");
                        $qty -> appendChild($doc -> createTextNode($r_rm['qty']));
                        $mfgmat -> appendChild($qty);
                        
                        break;    
                }
                
                
                
                
                
                //**End #187
                

                //LAYER 1 - ACRYLIC
                $layerchroma1 = $doc -> createElement("layer");
                $layers -> appendChild($layerchroma1);

                $chel = $doc -> createElement("element");
                $layerchroma1 -> appendChild($chel);

                $acrylic = $doc -> createElement("Acrylic");
                $chel -> appendChild($acrylic);

                //get the raw materials for the acyrlic.  According to Sept 23 e-mail, only use uv chroma
                //**Romain, 2013-06-13, Spiceworks Ticket #149, Change Chroma Cast for Extruded by default
                /*$chromaquery = "SELECT * FROM qt_raw_materials WHERE extra='$quoteproduct[Gauge]' 
					AND sheet_size_id='$quoteproduct[sheetdimension]' AND element_type = 'acyrlicuv'";*/
				$chromaquery = "SELECT * FROM qt_raw_materials WHERE extra='$quoteproduct[Gauge]' 
					AND sheet_size_id='$quoteproduct[sheetdimension]' AND element_type = 'acyrlic'";
                $q_acyrlic = db_query($chromaquery);
                $r_acyrlic = mysql_fetch_assoc($q_acyrlic);

                $chpn = $doc -> createElement("partNumber");
                $chpn -> appendChild($doc -> createTextNode($r_acyrlic['partnumber']));
                $acrylic -> appendChild($chpn);

                $chdescr = $doc -> createElement("description");
                $chdescr -> appendChild($doc -> createTextNode($r_acyrlic['description']));
                //." : $chromaquery"
                $acrylic -> appendChild($chdescr);

                $chqty = $doc -> createElement("quantity");
                $chqty -> appendChild($doc -> createTextNode($r_acyrlic['qty']));
                $acrylic -> appendChild($chqty);

                //LAYERS COLORS (including reflect)
                if ($hasreflect == true) {//build chroma reflect

                    foreach ($chroma_colors as $index => $value) {
                         //**Romain, #187, remove the TPU put just after the PMMA, index0    
                        if ($index!=0){
                            $layertpu = $doc -> createElement("layer");
                            $layers -> appendChild($layertpu);
    
                            $chtpuel = $doc -> createElement("element");
                            $layertpu -> appendChild($chtpuel);
    
                            $tpu = $doc -> createElement("TPU");
                            $chtpuel -> appendChild($tpu);
    
                            $q_tpu = db_query("SELECT * FROM qt_raw_materials WHERE partnumber='4-10-028' 
    							AND sheet_size_id='$quoteproduct[sheetdimension]'");
                            $r_tpu = mysql_fetch_assoc($q_tpu);
    
                            $chtpupn = $doc -> createElement("partNumber");
                            $chtpupn -> appendChild($doc -> createTextNode($r_tpu['partnumber']));
                            $tpu -> appendChild($chtpupn);
    
                            $chtpudescr = $doc -> createElement("description");
                            $chtpudescr -> appendChild($doc -> createTextNode($r_tpu['description']));
                            $tpu -> appendChild($chtpudescr);
    
                            $chtpuqty = $doc -> createElement("quantity");
                            $chtpuqty -> appendChild($doc -> createTextNode($r_tpu['qty']));
                            $tpu -> appendChild($chtpuqty);
                        }
                        //build the xml layers for chroma 2 color films
                        $layercolor = $doc -> createElement("layer");
                        $layers -> appendChild($layercolor);

                        $chcolorel = $doc -> createElement("element");
                        $layercolor -> appendChild($chcolorel);

                        $chroma2 = $doc -> createElement("chromaIIColor");
                        $chcolorel -> appendChild($chroma2);

                        $idconvert = $this -> convert_to_pidkey("color", $value);

                        $q_chprod = db_query("SELECT * FROM qt_raw_materials WHERE product_id='$idconvert' 
							AND sheet_size_id='$quoteproduct[sheetdimension]'");
                        $r_chprod = mysql_fetch_assoc($q_chprod);

                        $chcolorpn = $doc -> createElement("partNumber");
                        $chcolorpn -> appendChild($doc -> createTextNode($r_chprod['partnumber']));
                        $chroma2 -> appendChild($chcolorpn);

                        $chcolordescr = $doc -> createElement("description");
                        $chcolordescr -> appendChild($doc -> createTextNode($r_chprod['description']));
                        $chroma2 -> appendChild($chcolordescr);

                        $chcolorqty = $doc -> createElement("quantity");
                        $chcolorqty -> appendChild($doc -> createTextNode($r_chprod['qty']));
                        $chroma2 -> appendChild($chcolorqty);
                    }

                   
                    $layertpu = $doc -> createElement("layer");
                    $layers -> appendChild($layertpu);

                    $chtpuel = $doc -> createElement("element");
                    $layertpu -> appendChild($chtpuel);

                    $tpu = $doc -> createElement("TPU");
                    $chtpuel -> appendChild($tpu);

                    $q_tpu = db_query("SELECT * FROM qt_raw_materials WHERE partnumber='4-10-028' 
						AND sheet_size_id='$quoteproduct[sheetdimension]'");
                    $r_tpu = mysql_fetch_assoc($q_tpu);

                    $chtpupn = $doc -> createElement("partNumber");
                    $chtpupn -> appendChild($doc -> createTextNode($r_tpu['partnumber']));
                    $tpu -> appendChild($chtpupn);

                    $chtpudescr = $doc -> createElement("description");
                    $chtpudescr -> appendChild($doc -> createTextNode($r_tpu['description']));
                    $tpu -> appendChild($chtpudescr);

                    $chtpuqty = $doc -> createElement("quantity");
                    $chtpuqty -> appendChild($doc -> createTextNode($r_tpu['qty']));
                    $tpu -> appendChild($chtpuqty);

                } else {//build standard chroma colors layers

                    foreach ($chroma_colors as $index => $value) {

                        //build the xml layers for chroma 2 color films
                        $layercolor = $doc -> createElement("layer");
                        $layers -> appendChild($layercolor);

                        $chcolorel = $doc -> createElement("element");
                        $layercolor -> appendChild($chcolorel);

                        $chroma2 = $doc -> createElement("chromaIIColor");
                        $chcolorel -> appendChild($chroma2);

                        $idconvert = $this -> convert_to_pidkey("color", $value);

                        $chromarawmat = "SELECT * FROM qt_raw_materials WHERE product_id='$idconvert' AND sheet_size_id='$quoteproduct[sheetdimension]'";
                        $q_chprod = db_query($chromarawmat);
                        $r_chprod = mysql_fetch_assoc($q_chprod);

                        $chcolorpn = $doc -> createElement("partNumber");
                        $chcolorpn -> appendChild($doc -> createTextNode($r_chprod['partnumber']));
                        $chroma2 -> appendChild($chcolorpn);

                        $chcolordescr = $doc -> createElement("description");
                        $chcolordescr -> appendChild($doc -> createTextNode($r_chprod['description']));
                        //." : $chromarawmat"
                        $chroma2 -> appendChild($chcolordescr);

                        $chcolorqty = $doc -> createElement("quantity");
                        $chcolorqty -> appendChild($doc -> createTextNode($r_chprod['qty']));
                        $chroma2 -> appendChild($chcolorqty);
                    }
                }

                //LAYER Clearfilm
                $layerclearfilm = $doc -> createElement("layer");
                $layers -> appendChild($layerclearfilm);

                $chclearel = $doc -> createElement("element");
                $layerclearfilm -> appendChild($chclearel);

                $clearfilm = $doc -> createElement("clearFilm");
                $chclearel -> appendChild($clearfilm);
                
                //get the raw materials for the last layer.  According to Sept 23 e-mail, only use uv chroma
                $q_clearfilm = db_query("SELECT * FROM qt_raw_materials WHERE partnumber='0-31-029' 
                    AND sheet_size_id='$quoteproduct[sheetdimension]' AND element_type = 'clearfilm'");
                //0-31-071 not used per manuela sept 26, 2011
                $r_clearfilm = mysql_fetch_assoc($q_clearfilm);

                $chclearpn = $doc -> createElement("partNumber");
                $chclearpn -> appendChild($doc -> createTextNode($r_clearfilm['partnumber']));
                $clearfilm -> appendChild($chclearpn);

                $chcleardescr = $doc -> createElement("description");
                $chcleardescr -> appendChild($doc -> createTextNode($r_clearfilm['description']));
                $clearfilm -> appendChild($chcleardescr);

                $chclearqty = $doc -> createElement("quantity");
                $chclearqty -> appendChild($doc -> createTextNode($r_clearfilm['qty']));
                $clearfilm -> appendChild($chclearqty);
                
                 
                 
                 /*
                 * Romain, 2013-06-14, Chroma DS - Add an extra film for seaming
                 * An important material option in case of straight or curved butt seams especially where both sides are visible in the installation. 
                 */
                if ($quoteproduct['bf_code']=="88"){
                    //$firephp->warn($quoteproduct['bf_code'],"CHROMA DS");
                    //#1
                    $layerclearfilm = $doc -> createElement("layer");
                    $layers -> appendChild($layerclearfilm);

                    $chclearel = $doc -> createElement("element");
                    $layerclearfilm -> appendChild($chclearel);
                    
                    $clearfilm = $doc -> createElement("clearFilm");
                    $chclearel -> appendChild($clearfilm);
                      
                    $chclearpn = $doc -> createElement("partNumber");
                    $chclearpn -> appendChild($doc -> createTextNode($r_clearfilm['partnumber']));
                    $clearfilm -> appendChild($chclearpn);
    
                    $chcleardescr = $doc -> createElement("description");
                    $chcleardescr -> appendChild($doc -> createTextNode($r_clearfilm['description']));
                    $clearfilm -> appendChild($chcleardescr);
    
                    $chclearqty = $doc -> createElement("quantity");
                    $chclearqty -> appendChild($doc -> createTextNode($r_clearfilm['qty']));
                    $clearfilm -> appendChild($chclearqty);
                    
                    //#2
                    $layerclearfilm = $doc -> createElement("layer");
                    $layers -> appendChild($layerclearfilm);

                    $chclearel = $doc -> createElement("element");
                    $layerclearfilm -> appendChild($chclearel);
                    
                    $clearfilm = $doc -> createElement("clearFilm");
                    $chclearel -> appendChild($clearfilm);
                      
                    $chclearpn = $doc -> createElement("partNumber");
                    $chclearpn -> appendChild($doc -> createTextNode($r_clearfilm['partnumber']));
                    $clearfilm -> appendChild($chclearpn);
    
                    $chcleardescr = $doc -> createElement("description");
                    $chcleardescr -> appendChild($doc -> createTextNode($r_clearfilm['description']));
                    $clearfilm -> appendChild($chcleardescr);
    
                    $chclearqty = $doc -> createElement("quantity");
                    $chclearqty -> appendChild($doc -> createTextNode($r_clearfilm['qty']));
                    $clearfilm -> appendChild($chclearqty);
                    
                }
                
                
                //**Romain, #187, add Chroma back finish
                    $q_rm = db_query("SELECT * FROM qt_raw_materials WHERE id='446'");
                    //446 is TEX PAP MATTE 51 inch 
                    $r_rm = mysql_fetch_assoc($q_rm);
                    
                    $layerchromaff = $doc -> createElement("layer");
                    $layers -> appendChild($layerchromaff);
    
                    $el = $doc -> createElement("element");
                    $layerchromaff -> appendChild($el);
    
                    $standardFrontFinish = $doc -> createElement("standardBackFinish");
                    $el -> appendChild($standardFrontFinish);
                    
                    $id = $doc -> createElement("id");
                    $id -> appendChild($doc -> createTextNode("TEX PAP MATTE"));
                    $standardFrontFinish -> appendChild($id);
                    
                    $mfgmat = $doc -> createElement("MfgMaterial");
                    $standardFrontFinish -> appendChild($mfgmat);
    
                    $descr = $doc -> createElement("description");
                    $descr -> appendChild($doc -> createTextNode($r_rm['description']));
                    $mfgmat -> appendChild($descr);
    
                    $pn = $doc -> createElement("partNumber");
                    $pn -> appendChild($doc -> createTextNode($r_rm['partnumber']));
                    $mfgmat -> appendChild($pn);
    
                    $qty = $doc -> createElement("quantity");
                    $qty -> appendChild($doc -> createTextNode($r_rm['qty']));
                    $mfgmat -> appendChild($qty);  
                
                
                
                //**End #187
                
                
                
                

            } elseif ($quoteproduct['pline'] == "3") {

                //Layers
                $layers = $doc -> createElement("layers");
                $b -> appendChild($layers);

                //get product
                $prodid = $quoteproduct['product_codes'];

                //get white out/reflect additions (HARDCODED SOLUTION)
                $haswhiteout = $hasreflect = false;
                if ($prodid == "693") {
                    $haswhiteout = true;
                    $prodid = "260";
                } elseif ($prodid == "694") {
                    $hasreflect = true;
                    $prodid = "260";
                }

                //pidkey conversion
                $idconvert = $this -> convert_to_pidkey("product", $prodid);

                //get astrocure type (includingn partnumber)
                if ($prodid == "626" || $prodid == "625" || $prodid == "495" || $prodid == "280" || $prodid == "281" || $prodid == "491") {
                    $astrocure = "5000";
                    //illusion, cosmos, creekside, sequin silver, harmony, tangle
                    $astropartnumber = "3FG0000022";
                } else {//
                    $astrocure = "1600";
                    //bubble, cascade, curly willo, dhichroic, larkspur, pineapple weave, seaweed, birch
                    $astropartnumber = "3FG0000010";
                }

                //get resin gauge for given interlayer
                $resingauge = "";
                if ($prodid == "489" || $prodid == "280" || $prodid == "281" || $prodid == "495") {//bubble, creekside, harmony, sequin silver
                    $resingauge = "6";
                } elseif ($prodid == "286" || $prodid == "627" || $prodid == "284" || $prodid == "285" || $prodid == "287" || $prodid == "626" || $prodid == "306" || $prodid == "628" || $prodid == "629" || $prodid == "625" || $prodid == "259" || $prodid == "694" || $prodid == "693" || $prodid == "260" || $prodid == "727" || $prodid == "728" || $prodid == "729") {//cascade, cosmos, Curly willow, Dichroic, illusion, pineapple, seaweed (has reflect and white out)
                    $resingauge = "7.5";
                    /*
                     }elseif($prodid == "???"){//not used
                     $resingauge = "9";
                     */
                } elseif ($prodid == "261") {//larkspur
                    $resingauge = "10.5";
                } elseif ($prodid == "491") {//tangle
                    $resingauge = "12";
                }

                $q_glastro = db_query("SELECT * FROM qt_raw_materials WHERE element_type='pouredglass_astrocure' AND extra='$resingauge'");
                $r_glastro = mysql_fetch_assoc($q_glastro);

                $astrocureqty = ($r_glastro['qty']) * (($quoteproduct['Height'] / 1000) * ($quoteproduct['Width'] / 1000));
                //put into L

                //peroxide
                $q_glperoxide = db_query("SELECT * FROM qt_raw_materials WHERE partnumber='3FG0000014'");
                //Glass only has one product
                $r_glperoxide = mysql_fetch_assoc($q_glperoxide);

                $glasssheetmatperoxide = $doc -> createElement("pouredGlassSheetMaterial");
                $layers -> appendChild($glasssheetmatperoxide);

                $peroxidedescr = $doc -> createElement("description");
                $peroxidedescr -> appendChild($doc -> createTextNode($r_glperoxide['description']));
                //
                $glasssheetmatperoxide -> appendChild($peroxidedescr);

                $peroxidepn = $doc -> createElement("partNumber");
                $peroxidepn -> appendChild($doc -> createTextNode($r_glperoxide['partnumber']));
                $glasssheetmatperoxide -> appendChild($peroxidepn);

                $peroxideqty = round(($r_glperoxide['qty'] * ($astrocureqty)) * 1000);
                //put into mL

                $pgperoxideqty = $doc -> createElement("quantity");
                $pgperoxideqty -> appendChild($doc -> createTextNode($peroxideqty));
                $glasssheetmatperoxide -> appendChild($pgperoxideqty);

                //primer
                $q_glprimer = db_query("SELECT * FROM qt_raw_materials WHERE partnumber='3FG0000011'");
                //Glass only has one product
                $r_glprimer = mysql_fetch_assoc($q_glprimer);

                $glasssheetmatprimer = $doc -> createElement("pouredGlassSheetMaterial");
                $layers -> appendChild($glasssheetmatprimer);

                $primerdescr = $doc -> createElement("description");
                $primerdescr -> appendChild($doc -> createTextNode($r_glprimer['description']));
                //
                $glasssheetmatprimer -> appendChild($primerdescr);

                $primerpn = $doc -> createElement("partNumber");
                $primerpn -> appendChild($doc -> createTextNode($r_glprimer['partnumber']));
                $glasssheetmatprimer -> appendChild($primerpn);

                $primerqty = round(($r_glprimer['qty'] * ($astrocureqty)) * 1000);
                //put into mL

                $pgprimerqty = $doc -> createElement("quantity");
                $pgprimerqty -> appendChild($doc -> createTextNode($primerqty));
                $glasssheetmatprimer -> appendChild($pgprimerqty);

                //astrocure
                $glassastro = $doc -> createElement("pouredGlassSheetMaterial");
                $layers -> appendChild($glassastro);

                $astrodescr = $doc -> createElement("description");
                $astrodescr -> appendChild($doc -> createTextNode($r_glastro['description'] . " $astrocure"));
                //
                $glassastro -> appendChild($astrodescr);

                $astropn = $doc -> createElement("partNumber");
                $astropn -> appendChild($doc -> createTextNode($astropartnumber));
                $glassastro -> appendChild($astropn);

                $pgastroqty = $doc -> createElement("quantity");
                $pgastroqty -> appendChild($doc -> createTextNode($astrocureqty));
                $glassastro -> appendChild($pgastroqty);

                //glass
                $q_glglass = db_query("SELECT * FROM qt_raw_materials WHERE partnumber='3FG0000012'");
                //Glass only has one product
                $r_glglass = mysql_fetch_assoc($q_glglass);

                $glassmat = $doc -> createElement("pouredGlassSheetMaterial");
                $layers -> appendChild($glassmat);

                $glassdescr = $doc -> createElement("description");
                $glassdescr -> appendChild($doc -> createTextNode($r_glglass['description']));
                //
                $glassmat -> appendChild($glassdescr);

                $glasspn = $doc -> createElement("partNumber");
                $glasspn -> appendChild($doc -> createTextNode($r_glglass['partnumber']));
                $glassmat -> appendChild($glasspn);

                $glassqty = $doc -> createElement("quantity");
                $glassqty -> appendChild($doc -> createTextNode($r_glglass['qty']));
                $glassmat -> appendChild($glassqty);

                //product
                $q_glprod = db_query("SELECT * FROM qt_raw_materials WHERE product_id='$idconvert'");
                //Glass only has one product
                $r_glprod = mysql_fetch_assoc($q_glprod);

                //get partnumber
                $glass_pns = explode(",", $r_glprod['partnumber']);
                $glass_qty = explode(",", $r_glprod['qty']);

                //build poured glass sheet material
                foreach ($glass_pns as $index => $value) {
                    $glasssheetmat = $doc -> createElement("pouredGlassSheetMaterial");
                    $layers -> appendChild($glasssheetmat);

                    $glassdescr = $doc -> createElement("description");
                    $glassdescr -> appendChild($doc -> createTextNode($r_glprod['description']));
                    //
                    $glasssheetmat -> appendChild($glassdescr);

                    //.":SELECT * FROM qt_raw_materials WHERE product_id='$idconvert':$quoteproduct[product_codes]"
                    $glasspn = $doc -> createElement("partNumber");
                    $glasspn -> appendChild($doc -> createTextNode($value));
                    $glasssheetmat -> appendChild($glasspn);

                    $glassqty = $doc -> createElement("quantity");
                    $glassqty -> appendChild($doc -> createTextNode($glass_qty[$index]));
                    $glasssheetmat -> appendChild($glassqty);
                }

                //glass (or white out, or reflect)

                if ($hasreflect) {//reflect

                    $q_glglass = db_query("SELECT * FROM qt_raw_materials WHERE partnumber='3FG0000024'");
                    //Glass only has one product
                    $r_glglass = mysql_fetch_assoc($q_glglass);

                    $glassmat = $doc -> createElement("pouredGlassSheetMaterial");
                    $layers -> appendChild($glassmat);

                    $glassdescr = $doc -> createElement("description");
                    $glassdescr -> appendChild($doc -> createTextNode($r_glglass['description']));
                    //
                    $glassmat -> appendChild($glassdescr);

                    $glasspn = $doc -> createElement("partNumber");
                    $glasspn -> appendChild($doc -> createTextNode($r_glglass['partnumber']));
                    $glassmat -> appendChild($glasspn);

                    $glassqty = $doc -> createElement("quantity");
                    $glassqty -> appendChild($doc -> createTextNode($r_glglass['qty']));
                    $glassmat -> appendChild($glassqty);

                } elseif ($haswhiteout) {//white out

                    $q_glglass = db_query("SELECT * FROM qt_raw_materials WHERE partnumber='3FG0000025'");
                    //Glass only has one product
                    $r_glglass = mysql_fetch_assoc($q_glglass);

                    $glassmat = $doc -> createElement("pouredGlassSheetMaterial");
                    $layers -> appendChild($glassmat);

                    $glassdescr = $doc -> createElement("description");
                    $glassdescr -> appendChild($doc -> createTextNode($r_glglass['description']));
                    //
                    $glassmat -> appendChild($glassdescr);

                    $glasspn = $doc -> createElement("partNumber");
                    $glasspn -> appendChild($doc -> createTextNode($r_glglass['partnumber']));
                    $glassmat -> appendChild($glasspn);

                    $glassqty = $doc -> createElement("quantity");
                    $glassqty -> appendChild($doc -> createTextNode($r_glglass['qty']));
                    $glassmat -> appendChild($glassqty);

                } else {//standard glass

                    $q_glglass = db_query("SELECT * FROM qt_raw_materials WHERE partnumber='3FG0000012'");
                    //Glass only has one product
                    $r_glglass = mysql_fetch_assoc($q_glglass);

                    $glassmat = $doc -> createElement("pouredGlassSheetMaterial");
                    $layers -> appendChild($glassmat);

                    $glassdescr = $doc -> createElement("description");
                    $glassdescr -> appendChild($doc -> createTextNode($r_glglass['description']));
                    //
                    $glassmat -> appendChild($glassdescr);

                    $glasspn = $doc -> createElement("partNumber");
                    $glasspn -> appendChild($doc -> createTextNode($r_glglass['partnumber']));
                    $glassmat -> appendChild($glasspn);

                    $glassqty = $doc -> createElement("quantity");
                    $glassqty -> appendChild($doc -> createTextNode($r_glglass['qty']));
                    $glassmat -> appendChild($glassqty);
                }

            }

            if ($in_xml == "ICEpack.xml") {

                $qty = $doc -> createElement("qty");
                $qty -> appendChild($doc -> createTextNode($quoteproduct['qty']));
                $b -> appendChild($qty);

                $discount = $doc -> createElement("discount");
                $discount -> appendChild($doc -> createTextNode($quoteproduct['discount']));
                $b -> appendChild($discount);

                if ($quoteproduct['pline'] == "3") {
                    $sheetid = $doc -> createElement("sheetUID");
                    $sheetid -> appendChild($doc -> createTextNode($quoteproduct['sheetUID']));
                    //$quoteproduct['sheetUID'] this is actually sheetgroupid
                    $b -> appendChild($sheetid);
                } else {

                    //add sheet ids used in this group
                    foreach ($quoteproduct['sheets'] as $innerkey => $innervalue) {
                        $sheetid = $doc -> createElement("sheetUID");
                        $sheetid -> appendChild($doc -> createTextNode($innerkey));
                        //$quoteproduct['sheetUID'] this is actually sheetgroupid
                        $b -> appendChild($sheetid);
                    }
                }

                //finalize
                $r -> appendChild($s);
                $s -> appendChild($b);

            } elseif ($in_xml == "SimpleFab.xml") {

                //display panel details for the current sheet
                $quoteproduct_panelid = "";
                $haspanel = false;
                foreach ($this->sheetpanels_arr as $outerarraykey => $innerarray) {

                    if ($outerarraykey == $this -> sheetuid_value) {

                        $haspanel = true;

                        foreach ($innerarray as $panelid => $panelvalues) {

                            //get panelid
                            $quoteproduct_panelid = $panelvalues['panelid'];

                            //build panel section
                            $panel = $doc -> createElement("panel");
                            $b -> appendChild($panel);

                            $panelid = $doc -> createElement("PanelID");
                            $panelid -> appendChild($doc -> createTextNode($panelvalues['partid']));
                            $panel -> appendChild($panelid);

                            $panelwidth = $doc -> createElement("width");
                            $panelwidth -> appendChild($doc -> createTextNode($panelvalues['width']));
                            $panel -> appendChild($panelwidth);

                            $panelheight = $doc -> createElement("height");
                            $panelheight -> appendChild($doc -> createTextNode($panelvalues['length']));
                            $panel -> appendChild($panelheight);

                            $paneloptwidth = $doc -> createElement("optimizedwidth");
                            $paneloptwidth -> appendChild($doc -> createTextNode($panelvalues['optimizedwidth']));
                            $panel -> appendChild($paneloptwidth);

                            $paneloptheight = $doc -> createElement("optimizedheight");
                            $paneloptheight -> appendChild($doc -> createTextNode($panelvalues['optimizedlength']));
                            $panel -> appendChild($paneloptheight);

                            $panelcutid = $doc -> createElement("cutID");
                            $panelcutid -> appendChild($doc -> createTextNode($panelvalues['cutid']));
                            $panel -> appendChild($panelcutid);

                            if ($panelvalues['patterndirection'] == 2) {//FIXME?
                                $paneldirsf = "Parallel to " . $panelvalues['length'];
                            } elseif ($panelvalues['patterndirection'] == 1) {
                                $paneldirsf = "Parallel to " . $panelvalues['width'];
                            } else {
                                $paneldirsf = "";
                            }

                            $panelmatdirdesc = $doc -> createElement("materialDirectionDescription");
                            $panelmatdirdesc -> appendChild($doc -> createTextNode($paneldirsf));
                            $panel -> appendChild($panelmatdirdesc);

                            //TODO put fabrication such as line bend, holes, edge finishes, etc. here?

                            $panelid_arr = explode(":", $panelvalues['panelid']);
                            $quoteprodid = $panelid_arr[0];
                            $panel_num = intval($panelid_arr[1]);

                            //create subdirecty "fabrication" and put in "panel" element
                            $panelfab = $doc -> createElement("panelFabrication");
                            $panel -> appendChild($panelfab);

                            //get fabrication for the given panel
                       		$ofquery = "SELECT * FROM qt_fab_products WHERE quoteproduct_id ='$quoteprodid' AND active = '1' AND 
							(fab_category = 'otherfab' OR panel_num <='$panel_num') ";
                            //ORDER BY panel_num ASC //$quoteproduct[qty]
                            $q_fab = db_query($ofquery);

                            $fabdetails = new Fabrication();

                            while ($r_fab = mysql_fetch_assoc($q_fab)) {

                                if ($r_fab['panel_id'] != "" && ($r_fab['fab_detail_1'] != "" || $r_fab['fab_detail_2'] != "")) {

                                    if ($r_fab['fab_category'] == "otherfab") {

                                        //create "other fabrication" sub directory under "fabrication"
                                        $otherfab = $doc -> createElement("otherFabrication");
                                        $panelfab -> appendChild($otherfab);

                                        //create data for other fabrication (id, pricing, text)
                                        $ofid = $doc -> createElement("otherfabID");
                                        $ofid -> appendChild($doc -> createTextNode($r_fab['panel_id']));
                                        $otherfab -> appendChild($ofid);

                                        $oftext = $doc -> createElement("otherfabText");
                                        $oftext -> appendChild($doc -> createTextNode($r_fab['fab_detail_1']));
                                        //TODO test crazy characters
                                        $otherfab -> appendChild($oftext);

                                        $ofvalue = $doc -> createElement("otherfabValue");
                                        $ofvalue -> appendChild($doc -> createTextNode($r_fab['fab_detail_2']));
                                        $otherfab -> appendChild($ofvalue);

                                        //add custom notes
                                        if ($quoteproduct['productCode'] == "VARIA") {
                                            $variafabricationnotes .= $r_fab['fab_detail_1'] . "\r\n";
                                        } elseif ($quoteproduct['productCode'] == "CHROMA") {
                                            $chromafabricationnotes .= $r_fab['fab_detail_1'] . "\r\n";
                                        } elseif ($quoteproduct['productCode'] == "GLASS") {
                                            $glassfabricationnotes .= $r_fab['fab_detail_1'] . "\r\n";
                                        }
                                    } else {

                                        $fabdetails -> get_fab_completion($quoteprodid, $r_fab['fab_category'], $r_fab['fab_detail_1'], $r_fab['fab_detail_2'], $r_fab['fab_detail_3'], $r_fab['fab_detail_4'], $r_fab['fab_detail_5'], $r_fab['fab_detail_6'], $r_fab['fab_detail_7'], $r_fab['fab_detail_8'], $r_fab['id']);

                                        //add fabrication
                                        if ($fabdetails -> completion == true) {

                                            if ($r_fab['fab_category'] != "highres" && $r_fab['fab_category'] != "fade") {

                                                if ($r_fab['panel_num'] == $panel_num || $r_fab['process_group'] == 1) {//

                                                    $fabdetails -> get_fab_descr($quoteprodid, $r_fab['panel_id'], $quoteproduct['qty'], $panelvalues['length'], $panelvalues['width'], $quoteproduct['Gauge'], $r_fab['fab_category'], $r_fab['fab_detail_1'], $r_fab['fab_detail_2'], $r_fab['fab_detail_3'], $r_fab['fab_detail_4'], $r_fab['fab_detail_5'], $r_fab['fab_detail_6'], $r_fab['fab_detail_7'], $r_fab['fab_detail_8'], $r_fab['id'],1,1);

                                                    $descriptionfab = "$fabdetails->descr";

                                                    //get price
                                                    $q_quote = db_query("SELECT * FROM qt_quotes WHERE id='$_SESSION[quote]';");
                                                    $r_quote = mysql_fetch_assoc($q_quote);

                                                    //get currency
                                                    $currencyconvert = 1;
                                                    if ($r_quote['currency_id'] > 0) {
                                                        $q_cu = db_query("SELECT * FROM currency WHERE id = '$r_quote[currency_id]';");
                                                        $r_cu = mysql_fetch_assoc($q_cu);
                                                        //$currsymbol = "$r_cu[symbol]";
                                                        $currencyconvert = $r_cu['ex_val'];
                                                        //$currsymbol = $r_cu['symbol'];
                                                    }

                                                    $fabdetails -> get_fab_price($quoteprodid, $quoteproduct['pline'], $r_quote['catalog_id'], $panelvalues['length'], $panelvalues['width'], $quoteproduct['Gauge'], $r_fab['fab_category'], $r_fab['fab_detail_1'], $r_fab['fab_detail_2'], $r_fab['fab_detail_3'], $r_fab['fab_detail_4'], $r_fab['fab_detail_5'], $r_fab['fab_detail_6'], $r_fab['fab_detail_7'], $r_fab['fab_detail_8'], $r_fab['id']);

                                                    $fabdetails -> price = $fabdetails -> price * $currencyconvert;
                                                    $fabdetails -> price = number_format($fabdetails -> price, 2);
                                                    $fabdetails -> price = str_replace(",", "", $fabdetails -> price);

                                                    //create fabrication elements - these are standard to make looping easier/ updating easier
                                                    $fab = $doc -> createElement("fabrication");
                                                    //$r_fab['fab_category']
                                                    $panelfab -> appendChild($fab);

                                                    $fabid = $doc -> createElement("fabID");
                                                    
                                                    $fabid -> appendChild($doc -> createTextNode($r_fab['id']));
                                                    $fab -> appendChild($fabid);

                                                    $fabtype = $doc -> createElement("fabType");
                                                    $fabtype -> appendChild($doc -> createTextNode($r_fab['fab_category']));
                                                    $fab -> appendChild($fabtype);

                                                    $fabtext = $doc -> createElement("fabText");
                                                    $fabtext -> appendChild($doc -> createTextNode($descriptionfab));
                                                    $fab -> appendChild($fabtext);

                                                    $fabvalue = $doc -> createElement("fabValue");
                                                    $fabvalue -> appendChild($doc -> createTextNode($fabdetails -> price));
                                                    
                                                    $fab -> appendChild($fabvalue);

                                                    //add custom notes
                                                    if ($quoteproduct['productCode'] == "VARIA") {
                                                        $variafabricationnotes .= $descriptionfab . "\r\n";
                                                    } elseif ($quoteproduct['productCode'] == "CHROMA") {
                                                        $chromafabricationnotes .= $descriptionfab . "\r\n";
                                                    } elseif ($quoteproduct['productCode'] == "GLASS") {
                                                        $glassfabricationnotes .= $descriptionfab . "\r\n";
                                                    }
                                                }

                                            }

                                        }

                                    }
                                }
                            }
                            //}
                        }
                    }
                }

                //add quoteproduct id
                $panelid_parts = explode(":", $quoteproduct_panelid);
                $quoteprodid = $doc -> createElement("quoteproductID");
                $quoteprodid -> appendChild($doc -> createTextNode($panelid_parts[0]));
                $b -> appendChild($quoteprodid);

                //FS means that there are no cuts, so display the full sized panel.  Otherwise, place cut
                if ($haspanel == false) {

                    $panel = $doc -> createElement("panel");
                    $b -> appendChild($panel);

                    $panelid = $doc -> createElement("PanelID");
                    $panelid -> appendChild($doc -> createTextNode($this -> sheetuid_value));
                    $panel -> appendChild($panelid);

                    $panelwidth = $doc -> createElement("width");
                    $panelwidth -> appendChild($doc -> createTextNode($quoteproduct['Width']));
                    $panel -> appendChild($panelwidth);

                    $panelheight = $doc -> createElement("height");
                    $panelheight -> appendChild($doc -> createTextNode($quoteproduct['Height']));
                    $panel -> appendChild($panelheight);

                    $paneloptwidth = $doc -> createElement("optimizedwidth");
                    $paneloptwidth -> appendChild($doc -> createTextNode($quoteproduct['optimizedwidth']));
                    $panel -> appendChild($paneloptwidth);

                    $paneloptheight = $doc -> createElement("optimizedheight");
                    $paneloptheight -> appendChild($doc -> createTextNode($quoteproduct['optimizedlength']));
                    $panel -> appendChild($paneloptheight);

                    $panelcutid = $doc -> createElement("cutID");
                    $panelcutid -> appendChild($doc -> createTextNode("FS"));
                    $panel -> appendChild($panelcutid);

                    $panelmatdirdesc = $doc -> createElement("materialDirectionDescription");
                    $panelmatdirdesc -> appendChild($doc -> createTextNode($quoteproduct['sheetMaterialDirectionDesc']));
                    $panel -> appendChild($panelmatdirdesc);
                }

                //finalize
                $r -> appendChild($b);
            }

        }

        if ($in_xml == "ICEpack.xml") {

            //currency symbol front and back
            $currency_unused = $doc -> createElement("currency_symbol_front");
            $start -> appendChild($currency_unused);

            $currency = $doc -> createElement("currency_symbol_back");
            $currency -> appendChild($doc -> createTextNode($_SESSION['to_export_icepack']['misc']['currency_symbol_back']));
            $start -> appendChild($currency);

            //cutlist = description, cutitems
            $cutlist = $doc -> createElement("cutlists");
            $start -> appendChild($cutlist);

            //cutlists per sheet.  Only display if there is at least one cut for a sheet
            //$haspanels = false;
            $uniquecuts_arr = $cuts_arr = $cutdescr_arr = $uniquedescr_arr = array();
            $cutdetails_arr = array();

            //get cut/panel details for icepack
            foreach ($_SESSION['to_export_icepack'] as $index => $quoteproduct) {
                foreach ($quoteproduct as $key => $value) {
                    if ($key == "sheets" && $quoteproduct['pline'] != "3") {//
                        foreach ($quoteproduct['sheets'] as $sheetuidkey => $innervalue) {

                            foreach ($innervalue as $panelkey => $panelarr) {

                                if ($panelarr['cutid'] == "FS" || $quoteproduct['pline'] == 3) {
                                    continue;
                                }

                                $cutdescr_arr[$index] = "$quoteproduct[Description] $quoteproduct[Gauge] $quoteproduct[FrontFinishDescription] 
								$quoteproduct[BackFinishDescription] $quoteproduct[UV_Protection] $quoteproduct[discount] 
								$quoteproduct[TFM_SheetMaterialDirection]";
                                $cuts_arr[] = $panelarr['cutid'];
                                $cutdetails_arr[$panelarr['cutid']] = array("length" => $panelarr['length'], "width" => $panelarr['width'], "description" => "$quoteproduct[Description] $quoteproduct[Gauge] $quoteproduct[FrontFinishDescription] 
								$quoteproduct[BackFinishDescription] $quoteproduct[UV_Protection] $quoteproduct[discount] 
								$quoteproduct[TFM_SheetMaterialDirection]", "patterndirection" => $panelarr['patterndirection']);
                                $sheetuids_arr[] = array("cutid" => $panelarr['cutid'], "sheetuid" => $sheetuidkey);
                                //TODO fix id because it's group, not sheetuid

                            }
                        }
                    }
                }
            }

            $uniquecuts_arr = array_unique($cuts_arr);
            $uniquedescr_arr = array_unique($cutdescr_arr);

            foreach ($uniquedescr_arr as $desckey => $descrval) {

                $cutlistgrp = $doc -> createElement("cutlist");
                $cutlistgrp -> setAttribute('id', $desckey);
                $cutlist -> appendChild($cutlistgrp);

                $cutdescr = $doc -> createElement("description");
                $cutdescr -> appendChild($doc -> createTextNode($descrval));
                $cutlistgrp -> appendChild($cutdescr);

                foreach ($uniquecuts_arr as $cutkey => $cut_id) {

                    if (trim($cutdetails_arr[$cut_id]['description']) == trim($descrval)) {

                        //get count of sheets with this cut
                        $cuts_count = 0;
                        foreach ($sheetuids_arr as $key => $value_arr) {
                            if ($value_arr['cutid'] == $cut_id) {
                                $cuts_count++;
                            }
                        }

                        $cutitems = $doc -> createElement("cutitems");
                        $cutlistgrp -> appendChild($cutitems);

                        $cutprice = $doc -> createElement("cutPrice");
                        $cutprice -> appendChild($doc -> createTextNode(".003/mm"));
                        //(todo when fab added)
                        $cutitems -> appendChild($cutprice);

                        $cutqty = $doc -> createElement("cutQty");
                        $cutqty -> appendChild($doc -> createTextNode("Cut $cut_id ($cuts_count)"));
                        $cutitems -> appendChild($cutqty);

                        $cutsize = $doc -> createElement("cutSize");
                        $cutsize -> appendChild($doc -> createTextNode($cutdetails_arr[$cut_id]['length'] . " x " . $cutdetails_arr[$cut_id]['width'] . " mm"));
                        $cutitems -> appendChild($cutsize);

                        $cutdiscount = $doc -> createElement("discount");
                        $cutdiscount -> appendChild($doc -> createTextNode("0.0"));
                        //todo later
                        $cutitems -> appendChild($cutdiscount);

                        if ($cutdetails_arr[$cut_id]['patterndirection'] == "2") {//FIXME? is this right?  swapped Aug 14 2012 to make 2 = width...
                            $paneldir = "Parallel to " . $cutdetails_arr[$cut_id]['length'];
                        } elseif ($cutdetails_arr[$cut_id]['patterndirection'] == "1") {
                            $paneldir = "Parallel to " . $cutdetails_arr[$cut_id]['width'];
                        } else {
                            $paneldir = "";
                        }

                        $cutdir = $doc -> createElement("materialDirectionDescription");
                        $cutdir -> appendChild($doc -> createTextNode($paneldir));
                        $cutitems -> appendChild($cutdir);

                        $cuttotalprice = $doc -> createElement("totalcutPrice");
                        $cuttotalprice -> appendChild($doc -> createTextNode("0.0"));
                        //todo later
                        $cutitems -> appendChild($cuttotalprice);

                        //add sheetuids
                        foreach ($sheetuids_arr as $key => $value_arr) {
                            if ($value_arr['cutid'] == $cut_id) {
                                $sheetuids = $doc -> createElement("sheetUID");
                                $sheetuids -> appendChild($doc -> createTextNode($value_arr['sheetuid']));
                                //.": ".$value_arr['cutid']." = ".$cut_id
                                $cutitems -> appendChild($sheetuids);
                            }
                        }

                    }
                }
            }

            //$firephp->warn("CALL get_fabrication FROM qt_config");
            $setupfee = $doc -> createElement("setupfee");
            $setupfee -> appendChild($doc -> createTextNode("0.00"));
            $cutlist -> appendChild($setupfee);

            $totalFabCharge = $doc -> createElement("totalFabCharge");
            $totalFabCharge -> appendChild($doc -> createTextNode("0.00"));
            $cutlist -> appendChild($totalFabCharge);

            $showCutFee = $doc -> createElement("showCutFee");
            $showCutFee -> appendChild($doc -> createTextNode("0"));
            $cutlist -> appendChild($showCutFee);

            $showFab = $doc -> createElement("showFab");
            $showFab -> appendChild($doc -> createTextNode("0"));
            $cutlist -> appendChild($showFab);
        }

        //allHardware
        $hardware = $doc -> createElement("allHardware");
        $start -> appendChild($hardware);

        //ADD LeadTimes
        if ($in_xml == "SimpleFab.xml") {//&& $DEVSERVER

            //first get data
            $q_quote = db_query("SELECT * FROM qt_quotes WHERE id='$_SESSION[quote]';");
            $r_quote = mysql_fetch_assoc($q_quote);

            //get currency
            $currencyconvert = 1;
            if ($r_quote['currency_id'] > 0) {
                $q_cu = db_query("SELECT * FROM currency WHERE id = '$r_quote[currency_id]';");
                $r_cu = mysql_fetch_assoc($q_cu);
                //$currsymbol = "$r_cu[symbol]";
                $currencyconvert = $r_cu['ex_val'];
                $currsymbol = $r_cu['symbol'];
            }
            $get_fabrication = get_fabrication($r_quote['catalog_id'], $currencyconvert, $currsymbol,$_SESSION['lang']);

            $fabLeadTimes = $doc -> createElement("fabLeadTimes");
            $start -> appendChild($fabLeadTimes);

            $manHoursFabrication = $doc -> createElement("manHoursFabrication");
            $fabLeadTimes -> appendChild($manHoursFabrication);

            $mhfID = $doc -> createElement("id");
            $mhfID -> appendChild($doc -> createTextNode("5000"));
            $manHoursFabrication -> appendChild($mhfID);

            $mhfMin = $doc -> createElement("minutes");
            $mhfMin -> appendChild($doc -> createTextNode(round($get_fabrication['leadtimes']['total_manmin'])));
            $manHoursFabrication -> appendChild($mhfMin);

            $cnc = $doc -> createElement("cnc");
            $fabLeadTimes -> appendChild($cnc);

            $cncID = $doc -> createElement("id");
            $cncID -> appendChild($doc -> createTextNode("5002"));
            $cnc -> appendChild($cncID);

            $cncMin = $doc -> createElement("minutes");
            $cncMin -> appendChild($doc -> createTextNode(round($get_fabrication['leadtimes']['cnch'])));
            $cnc -> appendChild($cncMin);

            $oven = $doc -> createElement("oven");
            $fabLeadTimes -> appendChild($oven);

            $ovenID = $doc -> createElement("id");
            $ovenID -> appendChild($doc -> createTextNode("5004"));
            $oven -> appendChild($ovenID);

            $ovenMin = $doc -> createElement("minutes");
            $ovenMin -> appendChild($doc -> createTextNode(round($get_fabrication['leadtimes']['ovenh'])));
            $oven -> appendChild($ovenMin);

            $vacTables = $doc -> createElement("vacTables");
            $fabLeadTimes -> appendChild($vacTables);

            $vtID = $doc -> createElement("id");
            $vtID -> appendChild($doc -> createTextNode("5006"));
            $vacTables -> appendChild($vtID);

            $vtMin = $doc -> createElement("minutes");
            $vtMin -> appendChild($doc -> createTextNode(round($get_fabrication['leadtimes']['vacth'])));
            $vacTables -> appendChild($vtMin);

            $lineBend = $doc -> createElement("lineBend");
            $fabLeadTimes -> appendChild($lineBend);

            $lbID = $doc -> createElement("id");
            $lbID -> appendChild($doc -> createTextNode("5008"));
            $lineBend -> appendChild($lbID);

            $lbMin = $doc -> createElement("minutes");
            $lbMin -> appendChild($doc -> createTextNode(round($get_fabrication['leadtimes']['linebendh'])));
            $lineBend -> appendChild($lbMin);

            $sawingFabrication = $doc -> createElement("sawingFabrication");
            $fabLeadTimes -> appendChild($sawingFabrication);

            $sfID = $doc -> createElement("id");
            $sfID -> appendChild($doc -> createTextNode("5010"));
            $sawingFabrication -> appendChild($sfID);

            $sfMin = $doc -> createElement("minutes");
            $sfMin -> appendChild($doc -> createTextNode(round($get_fabrication['leadtimes']['sawh'])));
            $sawingFabrication -> appendChild($sfMin);

            $frees = $doc -> createElement("frees");
            $fabLeadTimes -> appendChild($frees);

            $fID = $doc -> createElement("id");
            $fID -> appendChild($doc -> createTextNode("5012"));
            $frees -> appendChild($fID);

            $fMin = $doc -> createElement("minutes");
            $fMin -> appendChild($doc -> createTextNode(round($get_fabrication['leadtimes']['freesh'])));
            $frees -> appendChild($fMin);

            $handtools = $doc -> createElement("handtools");
            $fabLeadTimes -> appendChild($handtools);

            $hID = $doc -> createElement("id");
            $hID -> appendChild($doc -> createTextNode("5014"));
            $handtools -> appendChild($hID);

            $hMin = $doc -> createElement("minutes");
            $hMin -> appendChild($doc -> createTextNode(round($get_fabrication['leadtimes']['handtools'])));
            $handtools -> appendChild($hMin);

            //now put all fabrication notes into easy to use elements
            $sapFabrication = $doc -> createElement("sapFabrication");
            $start -> appendChild($sapFabrication);

            $variaFabrication = $doc -> createElement("variaFabrication");
            $variaFabrication -> appendChild($doc -> createTextNode("$variafabricationnotes"));
            $sapFabrication -> appendChild($variaFabrication);

            $chromaFabrication = $doc -> createElement("chromaFabrication");
            $chromaFabrication -> appendChild($doc -> createTextNode("$chromafabricationnotes"));
            $sapFabrication -> appendChild($chromaFabrication);

            $glassFabrication = $doc -> createElement("glassFabrication");
            $glassFabrication -> appendChild($doc -> createTextNode("$glassfabricationnotes"));
            $sapFabrication -> appendChild($glassFabrication);
        }

        if ($in_xml == "ICEpack.xml") {//these are only

            //Freight
            $freightprice = (is_numeric($_SESSION['to_export_icepack']['misc']['Freight']) ? $_SESSION['to_export_icepack']['misc']['Freight'] : "0.0");

            $freight = $doc -> createElement("Freight");
            $start -> appendChild($freight);

            $freight_descr = $doc -> createElement("description");
            $freight_descr -> appendChild($doc -> createTextNode("Freight Charge"));
            $freight -> appendChild($freight_descr);

            $freight_price = $doc -> createElement("price");
            $freight_price -> appendChild($doc -> createTextNode($freightprice));
            $freight -> appendChild($freight_price);

            //packaging, including freight
            $packagingprice = (is_numeric($_SESSION['to_export_icepack']['misc']['Packaging']) ? $_SESSION['to_export_icepack']['misc']['Packaging'] : "0.0");

            $packaging = $doc -> createElement("Packaging");
            $start -> appendChild($packaging);

            $packaging_descr = $doc -> createElement("description");
            $packaging_descr -> appendChild($doc -> createTextNode("Packaging Charge"));
            $packaging -> appendChild($packaging_descr);

            $packaging_price = $doc -> createElement("price");
            $packaging_price -> appendChild($doc -> createTextNode($packagingprice));
            $packaging -> appendChild($packaging_price);

            $smallpallet = $doc -> createElement("smallpallets");
            $smallpallet -> appendChild($doc -> createTextNode($_SESSION['to_export_icepack']['misc']['smallpallet']));
            $packaging -> appendChild($smallpallet);

            $largepallet = $doc -> createElement("largepallets");
            $largepallet -> appendChild($doc -> createTextNode($_SESSION['to_export_icepack']['misc']['largepallet']));
            $packaging -> appendChild($largepallet);

            $largepallet = $doc -> createElement("crate");
            $largepallet -> appendChild($doc -> createTextNode($_SESSION['to_export_icepack']['misc']['crate']));
            $packaging -> appendChild($largepallet);

            //free text labels 1-10
            foreach ($_SESSION['to_export_icepack']['misc'] as $freeentry => $textvalue) {
                if (preg_match("/extra_price_label/", $freeentry) || preg_match("/extra_price_value/", $freeentry)) {
                    $add_freetext = $doc -> createElement($freeentry);
                    $add_freetext -> appendChild($doc -> createTextNode($textvalue));
                    $start -> appendChild($add_freetext);
                }
            }

            //VAT
            $vat = $doc -> createElement("VAT");
            $start -> appendChild($vat);

            $vat_descr = $doc -> createElement("description");
            $vat_descr -> appendChild($doc -> createTextNode("VAT Charge"));
            $vat -> appendChild($vat_descr);

            $vat_percent = $doc -> createElement("VAT_percent");
            $vat_percent -> appendChild($doc -> createTextNode($_SESSION['to_export_icepack']['misc']['VAT_percent']));
            $vat -> appendChild($vat_percent);

            $vat_dollar = $doc -> createElement("VAT_dollar");
            $vat_dollar -> appendChild($doc -> createTextNode($_SESSION['to_export_icepack']['misc']['VAT_dollar']));
            $vat -> appendChild($vat_dollar);
        }

        //extra
        $extra = $doc -> createElement("extra");
        $start -> appendChild($extra);

        $total_price = $doc -> createElement("total_price");
        $total_price -> appendChild($doc -> createTextNode($_SESSION['to_export_icepack']['misc']['total_price']));
        $extra -> appendChild($total_price);

        //write the xml files
        $absolutePath = $_SERVER['DOCUMENT_ROOT'] . "/";
        if ($in_xml == "ICEpack.xml") {
            $doc -> save($absolutePath . "icepacks/" . $in_tempnum . "ICEpack.xml");
            //my3form_europe/icepacks/ it is currently placed in public_html
        } elseif ($in_xml == "SimpleFab.xml") {
            $doc -> save($absolutePath . "icepacks/" . $in_tempnum . "SimpleFab.xml");
            //my3form_europe/icepacks/ it is currently placed in public_html
        }

        
    }

    private function convert_to_pidkey($in_column, $in_prodid) {
        //mail("ict@3form.eu", "PID TEST 2", $in_prodid);
        //get name
        $q_pidkey = db_query("SELECT name FROM products WHERE id='$in_prodid'");
        $r_pidkey = mysql_fetch_assoc($q_pidkey);

        //get pidcode to get qt_raw_materials
        $pid_code = convertPIDField($in_column, "", $r_pidkey['name']);

        //**Do an exception for Birch
        if ($in_prodid == 727) {
            //**This will take birch_grove in pid)_key as brich_glass doesn't exist there
            $pid_code = convertPIDField($in_column, "", "birch_grove");
        }
        if ($in_prodid == 717 || $in_prodid == 718 || $in_prodid == 719 || $in_prodid == 720) {
            //**This will take raw in pid_key as raw_brush doesn't exist there
            $pid_code = convertPIDField($in_column, "", "raw");
        }

        return $pid_code;
    }
    
}


?>