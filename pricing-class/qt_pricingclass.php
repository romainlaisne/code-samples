<?php

//
class SheetPrice {

    //Sheet pricing criteria
    var $pricegroup = "";
    //A,B,C,D or custom
    var $step = "1s";
    //1s or 2s.  1s is default and will be overwritten if a 2s product is used

    //List of special pricing items - Hardcoded
    var $premiumfinish_arr = array();

    //Fabric
    var $fabriclist = array();
    //list of all fabrics
    var $sheetfabrics = array();
    //list of fabrics in current sheet
    var $sheetfabricpg = array();
    //pricegroups of any fabrics used.  this is sorted to find highest pg

    //Pure Color
    var $purecolorlist = array();
    //list of all pure colors
    var $sheetpcs = array();
    //list of pure colors in current sheet

    //High Res
    var $highreslist = array();
	var $marrakeshlist = array("483","484","569");
    //list of all highres
    var $hashighres = false;
    var $hasvariareflect = false;
    var $hasproduct = false;
	var $hasmarrakesh = false;

    //Varia adders
    var $adder_uv = 0;
    var $adder_premiumff = 0;
    var $adder_premiumbf = 0;
    var $adder_pc = 0;
    var $adder_fabric = 0;
    var $adder_texture = 0;
    var $adder_highres = 0;
	var $adder_marrakesh = 0;
    var $adder_varia_mirror_graphics = 0;
    var $adder_varia_reflect = 0;
    var $adder_varia_texture_plus = 0;
    var $adder_varia_arcs = 0;

    //Chroma Adders
    var $adder_chroma_colors = 0;
    var $adder_reflect = 0;
    var $adder_morph = 0;

    //Glass adders
    var $adder_glass_reflect = 0;
    var $adder_glass_whiteout = 0;

    //Glass Adders and fabrication
    var $adder_lowiron = 0;
    var $fab_setupcharge = 0;

    //final outputs
    var $product = "";
    //used for products or fabrics where there is no fabrics
    var $baseprice = 0;
    //price before any adders are added
    var $unitprice = 0;
    //total price per sheet

    function __construct() {
        //** Check panel sizes used
        //$this -> checkForcedSize();
    }

    public function get_varia_price($in_texture, $in_texture_plus = "", $in_layers, $in_catalog, $in_sheetsize, $in_gauge, $in_ff, $in_bf, $in_uv) {
        global $firephp;
        //$firephp->warn($in_texture, "Texture");
        //$firephp->log($in_layers, "LAYERS");

        if ($in_texture != "" && $in_texture_plus == "") {//get texture adder now because we also need to get price group before adder section (if applicable)

            $this -> adder_texture = $this -> get_adder_price($in_catalog, $in_sheetsize, "texture");
            //get texture adder while we are at it

            if ($in_layers == "") {
                $this -> pricegroup = "A";
                //Texture, by itself, is PriceGroup A //TODO get Pricegroup of Texture from DB
            }
            //$firephp->info("texture IN");
        }

        //**Romain,19oct2012, new collection 2012
        if ($in_texture_plus != "") {//get texture plus adder now because we also need to get price group before adder section (if applicable)

            $this -> adder_varia_texture_plus = $this -> get_adder_price($in_catalog, $in_sheetsize, "varia_texture_plus");
            //get texture adder while we are at it

            if ($in_layers == "") {
                $this -> pricegroup = "A";
                //Texture, by itself, is PriceGroup A //TODO get Pricegroup of Texture from DB
            }
            //$firephp->info("texture + IN");

        }

        //get fabric list
        $this -> get_fabrics();

        //get product list
        $this -> get_purecolors();

        //get highres list
        $this -> get_highres();

        //get mirror graphics list
        $this -> get_mirrorgraphics();

        //******* Loop through the products
        $layer = explode(" ", $in_layers);
        foreach ($layer as $key => $prodid) {

            //**If Reflect varia is present (712) calculate adder
            /*if ($prodid==712){
             $this->adder_varia_reflect = $this->get_adder_price($in_catalog, $in_sheetsize, "varia_reflect");//get texture adder while we are at it

             }*/

            if (in_array($prodid, $this -> purecolorlist)) {//get purecolor count
                $this -> sheetpcs[] = "$prodid";

            } elseif (in_array($prodid, $this -> fabriclist)) {//get fabric count and price group of highest cost fabric
                $this -> sheetfabrics[] = "$prodid";

                //get all fabric pricegroups
                $this -> sheetfabricpg[] = $this -> get_pricegroup($prodid);

                if ($this -> product == "") { $this -> product = $prodid;
                }//only set if empty.  This will be overwritten if product comes up

            } else {//standard product
                //**Romain,19oct2012, new collection 2012
                //**reflect with color
                if ($prodid == 712) {
                    $this -> hasvariareflect = true;
                    //$this->adder_varia_reflect = $this->get_adder_price($in_catalog, $in_sheetsize, "varia_reflect");//get texture adder while we are at it
                    //continue;
                } else {
                    $this -> hasproduct = true;

                    //**For now, define all to the current product
                    $this -> product = $prodid;
                    //if custom pricing, we need product id
                    //get price group
                    $this -> pricegroup = $this -> get_pricegroup($prodid);
                    //get 1s or 2s
                    $this -> get_step($prodid);
                }

            }

            if (in_array($prodid, $this -> highreslist)) {//get highres
                $this -> hashighres = true;
            }
			
			if (in_array($prodid, $this -> marrakeshlist)) {//get marrakesh
                $this -> hasmarrakesh = true;
            }
			
			
            if (in_array($prodid, $this -> mirrorgraphicslist)) {//get mirror graphics
                $this -> hasmirrorgraphics = true;
            }
        }//end foreach

        //**If only fabrics and pure colors then pricegroup will not be set
        if ($this -> pricegroup == "") {

            //Get highest value of fabric if exists
            if (!empty($this -> sheetfabricpg)) {
                sort($this -> sheetfabricpg);
                $this -> pricegroup = array_pop($this -> sheetfabricpg);
                //get highest pricegroup fabric
            } else {
                $this -> pricegroup = "A";
                //TODO get Pure Color PriceGroup from database
            }
        }

        //** REFLECT SPECIALS
        //**The abobe define price without reflect. Define with reflect below if needed.
        //**Define price group for product only or reflect + combinations
        if ($this -> hasvariareflect == true && $this -> hasproduct == false) {
            //**Reflect without other product, choose reflect group
            $this -> product = 712;
            //get price group
            $this -> pricegroup = $this -> get_pricegroup(712);
            //get 1s or 2s
            $this -> get_step(712);

        } else if ($this -> hasvariareflect == true && $this -> hasproduct == true) {
            //**Keep the defined previously for the product group
            //**Add reflect adder
            $this -> adder_varia_reflect = $this -> get_adder_price($in_catalog, $in_sheetsize, "varia_reflect");

        } else if ($this -> hasvariareflect == true && $this -> hasproduct == false && (count($this -> sheetpcs) > 0 || count($this -> sheetfabrics) > 0)) {
            //**Reflect with color or fabric
            $this -> product = 712;
            //get price group
            $this -> pricegroup = $this -> get_pricegroup(712);
            //get 1s or 2s
            $this -> get_step(712);
        }

        //********* Finally get base price
        if ($this -> pricegroup == "custom") {//if custom
            $this -> baseprice = $this -> get_base_price($in_catalog, $in_sheetsize, $in_gauge, "qt_special_pricing", "product_id", $this -> product, "1");
        } else {
            $this -> baseprice = $this -> get_base_price($in_catalog, $in_sheetsize, $in_gauge, "qt_pricing", "price_group", $this -> pricegroup, "1");
        }

        $firephp -> info($this -> baseprice, "PRICEGROUP");

        /************************
         * Get adders
         ***********************/

        //UV
        if ($in_uv == "1") {
            $this -> adder_uv = $this -> get_adder_price($in_catalog, $in_sheetsize, "uv");
        }

        //High Res
        if ($this -> hashighres == true) {
            $this -> adder_highres = $this -> get_adder_price($in_catalog, $in_sheetsize, "highres");
        }
		
		//Marrakesh
        if ($this -> hasmarrakesh == true) {
            $this -> adder_marrakesh = $this -> get_adder_price($in_catalog, $in_sheetsize, "marrakesh");
        }

        //**Mirror graphic
        if ($this -> hasmirrorgraphics == true) {
            $this -> adder_varia_mirror_graphics = $this -> get_adder_price($in_catalog, $in_sheetsize, "varia_mirror_graphics");
        }

        //Premium Finishes.  Both sides need to be calculated
        //**Romain, 21-02-2012, change function name as it is now use for premium and plus finishes
        $this -> premiumfinish_arr = getspecialfinishes("premium");
        // array("08", "09", "11") //Markerboard Plus, SFX Frost, renewable patina

        if (in_array($in_ff, $this -> premiumfinish_arr)) {
            $this -> specialff = $this -> get_adder_price($in_catalog, $in_sheetsize, "premium_finish");
        }
        if (in_array($in_bf, $this -> premiumfinish_arr)) {
            $this -> specialbf = $this -> get_adder_price($in_catalog, $in_sheetsize, "premium_finish");
        }

        //check for plus finishes
        $this -> plusfinish_arr = getspecialfinishes("plus");
        //("13", "14", "15","16", "17", "18") //Grain, Nappa, Transit, Grid, Brush, Velvet

        if (in_array($in_ff, $this -> plusfinish_arr)) {
            $this -> specialff = $this -> get_adder_price($in_catalog, $in_sheetsize, "plus_finish");
        }
        if (in_array($in_bf, $this -> plusfinish_arr)) {
            $this -> specialbf = $this -> get_adder_price($in_catalog, $in_sheetsize, "plus_finish");
        }

        //**Romain, 21-02-2012, handle new Varia/Chroma+ finishes
        //Plus Finishes.  Both sides need to be calculted
        /*$this->plusfinish_arr = getspecialfinishes("plus");// //Grain, Nappa, Transit, Grid, Brush, Velvet / collection 2012

         if(in_array($in_ff,$this->plusfinish_arr)){
         $this->premiumff = $this->get_adder_price($in_catalog, $in_sheetsize, "plus_finish");
         }
         if(in_array($in_bf,$this->plusfinish_arr)){
         $this->premiumbf = $this->get_adder_price($in_catalog, $in_sheetsize, "plus_finish");
         }*/

        //Pure Color
        //$firephp->info(count($this->sheetpcs),"AMOUNT COLORS");
        if (count($this -> sheetpcs) > 0) {

            $first = true;

            foreach ($this->sheetpcs as $key => $value) {
                //Get 1s or 2s
                if ($this -> step == "1s" && $first == true) {
                    $first = false;
                    if ($this -> product == "") {//If only Pure Colors, then do not double count first
                        //$firephp->info($value,"SKIP FIRST 1st color");
                        continue;
                    }
                    //$firephp->info($key,"Add PC adder");

                    $this -> adder_pc = $this -> get_adder_price($in_catalog, $in_sheetsize, "first_purecolor_1s");
                } elseif ($this -> step == "2s" && $first == true) {
                    $first = false;
                    $this -> adder_pc = $this -> get_adder_price($in_catalog, $in_sheetsize, "first_purecolor_2s");
                } else {
                    $additionalpc = $this -> get_adder_price($in_catalog, $in_sheetsize, "additional_purecolor");
                    $this -> adder_pc = $this -> adder_pc + $additionalpc;
                }
            }
        }

        //Fabric
        if (count($this -> sheetfabrics) > 0) {

            $first = true;

            foreach ($this->sheetfabrics as $key => $value) {
                if ($first == true) {
                    $first = false;
                    if (in_array($this -> product, $this -> fabriclist)) {//fabrics as the only product do not get double counted
                        continue;
                    }
                    $this -> adder_fabric = $this -> get_adder_price($in_catalog, $in_sheetsize, "first_fabric_1s_or_2s");
                } else {
                    $additionalfabric = $this -> get_adder_price($in_catalog, $in_sheetsize, "additional_fabric");
                    $this -> adder_fabric = $this -> adder_fabric + $additionalfabric;
                }
            }
        }

        /**************************
         * combine all prices
         * ************************/
        $this -> unitprice = $this -> baseprice + $this -> adder_texture + $this -> adder_varia_texture_plus + $this -> adder_uv + $this -> specialff + $this -> specialbf + $this -> adder_pc + $this -> adder_fabric + $this -> adder_highres + $this -> adder_marrakesh + $this -> adder_varia_reflect + $this -> adder_varia_mirror_graphics;

        $firephp -> warn($this -> baseprice, "this->baseprice");
        $firephp -> warn($this -> adder_texture, "this->adder_texture");
        $firephp -> warn($this -> adder_varia_texture_plus, "this->adder_varia_texture_plus");
        $firephp -> warn($this -> adder_uv, "this->adder_uv");
        $firephp -> warn($this -> specialff, "this->specialff");
        $firephp -> warn($this -> specialbf, "this->specialbf");
        $firephp -> warn($this -> adder_pc, "this->adder_pc");
        $firephp -> warn($this -> adder_fabric, "this->adder_fabric");
        $firephp -> warn($this -> adder_highres, "this->adder_highres");
		$firephp -> warn($this -> adder_marrakesh, "this->adder_marrakesh");
        $firephp -> warn($this -> adder_varia_reflect, "this->adder_varia_reflect");
        $firephp -> warn($this -> adder_varia_mirror_graphics, "this->adder_varia_mirror_graphics");

        //$this->unitprice = $this->product; //$this->adder_pc;
        return $this -> unitprice;
    }

    //get chroma price
    public function get_chroma_price($in_layers, $in_catalog, $in_sheetsize, $in_gauge, $in_ff, $in_bf) {
        global $firephp;
        //Put Chroma layers into array
        $layer_arr = explode(" ", $in_layers);

        //convert reflect gauge to standard size gauge if needed
        $reflectgaugeconvert_arr = array("18" => "06", "19" => "08", "20" => "09");
        //deduct the 3.1mm to get to base gauge size
        if (in_array("597", $layer_arr)) {
            $in_gauge = $reflectgaugeconvert_arr[$in_gauge];

            //pop off last layer of array(which must be reflect) so it isn't counted when adding up color layers
            $reflect_arr = array_pop($layer_arr);

            //get chroma reflect adder while we are at it
            $this -> adder_reflect = $this -> get_adder_price($in_catalog, $in_sheetsize, "chroma_reflect");
        }

        //**Romain, 15May2012, add Chroma Morph adder
        if (in_array("680", $layer_arr)) {
            //get chroma morph adder
            $this -> adder_morph = $this -> get_adder_price($in_catalog, $in_sheetsize, "morph");
        }
        if (in_array("681", $layer_arr)) {
            //get chroma morph adder
            $this -> adder_morph = $this -> get_adder_price($in_catalog, $in_sheetsize, "morph");
        }
        if (in_array("682", $layer_arr)) {
            //get chroma morph adder
            $this -> adder_morph = $this -> get_adder_price($in_catalog, $in_sheetsize, "morph");
        }

        //check for plus finishes
        $this -> plusfinish_arr = getspecialfinishes("plus");
        //("13", "14", "15","16", "17", "18") //Grain, Nappa, Transit, Grid, Brush, Velvet

        if (in_array($in_ff, $this -> plusfinish_arr)) {
            $this -> specialff = $this -> get_adder_price($in_catalog, $in_sheetsize, "plus_finish");
        }
        if (in_array($in_bf, $this -> plusfinish_arr)) {
            $this -> specialbf = $this -> get_adder_price($in_catalog, $in_sheetsize, "plus_finish");
        }
        
        //**Ren. matte for Seaming adder. 2x extra pure color price.
        //$firephp->warn($in_bf,"#####IN BF");
        if ($in_bf=="88") {
            $additionalpc = $this -> get_adder_price($in_catalog, $in_sheetsize, "chroma_pc");
            $this -> adder_chroma_colors = $this -> adder_chroma_colors + ($additionalpc*2);
            $firephp->warn($this -> adder_chroma_colors,"Adder finish Seaming");
        }

        //get base price
        $this -> baseprice = $this -> get_base_price($in_catalog, $in_sheetsize, $in_gauge, "qt_pricing", "pline", "2", "2");

        //get chroma color adder(s)
        if (count($layer_arr) > 1) {
            foreach ($layer_arr as $key => $value) {
                if ($key == 0) {
                    continue;
                }//skip first
                $additionalpc = $this -> get_adder_price($in_catalog, $in_sheetsize, "chroma_pc");
                $this -> adder_chroma_colors = $this -> adder_chroma_colors + $additionalpc;
            }
        }

        //combine all prices
        $this -> unitprice = $this -> baseprice + $this -> adder_reflect + $this -> specialff + $this -> specialbf + $this -> adder_chroma_colors + $this -> adder_morph;

        return $this -> unitprice;
    }

    //get Glass price
    public function get_glass_price($in_prod, $in_catalog, $in_length, $in_width, $in_uom) {
        global $firephp;
        //convert in to mm if needed
        if ($in_uom == "in") {
            $in_length = $in_length * 25.4;
            $in_length = number_format($in_length, 2);
            $in_width = $in_width * 25.4;
            $in_width = number_format($in_width, 2);
        }

        //get sq m
        $sqm = ($in_length / 1000) * ($in_width / 1000);

        //get sheetsize (unique for Glass)
        if ($sqm < 0.25) {
            $sheetsize = "1";
        } elseif ($sqm < 0.5) {
            $sheetsize = "2";
        } elseif ($sqm < 1.0) {
            $sheetsize = "3";
        } else {
            $sheetsize = "4";
        }

        //get price group
        $this -> pricegroup = $this -> get_pricegroup($in_prod);
        $firephp -> warn($this -> pricegroup, "Glass price group");

        //get base price
        $this -> baseprice = $this -> get_base_price($in_catalog, $sheetsize, "0", "qt_pricing", "price_group", $this -> pricegroup, "3");

        //if sheetsize = 4, then multipley baseprice by sqm
        if ($sheetsize == "4") {
            $this -> baseprice = $this -> baseprice * $sqm;
            $this -> baseprice = number_format($this -> baseprice, 2);
            $this -> baseprice = str_replace(",", "", $this -> baseprice);
            //decimal in database cannot handle comma
        }

        //get adder for low iron glass (cascade milk)
        if ($in_prod == "627") {
            $this -> adder_lowiron = $this -> get_adder_price($in_catalog, "0", "glass_low_iron");
            $this -> adder_lowiron = $this -> adder_lowiron * $sqm;
            $this -> adder_lowiron = number_format($this -> adder_lowiron, 2);
            $this -> adder_lowiron = str_replace(",", "", $this -> adder_lowiron);
            //decimal in database cannot handle comma
        }

        //**Romain, 24sept2012, Add adder reflect for glass
        if ($in_prod == "694") {
            $this -> adder_glass_reflect = $this -> get_adder_price($in_catalog, "0", "glass_reflect");
            $this -> adder_glass_reflect = $this -> adder_glass_reflect * $sqm;

        }

        //**Romain, 24sept2012, Add adder white out for glass
        if ($in_prod == "693") {
            $this -> adder_glass_whiteout = $this -> get_adder_price($in_catalog, "0", "glass_whiteout");
            $this -> adder_glass_whiteout = $this -> adder_glass_whiteout * $sqm;

        }

        //Now get fabrication rates for cutting
        $glass_cutting = new Fabrication();
        $fab_total = $glass_cutting -> get_waterjet_price($in_catalog, "3", $in_width, $in_length);
        $_SESSION['glassPanelCuttingAmount']=$_SESSION['glassPanelCuttingAmount']+$fab_total;
        //currently only pline 3 is set for water jet cutting

        /*
         //get linear m for width and length
         $linear_width = ($in_width/1000);
         $linear_length = ($in_length/1000);

         //$this->fab_setupcharge = $this->get_fab_price($in_catalog, "3", "water_jet_cutting", "set_up_charge");
         $fab_perlinearmeter = $this->get_fab_price($in_catalog, "3", "water_jet_cutting", "per_linear_m_rate");
         $fab_mincutcharge = $this->get_fab_price($in_catalog, "3", "water_jet_cutting", "minimum_cut_charge");

         //calculate per linear m rate
         $ratetotal = ((2 * $linear_width) + (2 * $linear_length)) * $fab_perlinearmeter;
         $ratetotal = number_format($ratetotal, 2);
         $ratetotal = str_replace(",","",$ratetotal);//decimal in database cannot handle comma

         //find out if min charge or rate charge is more
         $fabcutcharge = ($fab_mincutcharge > $ratetotal ? $fab_mincutcharge : $ratetotal);

         //add up fabrication rates
         $this->fab_setupcharge = 0;//add this in quote, not here because it is only added once
         $fab_total = $this->fab_setupcharge + $fabcutcharge;
         */

        //build and return price
        $this -> unitprice = $this -> baseprice + $this -> adder_glass_reflect + $this -> adder_glass_whiteout + $this -> adder_lowiron + $fab_total;
        //$this->pricegroup." $sqm";//len: $in_length, wid: $in_width
        $firephp -> warn($this -> unitprice, "Glass unit price");
        return $this -> unitprice;
    }

    //get list of pure colors for adders (Varia)
    private function get_purecolors() {
        $q_pc = db_query("SELECT id FROM products WHERE type='film' AND pline='1' AND active='1' AND gname NOT IN (120, 121);");
        while ($r_pc = mysql_fetch_assoc($q_pc)) {
            $this -> purecolorlist[] = $r_pc['id'];
        }
    }

    //get list of fabrics for adders (Varia)
    private function get_fabrics() {
        //SELECT *  FROM `products` WHERE `type` LIKE 'fabric' and active = 1 and gname not in (120, 121)
        $q_fab = db_query("SELECT id FROM products WHERE type='fabric' AND pline='1' AND active='1' AND gname NOT IN (120, 121);");
        while ($r_fab = mysql_fetch_assoc($q_fab)) {
            $this -> fabriclist[] = $r_fab['id'];
        }
    }

    //get list of highres for adders (Varia)
    private function get_highres() {
        $q_hr = db_query("SELECT id FROM products WHERE (type='highres' OR gname='23') AND pline='1' AND active='1' AND gname NOT IN (120, 121);");
        while ($r_hr = mysql_fetch_assoc($q_hr)) {
            $this -> highreslist[] = $r_hr['id'];
        }
    }

    //get list of highres for adders (Varia)
    private function get_mirrorgraphics() {
        $q_hr = db_query("SELECT id FROM products WHERE family='mirror' AND pline='1' AND active='1';");
        while ($r_hr = mysql_fetch_assoc($q_hr)) {
            $this -> mirrorgraphicslist[] = $r_hr['id'];
        }
    }

    //get pricegroup of passed product id
    function get_pricegroup($in_prodid) {
        $q_pg = db_query("SELECT pricegroup FROM products WHERE id='$in_prodid';");
        $r_pg = mysql_fetch_assoc($q_pg);

        return $r_pg['pricegroup'];
    }

    //get step (1s or 2s) from passed product id
    private function get_step($in_prodid) {
        $q_step = db_query("SELECT step FROM products WHERE id='$in_prodid';");
        $r_step = mysql_fetch_assoc($q_step);

        $this -> step = $r_step['step'];
    }

    //get base price of product (either custom price or PG A,B,C,D)
    private function get_base_price($in_cat, $in_ss, $in_g, $in_table, $in_field, $in_search, $in_pline) {
        $q_pr = db_query("SELECT price FROM $in_table WHERE catalog_id='$in_cat' AND sheet_size_id='$in_ss' AND gauge_id='$in_g' 
			AND pline='$in_pline' AND $in_field='$in_search';");
        $r_pr = mysql_fetch_assoc($q_pr);

        return $r_pr['price'];
    }

    //get adders as needed
    private function get_adder_price($in_cat, $in_ss, $in_adderdescr) {
        $q_adder = db_query("SELECT adder_price FROM qt_adders WHERE catalog_id='$in_cat' AND sheet_size_id='$in_ss' AND adder_descr='$in_adderdescr' AND active='1';");
        $r_adder = mysql_fetch_assoc($q_adder);

        return $r_adder['adder_price'];
    }

    //get fabrication rates
    private function get_fab_price($in_cat, $in_pline, $in_type, $in_criteria) {
        $criteria = (trim($in_criteria) != "" ? " AND criteria='$in_criteria'" : "");
        //this field not required
        $q_fab = db_query("SELECT price FROM qt_fabrication WHERE catalog_id='$in_cat' AND pline='$in_pline' AND type='$in_type' $criteria AND active='1';");
        $r_fab = mysql_fetch_assoc($q_fab);

        return $r_fab['price'];
    }

}

class Fabrication {
    
    //initialize return array
    //private $fabdetails = array();//complete, price, descr
    public $completion;
    public $price;
    public $descr;
    private $fab_perlinearmeter = 0;
    private $fab_mincutcharge = 0;
    private $fab_setupcharge = 0;

    private function get_rates($in_pidkey, $in_pline, $in_catalog, $in_criteria, $in_type = "") {

        $type = (trim($in_type) != "" ? " AND type='$in_type'" : "");
        //this field not required
        $criteria = (trim($in_criteria) != "" ? " AND criteria='$in_criteria'" : "");
        //this field not required
        $pidkey = (trim($in_pidkey) != "" ? " AND pid_key='$in_pidkey'" : "");
        //this field not required
        $q_fab = db_query("SELECT price FROM qt_fabrication WHERE catalog_id='$in_catalog' AND pline='$in_pline' $pidkey $criteria $type AND active='1';");
        $r_fab = mysql_fetch_assoc($q_fab);

        return $r_fab['price'];
    }

    //water jet cut price
    public function get_waterjet_price($in_catalog, $in_pline, $in_width, $in_length) {

        $this -> price = 0;

        //get linear m for width and length
        $linear_width = ($in_width / 1000);
        $linear_length = ($in_length / 1000);

        //$this->fab_setupcharge = $this->get_fab_price($in_catalog, "3", "water_jet_cutting", "set_up_charge");
        $this -> fab_perlinearmeter = $this -> get_rates('', $in_pline, $in_catalog, "per_linear_m_rate", "water_jet_cutting");
        $this -> fab_mincutcharge = $this -> get_rates('', $in_pline, $in_catalog, "minimum_cut_charge", "water_jet_cutting");

        //calculate per linear m rate
        $ratetotal = ((2 * $linear_width) + (2 * $linear_length)) * $this -> fab_perlinearmeter;
        $ratetotal = number_format($ratetotal, 2);
        $ratetotal = str_replace(",", "", $ratetotal);
        //decimal in database cannot handle comma

        //find out if min charge or rate charge is more
        //$fabcutcharge = ($this->fab_mincutcharge > $ratetotal ? $this->fab_mincutcharge : $ratetotal);
        $fabcutcharge = $ratetotal;
        //mincutcharge includes setup charging, making it very unlikely, so just remove Aug 13, 2012

        //add up fabrication rates
        $this -> fab_setupcharge = 0;
        //add this in quote, not here because it is only added once
        $fab_total = $this -> fab_setupcharge + $fabcutcharge;

        $this -> price = $fab_total;

        return $this -> price;
    }

    //get panel saw rates
    public function get_panelsaw_price($in_pline, $in_catalog, $in_length, $in_width, $in_gauge) {//$in_qpid,
        global $firephp;
        //$firephp->warn("GET_PANELSAW_PRICE");
    
    
        $this -> price = $standardrate = $customrate = 0;

        $gauge = str_replace("mm", "", $in_gauge);
        //get number

        //get criteria
        $critera = "";
        if ($in_pline == "1") {
            $critera = "varia_standardsawcharge";
        } elseif ($in_pline == "2") {
            if ($gauge <= 15.8) {
                $critera = "chroma_x<=12.7mm";
            } elseif ($gauge <= 28.5) {
                $critera = "chroma_12.7mm<x<=25.4mm";
            } else {
                $critera = "chroma_25.4mm<x<=50.8mm";
            }
        }

        //get rates for saw charge (for all panels)
        $standardrate = $this -> get_rates("", $in_pline, $in_catalog, $critera);

        //get rates custom cut for varia (when not 4x8 or 4x10)
        if ($in_pline == "1") {

            //check tomake sure shapes are 4x8
            $standardwidth = $standardlength = false;
            $q_ss = db_query("SELECT * FROM qt_sheet_sizes");
            //get official measurment for 4x8
            while ($r_ss = mysql_fetch_assoc($q_ss)) {

                if ($r_ss['width'] == $in_width) {
                    $standardwidth = true;
                }
                if ($r_ss['length'] == $in_length) {
                    $standardlength = true;
                }
            }

            if ($standardwidth == false || $standardlength == false) {
                $critera = "varia_customsawcharge";
                //$customrate = $this->get_rates ("", $in_pline, $in_catalog, $critera);//TODO how does this work?
            }
        }

        $this -> price = $customrate + $standardrate;
        if ($in_pline==1){
            $firephp->warn($this -> price,"GET_PANELSAW_PRICE VARIA, Gauge $in_gauge $in_lengthx$in_width");
        }
        
        $this ->splitted_panelcutting_pricing($in_pline,$this -> price);

        return $this -> price;
    }

    //get fabrication rates
    public function get_fab_price($in_qpid, $in_pline, $in_catalog, $in_length, $in_width, $in_gauge, $in_fabcategory, $in_fd1, $in_fd2, $in_fd3, $in_fd4, $in_fd5, $in_fd6, $in_fd7, $in_fd8, $in_fpid) {
        global $firephp;
        $this -> price = 0;
        $rate = 0;

        $gauge = str_replace("mm", "", $in_gauge);
        //get number

        if ($in_fabcategory == "edgefinishes") {//per meter pricing for sanding/polishing/water sealing

            //get criteria: polishing nad water sealing have the same gauge critera

            $criteria = "";
            if ($in_pline == "1") {//Varia
                $criteria = ($gauge < 12.7 ? "<12.7mm" : ">=12.7mm");
            } elseif ($in_pline == "2") {//Chroma
                if ($gauge <= 12.7) {
                    $criteria = "<=12.7mm";
                } elseif ($gauge <= 25.4) {
                    $criteria = "12.7mm><=25.4mm";
                } else {
                    $criteria = "25.4><=50.8mm";
                }
            } elseif ($in_pline == "3") {//poured glass
                $criteria = "";
            }

            //cycle through each item, so put them in array
            $ef = array($in_fd1, $in_fd2, $in_fd3, $in_fd4);
            foreach ($ef as $key => $pidkey) {

                $m = ($key % 2 == 0 ? $in_width : $in_length);
                //A,C = width, B,D = length for meter
                $m = $m / 1000;
                //put mm into m

                if ($pidkey == "03") {//sanding
                    $rate = $this -> get_rates($pidkey, $in_pline, $in_catalog, "");
                    $this -> price = $this -> price + ($rate * $m);
                    $this->splitted_fab_pricing($in_pline, $rate * $m);
                } elseif ($pidkey == "04") {//polishing
                    $rate = $this -> get_rates($pidkey, $in_pline, $in_catalog, $criteria);
                    $this -> price = $this -> price + ($rate * $m);
                    $this->splitted_fab_pricing($in_pline, $rate * $m);
                } elseif ($pidkey == "05") {//water sealing
                    $rate = $this -> get_rates($pidkey, $in_pline, $in_catalog, $criteria);
                    $this -> price = $this -> price + ($rate * $m);
                    $this->splitted_fab_pricing($in_pline, $rate * $m);
                }
            }

        } elseif ($in_fabcategory == "edgedetails") {

            //cycle through each item, so put them in array
            $ed = array($in_fd1, $in_fd2, $in_fd3, $in_fd4, $in_fd5, $in_fd6, $in_fd7, $in_fd8);
            foreach ($ed as $key => $pidkey) {

                $m = ($key % 2 == 0 ? $in_width : $in_length);
                //A,C = width, B,D = length for meter
                $m = $m / 1000;
                //put mm into m

                if ($pidkey == "06") {//radius
                    $rate = $this -> get_rates($pidkey, $in_pline, $in_catalog, "");
                    $this -> price = $this -> price + ($rate * $m);
                    $this->splitted_fab_pricing($in_pline, $rate * $m);
                } elseif ($pidkey == "07") {//bevel/chamfer
                    $rate = $this -> get_rates($pidkey, $in_pline, $in_catalog, "");
                    $this -> price = $this -> price + ($rate * $m);
                    $this->splitted_fab_pricing($in_pline, $rate * $m);
                } elseif ($pidkey == "08") {//eased
                    $rate = $this -> get_rates($pidkey, $in_pline, $in_catalog, "");
                    $this -> price = $this -> price + ($rate * $m);
                    $this->splitted_fab_pricing($in_pline, $rate * $m);
                } elseif ($pidkey == "09") {//edge sealing
                    $rate = $this -> get_rates($pidkey, $in_pline, $in_catalog, "");
                    $this -> price = $this -> price + ($rate * $m);
                    $this->splitted_fab_pricing($in_pline, $rate * $m);
                }
            }

        } elseif ($in_fabcategory == "milling") {

            //cycle through each item, so put them in array
            $milling = array($in_fd1, $in_fd2, $in_fd3, $in_fd4);
            foreach ($milling as $key => $pidkey) {

                $m = ($key % 2 == 0 ? $in_width : $in_length);
                //A,C = width, B,D = length for meter
                $m = $m / 1000;
                //put mm into m

                if ($pidkey == "10") {//Miter Joint
                    $rate = $this -> get_rates($pidkey, $in_pline, $in_catalog, "");
                    $this -> price = $this -> price + ($rate * $m);
                    $this->splitted_fab_pricing($in_pline, $rate * $m);
                } elseif ($pidkey == "11") {//Wavy Bit
                    $rate = $this -> get_rates($pidkey, $in_pline, $in_catalog, "");
                    $this -> price = $this -> price + ($rate * $m);
                    $this->splitted_fab_pricing($in_pline, $rate * $m);
                } elseif ($pidkey == "12") {//Halp Lap Quirk
                    $rate = $this -> get_rates($pidkey, $in_pline, $in_catalog, "");
                    $this -> price = $this -> price + ($rate * $m);
                    $this->splitted_fab_pricing($in_pline, $rate * $m);
                } elseif ($pidkey == "17") {//Light seam
                    $rate = $this -> get_rates($pidkey, $in_pline, $in_catalog, "");
                    $this -> price = $this -> price + ($rate * $m);
                    $this->splitted_fab_pricing($in_pline, $rate * $m);
                }
            }

        } elseif ($in_fabcategory == "holes") {

            //get criteria: pricing based on <=4, <=16, and > 16 holes
            //**TODO remove below logic after pricing 2013 update
            /*if ($in_fd1 <= 4) {
                $criteria = "<=4 holes/panel";
            } elseif ($in_fd1 <= 16) {
                $criteria = "4 < x < 16 holes/panel";
            } else {
                $criteria = ">16 holes/panel";
            }*/
            //**End todo
            
            $criteria="hole";
            
            if ($in_pline == "3") {//poured glass
                $criteria = "pouredglassholes";
            }

            //get the rate based on the criteria
            $rate = $this -> get_rates($pidkey, $in_pline, $in_catalog, $criteria);
            $firephp->warn($rate,"HOLE RATE");
            //**IMPORTANT, Setup part is calculated apart in qt_fabfuncs, get_fabrication()
            //$setup = $this -> get_rates($pidkey, $in_pline, $in_catalog, "set_up_charge", "Holes");
            //$firephp->warn($setup, "HOLES SETUP");
            
            //get price of all holes
            $this -> price = $this -> price + ($rate * $in_fd1);
            $sf_total=($rate * $in_fd1);
            //$firephp->warn($sf_total, "HOLES");
            $this->splitted_fab_pricing($in_pline, $sf_total);

        } elseif ($in_fabcategory == "lines") {

            $criteria = "";
            $bend1 = $bend2 = 0;

            //linebend pricing must use data from the qt_fab_linebends table
            $q_lines = db_query("SELECT * FROM qt_fab_linebends WHERE quoteprodfab_id = '$in_fpid' AND bend_num <='$in_fd1' ORDER BY bend_num ASC;");
            while ($r_lines = mysql_fetch_assoc($q_lines)) {

                //this charge added only if v groove, and is in addition to normal bend charge
                if ($r_lines['groove'] == "13") {//V Groove also requiers gauge

                    if ($gauge <= 9.5) {
                        $criteria = "line_bend_vgroove_<=9.5mm";
                    } elseif ($gauge <= 19) {
                        $criteria = "line_bend_vgroove_9.5<x<=19mm";
                    } else {
                        $criteria = "line_bend_vgroove_19<x<=25.4mm";
                    }

                    $bendcharge = $this -> get_rates("", $in_pline, $in_catalog, $criteria);
                    $this -> price = $this -> price + $bendcharge;
                    $this->splitted_fab_pricing($in_pline, $bendcharge);
                    

                }
                //				elseif($r_lines['groove'] == "14"){//Non V Groove uses a standard rate
                //					$criteria = "line_bend_non_vgroove";
                //				}
                //this bend charge is always added
                $criteria = "line_bend_non_vgroove";
                $bendcharge = $this -> get_rates("", $in_pline, $in_catalog, $criteria);
                $firephp->warn($bendcharge,"line bend");
                $this -> price = $this -> price + $bendcharge;
                $this->splitted_fab_pricing($in_pline, $bendcharge);

                //get bend distance for bend1 and bend2
                if ($r_lines['bend_num'] == "1") {
                    $bend1 = $r_lines['from_zero'];
                    $setup = $this -> get_rates("", $in_pline, $in_catalog, "set_up_charge");
                    //$firephp->warn($setup,"set_up_charge line bend");
                    //$this -> price = $this -> price + $setup;
                    //$this->splitted_fab_pricing($in_pline, $setup);
                } elseif ($r_lines['bend_num'] == "2") {
                    $bend2 = $r_lines['from_zero'] - $bend1;
                    //$firephp->warn($bend2);
                    //**Remove below condition to make sure any second bend will be counted
                    //if ($bend2 != $bend1) {
                        $setup = $this -> get_rates("", $in_pline, $in_catalog, "set_up_charge_2bend");
                        //$firephp->warn($setup,"set_up_charge_2bend");
                        //$this -> price = $this -> price + $setup;
                        //$this->splitted_fab_pricing($in_pline, $setup);
                    //}
                }
            }//end while
            $firephp->warn($setup,"set_up_charge line bend");
            $this -> price = $this -> price + $setup;
            $firephp->warn($this -> price,"line bend total");
           
            $this->splitted_fab_pricing($in_pline, $setup);

            //get setup charge per bend job
            //$setup = $this->get_rates ("", $in_pline, $in_catalog, "set_up_charge");
            //
            ////sum up all prices
            //$this->price = $this->price + $setup;

        } elseif ($in_fabcategory == "otherfab") {
            //**TODO Romain, I am not sure how to handle here. Does it redefine all price?
            $this -> price = $in_fd2;

        } elseif ($in_fabcategory == "shapes") {

            if ($in_fd1 == "21") {//twists

                $numtwists = $in_fd2 - 1;
                $criteria = "";

                if ($in_pline == '1') {
                    if ($gauge <= 19) {
                        $criteria = "extra_twist_9.5_to_19";
                        $this -> price = $this -> get_rates($in_fd1, $in_pline, $in_catalog, "1st_twist_9.5_to_19");
                        $this->splitted_fab_pricing($in_pline, $this -> price);
                    } elseif ($gauge <= 25.4) {
                        $criteria = "extra_twist_19_to_25.4";
                        $this -> price = $this -> get_rates($in_fd1, $in_pline, $in_catalog, "1st_twist_19_to_25.4");
                        $this->splitted_fab_pricing($in_pline, $this -> price);
                    }
                } elseif ($in_pline == '2') {
                    if ($gauge == 12.7) {
                        $criteria = "extra_twist_12.7";
                        $this -> price = $this -> get_rates($in_fd1, $in_pline, $in_catalog, "1st_twist_12.7");
                        $this->splitted_fab_pricing($in_pline, $this -> price);
                    } elseif ($gauge == 25.4) {
                        $criteria = "extra_twist_25.4";
                        $this -> price = $this -> get_rates($in_fd1, $in_pline, $in_catalog, "1st_twist_25.4");
                        $this->splitted_fab_pricing($in_pline, $this -> price);
                    }
                }

                while ($numtwists > 0) {
                    $this -> price = $this -> price + $this -> get_rates($in_fd1, $in_pline, $in_catalog, $criteria);
                    $this->splitted_fab_pricing($in_pline, $this -> price);
                    $numtwists--;
                }
            }

            //continuous and sculptural have rates that are standard to their respective groups
            if (($in_fd2 == "02" || $in_fd2 == "07") && $in_fd1 != "21") {//continuous //sculptural
                $this -> price = $this -> get_rates($in_fd2, $in_pline, $in_catalog, "standard");
                $this->splitted_fab_pricing($in_pline, $this -> price);
            }

        }

        return $this -> price;
    }


    //**Romain, function to split the cost of Fabrication per product line
    public function splitted_fab_pricing($in_pline, $in_currentitemprice) {
        global $firephp;
        switch ($in_pline) {
            case 1 :
                //$firephp->warn("Varia fab");
                $_SESSION['fabrication']['variaFabricationAmount'] = $_SESSION['fabrication']['variaFabricationAmount']+$in_currentitemprice;
                break;
            case 2 :
                $_SESSION['fabrication']['chromaFabricationAmount'] = $_SESSION['fabrication']['chromaFabricationAmount']+$in_currentitemprice;
                break;

            case 3 :
                $_SESSION['fabrication']['glassFabricationAmount'] = $_SESSION['fabrication']['glassFabricationAmount']+$in_currentitemprice;
                break;

            default :
                break;
        }

    }
    
    public function splitted_panelcutting_pricing($in_pline, $in_currentitemprice) {
        global $firephp;
        switch ($in_pline) {
            case 1 :
                $_SESSION['fabrication']['variaPanelCuttingAmount'] = $_SESSION['fabrication']['variaPanelCuttingAmount']+$in_currentitemprice;
                break;
            case 2 :
                $_SESSION['fabrication']['chromaPanelCuttingAmount'] = $_SESSION['fabrication']['chromaPanelCuttingAmount']+$in_currentitemprice;
                break;

            case 3 :
                $_SESSION['fabrication']['glassPanelCuttingAmount'] = $_SESSION['fabrication']['glassPanelCuttingAmount']+$in_currentitemprice;
                break;

            default :
                break;
        }
    }

    //get fabrication rates
    public function get_fab_completion($in_qpid, $in_fabcategory, $in_fd1, $in_fd2, $in_fd3, $in_fd4, $in_fd5, $in_fd6, $in_fd7, $in_fd8, $in_fpid) {
        /*
         * Documentation
         *
         * Check if the fabrication items have minimum requirements
         *
         */
        $this -> completion = true;

        if ($in_fabcategory == "edgefinishes") {
            //check to make sure at least one field is not default (00)
            if ($in_fd1 == "00" && $in_fd2 == "00" && $in_fd3 == "00" && $in_fd4 == "00") {
                $this -> completion = false;
            }
        } elseif ($in_fabcategory == "edgedetails") {

            //Get Depths for radius/bevel
            $q_fabprod = db_query("SELECT * FROM qt_fab_products WHERE id='$in_fpid' AND active='1';");
            $r_fabprod = mysql_fetch_assoc($q_fabprod);

            if ($in_fd1 == "00" && $in_fd2 == "00" && $in_fd3 == "00" && $in_fd4 == "00" && $in_fd5 == "00" && $in_fd6 == "00" && $in_fd7 == "00" && $in_fd8 == "00") {
                $this -> completion = false;
            }

            //Loop
            for ($x = 1; $x < 9; $x++) {
                if (${"in_fd" . $x} == "06" || ${"in_fd" . $x} == "07") {
                    $fab = "fab_detail_" . $x . "_depth";
                    if ($r_fabprod[$fab] == "") {
                        $this -> completion = false;
                    }
                }
            }
            //			if($in_fd1 == "06" || $in_fd1 == "07"){
            //				if($r_fabprod['fab_detail_1_depth'] == ""){
            //					$this->completion = false;
            //				}
            //			}

        } elseif ($in_fabcategory == "milling") {
            //check to make sure at least one field is not default (00)
            if ($in_fd1 == "00" && $in_fd2 == "00" && $in_fd3 == "00" && $in_fd4 == "00") {// && $in_fd5 == "00" && $in_fd6 == "00" && $in_fd7 == "00" && $in_fd8 == "00"
                $this -> completion = false;
            }
        } elseif ($in_fabcategory == "holes") {

            //preliminary linebend data must be done
            if ($in_fd1 == "" || $in_fd1 == "0") {
                $this -> completion = false;
            } elseif ($in_fd2 == true) {
                //preliminary is set, so no hole data must be filled out
            } else {
                //holes must be complete up to the number of holes as indicated in $in_fd1
                $q_lines = db_query("SELECT * FROM qt_fab_holes WHERE quoteprodfab_id = '$in_fpid' AND hole_num <='$in_fd1';");
                while ($r_lines = mysql_fetch_assoc($q_lines)) {

                    if ($r_lines['fromtop'] == "" || $r_lines['fromleft'] == "" || $r_lines['diameter'] == "") {
                        $this -> completion = false;
                    }
                }
            }

        } elseif ($in_fabcategory == "lines") {

            //preliminary linebend data must be done
            if ($in_fd1 == "" || $in_fd1 < 1 || $in_fd2 == "00") {
                $this -> completion = false;
            } else {
                $q_lines = db_query("SELECT * FROM qt_fab_linebends WHERE quoteprodfab_id = '$in_fpid' AND bend_num <='$in_fd1' ;");
                while ($r_lines = mysql_fetch_assoc($q_lines)) {

                    if ($r_lines['from_zero'] == "" || $r_lines['bend_dir'] == "" || $r_lines['groove'] == "00") {
                        $this -> completion = false;
                    }
                }
            }

        } elseif ($in_fabcategory == "otherfab") {
            if ($in_fd1 == "" && $in_fd2 == "") {
                $this -> completion = false;
            }
        } elseif ($in_fabcategory == "shapes") {

            if ($in_fd1 == "0" || $in_fd2 == "0" || $in_fd1 == "" || $in_fd2 == "") {//both shapes and twists
                $this -> completion = false;
            }

            if ($in_fd2 == "02" && $in_fd1 != "21") {//continuous
                if ($in_fd3 == "00" || $in_fd3 == "") {
                    $this -> completion = false;
                }
            }

            if ($in_fd2 == "07" && $in_fd1 != "21") {//sculptural
                if ($in_fd3 == "00" || $in_fd3 == "" || $in_fd4 == "00" || $in_fd4 == "") {
                    $this -> completion = false;
                }
            }

        } elseif ($in_fabcategory == "highres") {
            //check to make sure at least one field is not default (00)
            if ($in_fd1 == "00" || $in_fd2 == NULL || $in_fd1 == "" || $in_fd2 == "") {
                $this -> completion = false;
            }
        }

        //return boolean
        return $this -> completion;
    }

    //get fabrication rates
    public function get_fab_descr($in_qpid, $in_panelid, $in_qty, $in_length, $in_width, $in_gauge, $in_fabcategory, $in_fd1, $in_fd2, $in_fd3, $in_fd4, $in_fd5, $in_fd6, $in_fd7, $in_fd8, $in_fpid, $in_headeronoff, $in_lang) {
        //get text in appropriate language
        if (empty($in_lang)) {
            $in_lang = 1;
        }
        $q_reptext = db_query("SELECT * FROM tmpl_report WHERE lang_id='$in_lang'");
        $r_reptext = mysql_fetch_assoc($q_reptext);

        $this -> descr = "";
        $tempstr = "";
        $temparr = array();

        //put in ":" instead of "_" for panel id
        $panelname_arr = explode("_", $in_panelid);
        $in_panelid = implode(":", $panelname_arr);
        //$in_panelid = "[$in_panelid]";

        if ($in_fabcategory == "edgefinishes") {
            //If not Default, then add to string
            if ($in_fd1 != "00") {
                $tempstr = $this -> convert_pidkey("fabrication", $in_fd1, $in_lang);
                $temparr[] = "A: $tempstr";
            }
            if ($in_fd2 != "00") {
                $tempstr = $this -> convert_pidkey("fabrication", $in_fd2, $in_lang);
                $temparr[] = "B: $tempstr";
            }
            if ($in_fd3 != "00") {
                $tempstr = $this -> convert_pidkey("fabrication", $in_fd3, $in_lang);
                $temparr[] = "C: $tempstr";
            }
            if ($in_fd4 != "00") {
                $tempstr = $this -> convert_pidkey("fabrication", $in_fd4, $in_lang);
                $temparr[] = "D: $tempstr";
            }

            $tempstr = implode(", ", $temparr);
            $header = "";
            if ($in_headeronoff == 1) {
                //$header = "$in_panelid Edge Finishes => ";
                //**Romain, 20March2013, translate the header of fabrication
                $header = "$in_panelid $r_reptext[rep_edgefinishes] => ";
            }
            $this -> descr = "$header $tempstr";

        } elseif ($in_fabcategory == "edgedetails") {

            //Get Depths for radius/bevel
            $q_fabprod = db_query("SELECT * FROM qt_fab_products WHERE id='$in_fpid' AND active='1';");
            $r_fabprod = mysql_fetch_assoc($q_fabprod);

            //TODO loop
            //			for($x=1;$x<9;$x++){
            //				if(${"in_fd".$x} != "00"){
            //					$tempstr = $this->convert_pidkey("fabrication", ${"in_fd".$x});
            //					$tempstr2 = "";
            //					if(${"in_fd".$x} == "06" || ${"in_fd".$x} == "07"){
            //						$tempstr2 = $r_fabprod["fab_detail_".$x."_depth"]."mm";
            //					}
            //						$temparr[] = "FRONT A: $tempstr $tempstr2";//the "A" needs to be dynamic to find "B" if needed
            //				}
            //			}
            if ($in_fd1 != "00") {
                $tempstr = $this -> convert_pidkey("fabrication", $in_fd1, $in_lang);
                $tempstr2 = "";
                if ($in_fd1 == "06" || $in_fd1 == "07") {
                    $tempstr2 = " " . $r_fabprod['fab_detail_1_depth'] . "mm";
                }
                $temparr[] = "FRONT A: $tempstr$tempstr2";
            }
            if ($in_fd2 != "00") {
                $tempstr = $this -> convert_pidkey("fabrication", $in_fd2, $in_lang);
                $tempstr2 = "";
                if ($in_fd2 == "06" || $in_fd2 == "07") {
                    $tempstr2 = " " . $r_fabprod['fab_detail_2_depth'] . "mm";
                }
                $temparr[] = "FRONT B: $tempstr$tempstr2";
            }
            if ($in_fd3 != "00") {
                $tempstr = $this -> convert_pidkey("fabrication", $in_fd3, $in_lang);
                $tempstr2 = "";
                if ($in_fd3 == "06" || $in_fd3 == "07") {
                    $tempstr2 = " " . $r_fabprod['fab_detail_3_depth'] . "mm";
                }
                $temparr[] = "FRONT C: $tempstr$tempstr2";
            }
            if ($in_fd4 != "00") {
                $tempstr = $this -> convert_pidkey("fabrication", $in_fd4, $in_lang);
                $tempstr2 = "";
                if ($in_fd4 == "06" || $in_fd4 == "07") {
                    $tempstr2 = " " . $r_fabprod['fab_detail_4_depth'] . "mm";
                }
                $temparr[] = "FRONT D: $tempstr$tempstr2";
            }
            if ($in_fd5 != "00") {
                $tempstr = $this -> convert_pidkey("fabrication", $in_fd5, $in_lang);
                $tempstr2 = "";
                if ($in_fd5 == "06" || $in_fd5 == "07") {
                    $tempstr2 = " " . $r_fabprod['fab_detail_5_depth'] . "mm";
                }
                $temparr[] = "BACK A: $tempstr$tempstr2";
            }
            if ($in_fd6 != "00") {
                $tempstr = $this -> convert_pidkey("fabrication", $in_fd6, $in_lang);
                $tempstr2 = "";
                if ($in_fd6 == "06" || $in_fd6 == "07") {
                    $tempstr2 = " " . $r_fabprod['fab_detail_6_depth'] . "mm";
                }
                $temparr[] = "BACK B: $tempstr$tempstr2";
            }
            if ($in_fd7 != "00") {
                $tempstr = $this -> convert_pidkey("fabrication", $in_fd7, $in_lang);
                $tempstr2 = "";
                if ($in_fd7 == "06" || $in_fd7 == "07") {
                    $tempstr2 = " " . $r_fabprod['fab_detail_7_depth'] . "mm";
                }
                $temparr[] = "BACK C: $tempstr$tempstr2";
            }
            if ($in_fd8 != "00") {
                $tempstr = $this -> convert_pidkey("fabrication", $in_fd8, $in_lang);
                $tempstr2 = "";
                if ($in_fd8 == "06" || $in_fd8 == "07") {
                    $tempstr2 = " " . $r_fabprod['fab_detail_8_depth'] . "mm";
                }
                $temparr[] = "BACK D: $tempstr$tempstr2";
            }

            $tempstr = implode(", ", $temparr);
            $header = "";
            if ($in_headeronoff == 1) {
                //$header = "$in_panelid Edge Details => ";
                //**Romain, 20March2013, translate the header of fabrication
                $header = "$in_panelid $r_reptext[rep_edgedetails] => ";
            }
            $this -> descr = "$header $tempstr";

        } elseif ($in_fabcategory == "milling") {

            //If not Default, then add to string
            if ($in_fd1 != "00") {
                $tempstr = $this -> convert_pidkey("fabrication", $in_fd1, $in_lang);
                $temparr[] = "A: $tempstr";
            }
            if ($in_fd2 != "00") {
                $tempstr = $this -> convert_pidkey("fabrication", $in_fd2, $in_lang);
                $temparr[] = "B: $tempstr";
            }
            if ($in_fd3 != "00") {
                $tempstr = $this -> convert_pidkey("fabrication", $in_fd3, $in_lang);
                $temparr[] = "C: $tempstr";
            }
            if ($in_fd4 != "00") {
                $tempstr = $this -> convert_pidkey("fabrication", $in_fd4, $in_lang);
                $temparr[] = "D: $tempstr";
            }

            $tempstr = implode(", ", $temparr);
            $header = "";
            if ($in_headeronoff == 1) {
                //$header = "$in_panelid Milling => ";
                //**Romain, 20March2013, translate the header of fabrication
                $header = "$in_panelid $r_reptext[rep_milling] => ";
            }
            $this -> descr = "$header $tempstr";

        } elseif ($in_fabcategory == "holes") {

            //			//preliminary linebend data must be done
            //			if($in_fd1 == ""){
            //				$this->completion = false;
            //			}elseif($in_fd2 == true){
            //				//preliminary is set, so no hole data must be filled out
            //			}else{
            //				//holes must be complete up to the number of holes as indicated in $in_fd1
            //				$q_lines = db_query("SELECT * FROM qt_fab_holes WHERE quoteprodfab_id = '$in_qpid' AND hole_num <='$in_fd1';");
            //				while($r_lines = mysql_fetch_assoc($q_lines)){
            //
            //					if($r_lines['fromtop'] == "" || $r_lines['fromleft'] == "" || $r_lines['diameter'] == ""){
            //						$this->completion = false;
            //					}
            //				}
            //			}

            //Just get number of holes
            $header = "";
            if ($in_headeronoff == 1) {
                //$header = "$in_panelid Holes => ";
                //**Romain, 20March2013, translate the header of fabrication
                $header = "$in_panelid $r_reptext[rep_hole] => ";
            }
            $this -> descr = "$header $in_fd1";

        } elseif ($in_fabcategory == "lines") {

            //get line bend
            $q_lines = db_query("SELECT * FROM qt_fab_linebends WHERE quoteprodfab_id='$in_fpid' AND bend_num <='$in_fd1';");
            while ($r_lines = mysql_fetch_assoc($q_lines)) {
                $tempstr1 = $this -> convert_pidkey("fabrication", $r_lines['bend_dir'], $in_lang);
                $tempstr2 = $this -> convert_pidkey("fabrication", $r_lines['groove'], $in_lang);
                $temparr[] = ucfirst($r_reptext['rep_bend']) . " $r_lines[bend_num]: $r_lines[from_zero]mm $tempstr1 $tempstr2";
            }

            $tempstr = implode(", ", $temparr);
            $header = "";
            if ($in_headeronoff == 1) {
                //$header = "$in_panelid Line Bends => ";
                //**Romain, 20March2013, translate the header of fabrication
                $header = "$in_panelid $r_reptext[rep_linebends] => ";
            }
            $this -> descr = "$header $tempstr ";

        } elseif ($in_fabcategory == "shapes") {

            if ($in_fd1 == "21") {//twists
                $tempstr = "$in_fd2";
            }

            if ($in_fd2 == "02" && $in_fd1 != "21") {//continuous
                $tempstr = $this -> convert_pidkey("shapes", $in_fd3, $in_lang);
            }

            if ($in_fd2 == "07") {//sculptural
                $tempstr = $this -> convert_pidkey("shapes", $in_fd4, $in_lang);
            }

            $shapetwist = $this -> convert_pidkey("shapes", $in_fd1, $in_lang);

            $header = "";
            if ($in_headeronoff == 1) {
                $header = "$in_panelid $shapetwist => ";
            }
            $this -> descr = "$header $tempstr $r_reptext[rep_shapeincludespolish]";

        }

        //		elseif($in_fabcategory=="otherfab"){
        //			if($in_fd1 == "" && $in_fd2 == ""){
        //				$this->completion = false;
        //			}
        //		}

        //return descr string
        //$this->descr = "TEST! Jarom is the best: $in_fabcategory: $in_fd1";
        return $this -> descr;
    }

    //get fabrication machine and man minutes
    public function get_fab_minutes($in_qpid, $in_pline, $in_length, $in_width, $in_gauge, $in_fabcategory, $in_fd1, $in_fd2, $in_fd3, $in_fd4, $in_fd5, $in_fd6, $in_fd7, $in_fd8, $in_fpid, $in_qty) {//XXX Pass in qty?
        global $firephp;
        //not used? $in_qpid //TODO delete?

        $complf_manmin = $cnc_manmin = $lite_man = 0;
        //man hours
        $cnc_machine = $milling_machine = $vact_machine = $oven_machine = $linebends_machine = 0;
        //machine hours //$saw_machine = 0;
        $manhours = $handtools = $total_machine = 0;
        //summary line item totals
        $handtools_sep = "";
        //for testing

        $gauge = str_replace("mm", "", $in_gauge);
        //get number

        if ($in_fabcategory == "edgefinishes") {//per meter pricing for sanding/polishing/water sealing

            //get criteria: polishing nad water sealing have the same gauge critera
            $criteria = "";
            if ($in_pline == "1") {//Varia
                $criteria = ($gauge < 12.7 ? "<12.7mm" : ">=12.7mm");
            } elseif ($in_pline == "2") {//Chroma //TODO fix for reflect - does it need to be adjusted up 3.1mm?
                if ($gauge <= 12.7) {
                    $criteria = "<=12.7mm";
                } elseif ($gauge <= 25.4) {
                    $criteria = "12.7mm><=25.4mm";
                } else {
                    $criteria = "25.4><=50.8mm";
                }
            } elseif ($in_pline == "3") {//poured glass
                $criteria = "";
            }

            //cycle through each edgefinish (ef), so put them in array
            $ef = array($in_fd1, $in_fd2, $in_fd3, $in_fd4);
            foreach ($ef as $key => $pidkey) {

                $m = ($key % 2 == 0 ? $in_width : $in_length);
                //A,C = width, B,D = length for meter
                $m = $m / 1000;
                //put mm into m

                $minutes = array();

                if ($pidkey == "03") {//sanding
                    $minutes = $this -> get_minutes($pidkey, $in_pline, "", "lite_man", "fabrication");
                } elseif ($pidkey == "04") {//polishing
                    $minutes = $this -> get_minutes($pidkey, $in_pline, $criteria, "lite_man", "fabrication");
                } elseif ($pidkey == "05") {//water sealing
                    $minutes = $this -> get_minutes($pidkey, $in_pline, $criteria, "lite_man", "fabrication");
                }

                foreach ($minutes as $fabricationkey => $min_value) {
                    ${$fabricationkey} += $min_value * $m * $in_qty;
                    //min per m * number of m * sheet qty
                    //echo "<br /> $fabricationkey :".${$fabricationkey};
                }

                //				$manhours += $minutes;
            }

            $handtools += $lite_man + $complf_manmin;
            $handtools_sep = $handtools_sep . " <br />EdgeFinishes => Lite_man: $lite_man + compplf_manmin: $complf_manmin";

        } elseif ($in_fabcategory == "edgedetails") {

            //cycle through each item, so put them in array
            $ed = array($in_fd1, $in_fd2, $in_fd3, $in_fd4, $in_fd5, $in_fd6, $in_fd7, $in_fd8);
            foreach ($ed as $key => $pidkey) {

                $m = ($key % 2 == 0 ? $in_width : $in_length);
                //A,C = width, B,D = length for meter
                $m = $m / 1000;
                //put mm into m

                $minutes = array();

                if ($pidkey == "06") {//radius
                    $minutes = $this -> get_minutes($pidkey, $in_pline, "", "complf_manmin", "fabrication");
                } elseif ($pidkey == "07") {//bevel/chamfer
                    $minutes = $this -> get_minutes($pidkey, $in_pline, "", "complf_manmin", "fabrication");
                } elseif ($pidkey == "08") {//eased
                    $minutes = $this -> get_minutes($pidkey, $in_pline, "", "lite_man", "fabrication");
                } elseif ($pidkey == "09") {//edge sealing
                    $minutes = $this -> get_minutes($pidkey, $in_pline, "", "edgesealing_todo", "fabrication");
                    //
                }

                foreach ($minutes as $fabricationkey => $min_value) {
                    ${$fabricationkey} += $min_value * $m * $in_qty;
                    //min per m * number of m * sheet qty
                }

            }

            $handtools += $lite_man + $complf_manmin;
            $handtools_sep = $handtools_sep . " <br />Edgedetails => Lite_man: $lite_man + compplf_manmin: $complf_manmin";

        } elseif ($in_fabcategory == "milling") {

            //cycle through each item, so put them in array
            $milling = array($in_fd1, $in_fd2, $in_fd3, $in_fd4);
            foreach ($milling as $key => $pidkey) {

                $m = ($key % 2 == 0 ? $in_width : $in_length);
                //A,C = width, B,D = length for meter
                $m = $m / 1000;
                //put mm into m

                $minutes = $minutes1 = $minutes2 = array();

                if ($pidkey == "10") {//Miter Joint
                    $minutes1 = $this -> get_minutes($pidkey, $in_pline, "", "complf_manmin", "fabrication");
                    $minutes2 = $this -> get_minutes($pidkey, $in_pline, "", "milling_machine", "fabrication");
                } elseif ($pidkey == "11") {//Wavy Bit
                    $minutes1 = $this -> get_minutes($pidkey, $in_pline, "", "complf_manmin", "fabrication");
                    $minutes2 = $this -> get_minutes($pidkey, $in_pline, "", "milling_machine", "fabrication");
                } elseif ($pidkey == "12") {//Halp Lap Quirk
                    $minutes1 = $this -> get_minutes($pidkey, $in_pline, "", "complf_manmin", "fabrication");
                    $minutes2 = $this -> get_minutes($pidkey, $in_pline, "", "milling_machine", "fabrication");
                }

                $minutes = array_merge($minutes1, $minutes2);

                foreach ($minutes as $fabricationkey => $min_value) {
                    ${$fabricationkey} += $min_value * $m * $in_qty;
                    //min per m * number of m * sheet qty
                }
            }

            //$handtools += $complf_manmin;

        } elseif ($in_fabcategory == "holes") {
            $minutes = $this -> get_minutes("", $in_pline, "holes", "lite_man", "fabrication");
            //$firephp->warn($minutes, "Holes minutes");
            //**Recalculate array key with quantity
            foreach ($minutes as $fabricationkey => $min_value) {
                ${$fabricationkey} += $min_value * $in_fd1 * $in_qty;
                //min per m * bend count * sheet qty
                //echo ${$fabricationkey}." +=  $min_value * $in_fd1 * $in_qty; <Br />";
            }
            //**Add same handtools time as the lite_man time
            $handtools += ${$fabricationkey};
            $handtools_sep = $handtools_sep . " <br />Holes => Lite man: $lite_man";

        } elseif ($in_fabcategory == "lines") {

            $criteria = "";
            $minutes = $minutes0 = $minutes1 = $minutes2 = $minutes3 = $minutes4 = $minutes5 = $minutes10= $minutes11 = array();

            //**1. get standard bend count minutes
            $minutes0 = $this -> get_minutes("", $in_pline, "bends", "complf_manmin", "fabrication");
            $minutes1 = $this -> get_minutes("", $in_pline, "bends", "linebends_machine", "fabrication");
            $minutes = array_merge($minutes0, $minutes1);
            //**Recalculate array key with quantity
            foreach ($minutes as $fabricationkey => $min_value) {
                ${$fabricationkey} += $min_value * $in_fd1 * $in_qty;
                //${$fabricationkey} += $min_value * $in_qty;
                //min per m * bend count * sheet qty
                //echo ${$fabricationkey}." +=  $min_value * $in_fd1 * $in_qty; <Br />";
                //$firephp->warn($min_value,"min_value");
                //$firephp->warn($in_fd1,"fd1");
                //$firephp->warn($in_qty,"qty");
                //$firephp->warn(${$fabricationkey}, "Key value $fabricationkey");
                //$firephp->warn($minutes, "BEND: Standard bend time");
            }
            
            //$firephp->warn($minutes, "BEND: Standard bend time");
            //$firephp->warn(${$fabricationkey}, "LEADTIME: bend time");
            //$minutes = array_merge($minutes0, $minutes1);

            //**Leadtime 2013
            //**Get setup charge for 1 or 2 line bend
            //$firephp->warn($in_qty, "LEADTIME: Qty");
            if ($in_fd1==1){
                $minutes10=$this -> get_minutes("", $in_pline, "bend_setup", "complf_manmin", "fabrication");
                $minutes11=$this -> get_minutes("", $in_pline, "bend_setup", "linebends_machine", "fabrication");
            }else if ($in_fd1==2){
                $minutes10=$this -> get_minutes("", $in_pline, "bend_setup_2bends", "complf_manmin", "fabrication");
                $minutes11=$this -> get_minutes("", $in_pline, "bend_setup_2bends", "linebends_machine", "fabrication");
            }
           //$firephp->warn($minutes10, "LEADTIME: MINUTES10");
 
            //$minutes = array_merge($minutes10, $minutes11);
            //$firephp->warn($minutes, "LEADTIME: Line bend setup");
            foreach ($minutes as $fabricationkey => $min_value) {
                if ($fabricationkey=="complf_manmin"){
                    ${$fabricationkey} += $minutes10['complf_manmin'];
                }else if ($fabricationkey=="linebends_machine"){
                    ${$fabricationkey} += $minutes11['linebends_machine'];
                }
            }
            $firephp->warn($minutes, "BEND: Added setup");
            //$minutes = array();

            //linebend pricing must use data from the qt_fab_linebends table
            $q_lines = db_query("SELECT * FROM qt_fab_linebends WHERE quoteprodfab_id = '$in_fpid' AND bend_num <='$in_fd1' ORDER BY bend_num ASC;");
            while ($r_lines = mysql_fetch_assoc($q_lines)) {

                //some machine time only added if v groove, and this is in addition to number of bends
                if ($r_lines['groove'] == "13") {//V Groove also requiers gauge

                    if ($gauge <= 9.5) {
                        $criteria = "line_bend_vgroove_<=9.5mm";
                    } elseif ($gauge <= 19) {
                        $criteria = "line_bend_vgroove_9.5<x<=19mm";
                    } else {
                        $criteria = "line_bend_vgroove_19<x<=25.4mm";
                    }

                    $minutes2 = $this -> get_minutes($pidkey, $in_pline, $criteria, "cnc_manmin", "fabrication");
                    $minutes3 = $this -> get_minutes($pidkey, $in_pline, $criteria, "cnc_machine", "fabrication");
                    $minutes4 = $this -> get_minutes($pidkey, $in_pline, "vgroove", "complf_manmin", "fabrication");
                    $minutes5=$this -> get_minutes("", $in_pline, "bend_vgroove", "linebends_machine", "fabrication");
                
                    $minutes = array_merge($minutes2, $minutes3, $minutes4, $minutes5);

                    foreach ($minutes as $fabricationkey => $min_value) {
                        ${$fabricationkey} += $min_value * $in_qty;
                        //min per m * sheet qty
                        //echo ${$fabricationkey}." +=  $min_value * $in_fd1 * $in_qty; $fabricationkey<Br />";
                    }
                    
                    
                    
                    /*foreach ($minutes4 as $fabricationkey => $min_value) {
                        $minutes['linebends_machine'] += $min_value * $in_qty;
                        //min per m * sheet qty
                        //echo ${$fabricationkey}." +=  $min_value * $in_fd1 * $in_qty; $fabricationkey<Br />";
                    }*/
                    
                    
                    
                }

                //add setup time for each bend
                //$minutes4 = $this -> get_minutes("", $in_pline, "bend_setup", "complf_manmin", "fabrication");
                //$minutes5 = $this -> get_minutes("", $in_pline, "bend_setup", "linebends_machine", "fabrication");

                

            }//end while
            $firephp->warn($minutes, "BEND: Added groove");
            //$handtools += $minutes1['linebends_machine'] * $in_qty;
            //$linebends_machine;
            //$handtools_sep = $handtools_sep . " <br />Lines => linebend_machine: $linebends_machine (should be $minutes1[linebends_machine] * $in_qty)";

        } elseif ($in_fabcategory == "otherfab") {

        } elseif ($in_fabcategory == "shapes") {

            if ($in_fd1 == "21") {//twists

            }

            //continuous and sculptural have differing rates for fabrication time (and sculptural has more items)
            if ($in_fd2 == "02") {//continuous

                $minutes = $minutes0 = $minutes1 = $minutes2 = $minutes3 = array();

                //
                $minutes0 = $this -> get_minutes($in_fd2, $in_pline, "", "cnc_manmin", "shapes");
                $minutes1 = $this -> get_minutes($in_fd2, $in_pline, "", "complf_manmin", "shapes");
                $minutes2 = $this -> get_minutes($in_fd2, $in_pline, "", "cnc_machine", "shapes");
                $minutes3 = $this -> get_minutes($in_fd2, $in_pline, "", "oven_machine", "shapes");

                $minutes = array_merge($minutes0, $minutes1, $minutes2, $minutes3);

                foreach ($minutes as $fabricationkey => $min_value) {
                    ${$fabricationkey} += $min_value * $in_qty;
                    //min per m * sheet qty
                    //echo ${$fabricationkey}.": $fabricationkey +=  $min_value * $in_qty (from fd2) $in_fd2<br />";//min per m * sheet qty
                }

            } elseif ($in_fd2 == "07") {//sculptural

                $minutes = $minutes0 = $minutes1 = $minutes2 = $minutes3 = $minutes4 = array();

                //
                $minutes0 = $this -> get_minutes($in_fd2, $in_pline, "", "cnc_manmin", "shapes");
                $minutes1 = $this -> get_minutes($in_fd2, $in_pline, "", "complf_manmin", "shapes");
                $minutes2 = $this -> get_minutes($in_fd2, $in_pline, "", "cnc_machine", "shapes");
                $minutes3 = $this -> get_minutes($in_fd2, $in_pline, "", "oven_machine", "shapes");
                $minutes4 = $this -> get_minutes($in_fd2, $in_pline, "", "vact_machine", "shapes");

                $minutes = array_merge($minutes0, $minutes1, $minutes2, $minutes3, $minutes4);

                foreach ($minutes as $fabricationkey => $min_value) {
                    ${$fabricationkey} += $min_value * $in_qty;
                    //min per m * sheet qty
                    //if($fabricationkey == "complf_manmin"){
                    //echo ${$fabricationkey}.": $fabricationkey +=  $min_value * $in_qty (from fd2) $in_fd2<br />";//min per m * sheet qty
                    //}
                }

            }

        }

        //Sum up all totals, put in terms of hours
        $manhours = $cnc_manmin + $complf_manmin + $lite_man;
        $total_machine = $milling_machine + $cnc_machine + $linebends_machine + $vact_machine + $oven_machine + $handtools;
        //TODO
        //$handtools = $lite_man + $complf_manmin;

        //Add 1 time setup

        //put all summed up totals in array
        $minutes = array("cnch" => "$cnc_machine", "ovenh" => "$oven_machine", "vacth" => "$vact_machine", "linebendh" => "$linebends_machine", "sawh" => "", "freesh" => "$milling_machine", "handtools" => "$handtools", "handtools_sep" => "$handtools_sep", "cncf_manmin" => "$cnc_manmin", "complf_manmin" => "$complf_manmin", "litef_manmin" => "$lite_man", "total_manmin" => "$manhours", "total_machine" => "$total_machine");

        //return array with summarized results
        //$firephp->warn ($minutes, "Minutes returned");
        return $minutes;
    }

    //	//get panel saw lead times
    //	public function get_panelsaw_leadtime ($in_pline, $in_qty){//$in_qpid,
    //
    //		$minutes = $minutes1 = $minutes2 = $minutes_return = array();
    //
    //		$minutes1 = $this->get_minutes ("", $in_pline, "panelsaw", "saw_machine", "fabrication");
    //		$minutes2 = $this->get_minutes ("", $in_pline, "panelsaw", "complf_manmin", "fabrication");
    //
    //		$minutes = array_merge($minutes1, $minutes2);
    //
    //		foreach($minutes as $fabricationkey => $min_value){
    //			${$fabricationkey} +=  $min_value * $in_qty;//min per sheet * sheet qty
    //			//echo "<br /> $fabricationkey :".${$fabricationkey};
    //		}
    //
    //		//add to totals
    //		//$manhours = $cnc_manmin + $complf_manmin + $lite_man;
    //		//$total_machine = $milling_machine + $cnc_machine + $linebends_machine + $vact_machine + $oven_machine;//
    //
    //		$minutes_return = array("sawh" => "$saw_machine", "complf_manmin" => "$complf_manmin", "total_manmin" => "$complf_manmin", "total_machine" => "$saw_machine");
    //
    //		return $minutes_return;
    //	}

    public function get_misc_leadtime($in_pline, $in_setup, $in_qty) {
        global $firephp;
        $minutes_return = array();

        $complf_manmin = $cnc_manmin = $lite_man = 0;
        //man hours
        $cnc_machine = $milling_machine = $vact_machine = $oven_machine = $linebends_machine = $saw_machine = 0;
        //machine hours
        $manhours = $handtools = $total_machine = 0;
        //summary line item totals

        if ($in_setup == "panelsaw" || $in_setup == "panelsaw_chroma12" || $in_setup == "panelsaw_chroma25" || $in_setup == "panelsaw_chroma50") {

            $minutes = $minutes1 = $minutes2 = array();

            $minutes1 = $this -> get_minutes("", $in_pline, "$in_setup", "saw_machine", "fabrication");
            $minutes2 = $this -> get_minutes("", $in_pline, "$in_setup", "complf_manmin", "fabrication");

            $minutes = array_merge($minutes1, $minutes2);

            foreach ($minutes as $fabricationkey => $min_value) {
                ${$fabricationkey} += $min_value * $in_qty;
                //min per sheet * sheet qty
                //				echo ${$fabricationkey}.": $fabricationkey +=  $min_value * $in_qty<br />";//
            }

        } elseif ($in_setup == "panelsaw_setup") {

            $minutes = $minutes1 = $minutes2 = array();

            $minutes1 = $this -> get_minutes("", $in_pline, "$in_setup", "saw_machine", "fabrication");
            $minutes2 = $this -> get_minutes("", $in_pline, "$in_setup", "complf_manmin", "fabrication");

            $minutes = array_merge($minutes1, $minutes2);

            foreach ($minutes as $fabricationkey => $min_value) {
                ${$fabricationkey} += $min_value;
                //min per sheet on setup
                //echo ${$fabricationkey}.": $fabricationkey +=  $min_value ($in_pline, $in_setup, $in_qty)<br />";//
            }

        } elseif ($in_setup == "milling_setup") {

            $minutes = $minutes1 = $minutes2 = array();

            $minutes1 = $this -> get_minutes("", $in_pline, "$in_setup", "milling_machine", "fabrication");
            $minutes2 = $this -> get_minutes("", $in_pline, "$in_setup", "complf_manmin", "fabrication");

            $minutes = array_merge($minutes1, $minutes2);

            foreach ($minutes as $fabricationkey => $min_value) {
                ${$fabricationkey} += $min_value;
                //min per sheet on setup
                //echo ${$fabricationkey}.": $fabricationkey +=  $min_value ($in_pline, $in_setup, $in_qty)<br />";//
            }

        } elseif ($in_setup == "shapes_setupcharge") {

            $minutes = array();

            $minutes = $this -> get_minutes("", $in_pline, "$in_setup", "cnc_manmin", "shapes");

            foreach ($minutes as $fabricationkey => $min_value) {
                ${$fabricationkey} += $min_value;
                //min per sheet on setup
                //				echo ${$fabricationkey}.": $fabricationkey +=  $min_value ($in_pline, $in_setup, $in_qty)<br />";//
            }

        }elseif ($in_setup == "hole_setup") {

            $minutes = $minutes1 = $minutes2 = array();

            $minutes1 = $this -> get_minutes("", $in_pline, "$in_setup", "handtools", "fabrication");
            $minutes2 = $this -> get_minutes("", $in_pline, "$in_setup", "lite_man", "fabrication");

            $minutes = array_merge($minutes1, $minutes2);

            foreach ($minutes as $fabricationkey => $min_value) {
                ${$fabricationkey} += $min_value;
                //min per sheet on setup
                //echo ${$fabricationkey}.": $fabricationkey +=  $min_value ($in_pline, $in_setup, $in_qty)<br />";//
            }
            //$firephp->warn($minutes, "Hole setup time");

        }elseif ($in_setup == "line_setup") {

            $minutes = $minutes1 = $minutes2 = array();

            $minutes1 = $this -> get_minutes("", $in_pline, "$in_setup", "handtools", "fabrication");
            $minutes2 = $this -> get_minutes("", $in_pline, "$in_setup", "lite_man", "fabrication");

            $minutes = array_merge($minutes1, $minutes2);

            foreach ($minutes as $fabricationkey => $min_value) {
                ${$fabricationkey} += $min_value;
                //min per sheet on setup
                //echo ${$fabricationkey}.": $fabricationkey +=  $min_value ($in_pline, $in_setup, $in_qty)<br />";//
            }
            //$firephp->warn($minutes, "Hole setup time");

        }

        //add to totals
        $manhours = $cnc_manmin + $complf_manmin + $lite_man;
        $total_machine = $milling_machine + $cnc_machine + $linebends_machine + $vact_machine + $oven_machine + $saw_machine + $handtools;
        //

        $minutes_return = array("cnch" => "$cnc_machine", "ovenh" => "$oven_machine", "vacth" => "$vact_machine", "linebendh" => "$linebends_machine", "sawh" => "$saw_machine", "freesh" => "$milling_machine", "handtools" => "$handtools", "cncf_manmin" => "$cnc_manmin", "complf_manmin" => "$complf_manmin", "litef_manmin" => "$lite_man", "total_manmin" => "$manhours", "total_machine" => "$total_machine");

        return $minutes_return;
    }

    private function convert_pidkey($in_column, $in_id, $in_lang) {

        //get pidcode to get qt_raw_materials
        $pid_name = convertPIDField($in_column, $in_id, "", $in_lang);
        return $pid_name;
    }

    /*
     * Look up min_per_unit from qt_fab_minutes
     *
     * $in_pidkey (null, or from pid_key table)
     * $in_pline (Pline, 1, 2 or 3)
     * $in_criteria (Null, or specific rule for a given fabrication/pid_key combination)
     * $in_fabrication (man minuets or machine minutes for various fabrication items)
     * $in_type (fabrication or shapes)
     */
    private function get_minutes($in_pidkey, $in_pline, $in_criteria, $in_fabrication, $in_type) {

        $minute_arr = array();

        $criteria_query = ($in_criteria == "" ? "" : "AND criteria='$in_criteria'");
        $pidkey_query = ($in_pidkey == "" ? "" : " AND pid_key='$in_pidkey'");

        $q_fabmin = db_query("SELECT fabrication, min_per_unit FROM qt_fab_minutes WHERE 
			pline='$in_pline' $pidkey_query AND active='1' AND fabrication='$in_fabrication' AND type='$in_type' $criteria_query");
        //while($r_fabmin = mysql_fetch_assoc($q_fabmin)){
        $r_fabmin = mysql_fetch_assoc($q_fabmin);
        $minute_arr[$r_fabmin['fabrication']] = $r_fabmin['min_per_unit'];
        //}

        //		echo "SELECT fabrication, min_per_unit FROM qt_fab_minutes WHERE pid_key='$in_pidkey' AND pline='$in_pline' AND active='1' $criteria_query; = ".
        //			$minute_arr[$r_fabmin['fabrication']]."<br />";

        return $minute_arr;
    }

    //	private function convert_to_pidkey($in_column, $in_prodid){
    //		//get name
    //		$q_pidkey= db_query("SELECT name FROM products WHERE id='$in_prodid'");
    //		$r_pidkey = mysql_fetch_assoc($q_pidkey);
    //
    //		//get pidcode to get qt_raw_materials
    //		$pid_code = convertPIDField($in_column,"",$r_pidkey['name']);
    //
    //		return $pid_code;
    //	}

}
?>