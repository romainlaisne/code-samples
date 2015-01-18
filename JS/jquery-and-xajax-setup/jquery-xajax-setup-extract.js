$(function() {

	loadingIcon = "<img style='text-align:right;width:25px' src='http://www.3form.eu/designguide/images/timer.png' width='32px'>";
	//**Highlight current section

	//**Get translations

	//**Check the solution in use
	xajax_checkCurrentSeries();

	/**************************************
	 * Current tab
	 **************************************/
	currentTab = "";
	var $tabs = $("#tabs").tabs({
		select : function(event, ui) {
			$("#currenttab").val(ui.tab.text);
			currentTab = ui.tab.text;
			xajax_renderSpecificationsTab();

		}
	});

	var selected = $tabs.tabs('option', 'selected');

	/**************************************
	 * Addtospecs for IMAGES
	 **************************************/
	//**Rollover for specification folder

	$(".addtospecs").each(function(index) {
		//**Reset filepath to avoid incorrect affection to next elements
		filePath = "";
		theCurrentMedia = "";
		//**Get tag name of children of this div
		try {
			theTagName = $(this).children()[0].tagName;
		} catch (err) {
			alert("One of the media element (<p>) with class addtospecs miss a IMG or A tag inside");
		}
		theCurrentMedia = $(this).children();
		//**Handle media type IMG or LINK
		if (theTagName == "IMG") {
			try {
				filePath = theCurrentMedia.attr('src');
			} catch (err) {
				alert("One of the <img> element with class addtospecs miss a SRC definition");
			}
		} else if (theTagName == "A") {
			try {
				filePath = theCurrentMedia.attr('a');
			} catch (err) {
				alert("One of the <a> element with class addtospecs miss a HREF definition");
			}

		}
		var pos = $(this).position();
		var xpos = pos.left + $(this).width();
		var ypos = pos.top;
		$(".addtospecsbtn").clone().css({
			position : "absolute",
			top : ypos + "px",
			left : xpos - $(".addtospecsbtn").width() - 25 + "px"
		}).show().prependTo($(this)).attr("filePath", filePath);
	});
	/**************************************
	 * Addtospecs for DOWNLOADS
	 **************************************/
	//**Rollover for specification folder
	i = 0;
	$(".addtospecsDownloads").each(function(index) {

		//**Reset filepath to avoid incorrect affection to next elements
		filePath2 = "";
		theCurrentMedia2 = "";
		//**Get tag name of children of this div
		try {
			theTagName2 = $(this).children()[0].tagName;
		} catch (err) {
			alert("One of the media element (<p>) with class addtospecs miss a IMG or A tag inside");
		}
		theCurrentMedia2 = $(this).children('a');

		//**Handle media type IMG or LINK
		if (theTagName2 == "IMG") {
			try {
				filePath2 = theCurrentMedia2.attr('src');
			} catch (err) {
				alert("One of the <img> element with class addtospecs miss a SRC definition");
			}
		} else if (theTagName2 == "A") {
			try {
				filePath2 = theCurrentMedia2.attr('href');
			} catch (err) {
				alert("One of the <a> element with class addtospecs miss a HREF definition");
			}

		}
		var pos = $(this).position();
		var xpos = pos.left + $(this).width();
		var ypos = pos.top;
		cloned = "";
		cloned = $(".addtospecsbtnDownloads").clone();
		cloned.removeClass('addtospecsbtnDownloads').addClass('addtospecsbtnDownloads2').show().appendTo($(this).parent().parent().children(".atsbutton")).attr("filePath", filePath2);

	});

	/**************************************
	 * Add all Downloads btn
	 **************************************/

	$(".addAllDownloadsBtn").live('click', function() {
		//**Loop
		var obj = $("#tabs-6").find(':checkbox');

		var childCount = obj.size();
		var checkedNb = obj.filter(':checked').length;
		//alert (checkedNb);
		if (checkedNb == 0) {
			//alert("Please select an item.");
			$(this).warnUser("Please select an item", "Ok", "close", "", null, null);
		} else {
			var checkedCount = obj.filter(':checked');
			var elts = new Array();
			checkedCount.each(function(index) {
				//**buid array to send to specs
				filepath = $(this).parent().siblings().find('a').attr('href');
				filesize = $(this).attr("filesize");
				elts[index] = filepath;
			});
			currentTab = $("#currenttab").val();
			xajax_addToSpecifications(null, null, elts, currentTab, null, filesize);
		}

	});

	/**************************************
	 * Addtospecs for COMPONENTS
	 **************************************/
	//**Rollover for specification folder
	$.fn.addSpecButtonsComponents = function() {
		//alert ("addSpecButtonsComponents");
		$(".addtospecsComponents").each(function(index) {
			//**Reset filepath to avoid incorrect affection to next elements
			filePath = "";
			theCurrentMedia = "";
			//**Get tag name of children of this div
			try {
				theTagName = $(this).find('img')[0].tagName;
			} catch (err) {
				alert("One of the media element (<p>) with class addtospecs miss a IMG or A tag inside");
			}
			theCurrentMedia = $(this).find('img');
			//**Handle media type IMG or LINK
			if (theTagName == "IMG") {
				try {
					filePath = theCurrentMedia.attr('src');
					//alert (filePath);
				} catch (err) {
					alert("One of the <img> element with class addtospecs miss a SRC definition");
				}
			} else if (theTagName == "A") {
				try {
					filePath = theCurrentMedia.attr('a');
				} catch (err) {
					alert("One of the <a> element with class addtospecs miss a HREF definition");
				}

			}
			var pos = $(this).position();
			var xpos = pos.left + $(this).width();
			var ypos = pos.top;
			$(".addtospecsbtnDownloads").clone().show().removeClass('addtospecsbtnDownloads').addClass('addtospecsbtnComponents').prependTo($(this).children(".atsComponents")).attr("filePath", filePath);
		});
	};

	/**************************************
	 * Add all COMPONENTS btn
	 **************************************/

	$(".addAllComponentsBtn").live('click', function() {
		//**Loop
		currentTab = $("#currenttab").val();
		xajax_addComponentsForSolution(null, currentTab);

	});

	/**************************************
	 * Addtospecs for SLIDESHOW
	 **************************************/

	$.fn.updateSlideShowATSbutton = function(in_src, in_dbid) {
		//**Reset filepath to avoid incorrect affection to next elements
		filePath = "";
		fullPath = "";
		dbID = "";

		theCurrentMedia = "";
		//**Get tag name of children of this div

		try {
			filePath = in_src;

		} catch (err) {
			alert("One of the <img> element with class addtospecs miss a SRC definition");
		}

		//**Add proper attributes to the unique addtospec button
		$('.addtospecsbtnSlideshow').attr("filePath", filePath).attr("dbid", in_dbid);

	};
	$(".addtospecsbtnSlideshow").mouseover(function() {
		$(".addtospecsbtnSlideshow").css({
			'color' : '#F5A01A',
			'cursor' : 'pointer'
		});

	});

	$(".addtospecsbtnSlideshow").mouseout(function() {
		$(".addtospecsbtnSlideshow").css({
			'color' : '#F5A01A'
		});
	});
	$(".addtospecsbtnSlideshow").ready(function() {
		$(".addtospecsbtnSlideshow").css({
			'text-decoration' : 'none',
			'color' : '#F5A01A'
		});
	});

	$(".goToSpecs").mouseover(function() {
		$(".goToSpecs").css({
			'color' : '#F5A01A',
			'cursor' : 'pointer'
		});

	});



	//**Test AJAX function
	$('.addtospecsbtn').click(function() {
		currentTab = $("#currenttab").val();
		//**fileType is IMG or A
		xajax_addToSpecifications($(this).attr('filePath'), $(this).attr('fileType'), null, currentTab, $(this).attr('dbid'));

	});
	$('.addtospecsbtnDownloads2').click(function() {
		currentTab = $("#currenttab").val();
		//**fileType is IMG or A
		xajax_addToSpecifications($(this).attr('filePath'), $(this).attr('fileType'), null, currentTab, $(this).attr('dbid'));

	});

	$('.addtospecsbtnSlideshow').live('click', function() {
		$(this).showTimer($(this), 0, 0, $(".rhino-active").find(".addtospecsbtnSlideshow"));
		currentTab = $("#currenttab").val();
		//**fileType is IMG or A
		xajax_addToSpecifications($(this).attr('filePath'), $(this).attr('fileType'), null, currentTab, $(this).attr('dbid'));
		xajax_filterComponentsTab($(this).attr('dbid'));
	});

	$('.goToSpecs').live('click', function() {
		window.location.href = "../specification_folder/index.php";
	});

	$('.addtospecsbtnComponents').live('click', function() {
		currentTab = $("#currenttab").val();
		//**fileType is IMG or A
		xajax_addToSpecifications($(this).attr('filePath'), $(this).attr('fileType'), null, currentTab, $(this).attr('dbid'));

		//alert ('Added to the specifications');
	});

	$(".deletespec").live('click', function() {
		$(this).showTimer($(this), 0);
		xajax_removeFromSpecifications($(this).attr('deletefile'), $(this).attr('section'), $(this).attr('dbid'));

	});

	$("#exportSpecsAsZip").live('click', function() {
		$(this).showTimer($(this), 0);
		xajax_exportSpecificationsAsZip();
	});
	$("#exportSpecsAsZip").mouseover(function() {
		$("#exportSpecsAsZip").css({
			'cursor' : 'pointer'
		});

	});

	$("#exportSpecsAsWord").live('click', function() {
		xajax_exportSpecificationsAsWord();
	});

	$('#tabs-7').click(function() {
		xajax_renderSpecificationsTab();
	});

	//** Check downloads checkboxes
	$("#selectAllVaria").live('click', function() {
		if ($(".varia").prop("checked") == true) {
			$(".varia").prop("checked", false);
		} else {
			$(".varia").prop("checked", true);
		}

	});
	$("#selectAllChroma").live('click', function() {
		if ($(".chroma").prop("checked") == true) {
			$(".chroma").prop("checked", false);
		} else {
			$(".chroma").prop("checked", true);
		}
	});
	$("#selectAllGlass").live('click', function() {
		if ($(".glass").prop("checked") == true) {
			$(".glass").prop("checked", false);
		} else {
			$(".glass").prop("checked", true);
		}
	});

	$("#checkAllComponents").live('click', function() {
		//alert ("checkAll");
		if ($("#componentsLayout").find(":checkbox").prop("checked") == true) {
			$("#componentsLayout").find(":checkbox").prop("checked", false);
			xajax_switchTerm("checkAllComponents", "check_all");
		} else {
			$("#componentsLayout").find(":checkbox").prop("checked", true);
			xajax_switchTerm("checkAllComponents", "uncheck_all");
		}

	});

	$(".checkAllDownloads").live('click', function() {
		//alert ("checkAll");
		grpDiv = $(this).closest("div");
		//alert (grpDiv);
		if (grpDiv.find(":checkbox").prop("checked") == true) {
			grpDiv.find(":checkbox").prop("checked", false);
			xajax_switchTerm($(this), "check_all");
		} else {
			grpDiv.find(":checkbox").prop("checked", true);
			xajax_switchTerm("checkAllDownloads", "uncheck_all");
		}

	});


	$("#emailSpecs").live('click', function() {
		$("#askaspecialistdiv").html("<img src='http://www.3form.eu/designguide/images/timer.gif' width='32px' />");
		xajax_showAskASpecialistDiv("email");
		$("#askaspecialistdiv").dialog({
			closeOnEscape : true,
			open : function(event, ui) {
				//$(".ui-dialog-titlebar-close").hide();
			},
			modal : true,
			draggable : true,
			resizable : false,
			width : 450
		});

	});

	$("#emailSpecs").live('mouseover', function() {
		$("#emailSpecs").css({
			'cursor' : 'pointer'
		});

	});

	$("#askaspecialistbtn").live('click', function() {

		$("#askaspecialistdiv").html("<img src='http://www.3form.eu/designguide/images/timer.gif' width='32px' />");

		//**popup the div below
		xajax_showAskASpecialistDiv("ask");

		$("#askaspecialistdiv").dialog({
			closeOnEscape : true,
			open : function(event, ui) {
				//$(".ui-dialog-titlebar-close").hide();
			},
			modal : true,
			draggable : true,
			resizable : false,
			width : 450
		});

		//**Switch image title
		$(".ui-widget-header").css({
			'background' : '#fdaf53 url(http://www.3form.eu/designguide/CSS/custom_jquery_ui/images/contact.png) 5px center no-repeat'
		});

	});

	//** Step 1, ask a specialist
	$("#okpostcode").live('click', function() {
		postcode = $("#clientPostcodeAS").val();
		$("#askaspecialistdiv").html(loadingIcon);
		xajax_showEmailFormAskASpecialist(postcode);

	});

	//**Below, ok when sending email and postcode asked
	$("#okpostcode_email").live('click', function() {
		postcode = $("#clientPostcodeAS").val();
		xajax_checkPostcodeBeforeEmail(postcode);

	});

	//**Below called if result of above checkPostcodeBeforeEmail() is ok
	$.fn.openEmailPopup = function() {
		$("#askaspecialistdiv").dialog("close");
		$("#emailspecsdiv").dialog({
			closeOnEscape : true,
			open : function(event, ui) {
				$(".ui-dialog-titlebar-close").hide();
			},
			modal : true,
			draggable : false,
			resizable : false,
			width : 500,
			buttons : [{
				id : "sendbtn",
				text : "Send",
				click : function() {
					$(this).showTimer($(this), -20, -20, $("#emailbtnholder"));
					email = $("#clientEmail").val();
					postcode = $("#clientPostcode").val();
					project_name = $("#clientProject").val();
					xajax_emailSpecifications(null, null, email, project_name, notes = null, postcode, false);

				}
			}, {
				id : "cancelbtn",
				text : "Cancel",
				click : function() {
					$(this).dialog("close");
				}
			}]
		});

		$(".ui-widget-header").css({
			'background' : '#fdaf53 url(<?php echo $serverHost; ?>/designguide/CSS/custom_jquery_ui/images/email_specifications.png) 5px center no-repeat'
		});

		//position buttons
		$("#emailbtnholder").html("&nbsp;");
		$("#sendbtn").appendTo($("#emailbtnholder"));
		$("#cancelbtn").appendTo($("#emailbtnholder"));
		$("#sendbtn").css({
			'width' : '100px',
			'margin-left' : '-5px',
			'margin-right' : '10px'
		});

	}

	$("#skippostcode_email").live('click', function() {
		$("#askaspecialistdiv").dialog("close");
		$("#emailspecsdiv").dialog({
			closeOnEscape : true,
			open : function(event, ui) {
				$(".ui-dialog-titlebar-close").hide();
			},
			modal : true,
			draggable : false,
			resizable : false,
			width : 500,
			buttons : [{
				id : "sendbtn",
				text : "Send",
				click : function() {
					$(this).showTimer($(this), -20, -20, $("#emailbtnholder"));
					email = $("#clientEmail").val();
					postcode = $("#clientPostcode").val();
					project_name = $("#clientProject").val();
					xajax_emailSpecifications(null, null, email, project_name, notes = null, postcode, false);

				}
			}, {
				id : "cancelbtn",
				text : "Cancel",
				click : function() {
					$(this).dialog("close");
				}
			}]
		});

		$(".ui-widget-header").css({
			'background' : '#fdaf53 url(<?php echo $serverHost; ?>/designguide/CSS/custom_jquery_ui/images/email_specifications.png) 5px center no-repeat'
		});

		//position buttons
		$("#emailbtnholder").html("&nbsp;");
		$("#sendbtn").appendTo($("#emailbtnholder"));
		$("#cancelbtn").appendTo($("#emailbtnholder"));
		$("#sendbtn").css({
			'width' : '100px',
			'margin-left' : '-5px',
			'margin-right' : '10px'
		});

	});

	//**Step 2, ask a specialist
	$("#sendtospecialistbtn").live('click', function() {
		clientName = $("#clientNameAS").val();
		clientPhone = $("#clientPhoneAS").val();
		clientEmail = $("#clientEmailAS").val();
		clientProject = $("#clientProjectAS").val();
		clientNotes = $("#clientNotesAS").val();
		//**postcode retrieve from session
		$(this).showTimer($("#askaspecialistdiv"), 0, 0, $("#askaspecialistdiv"));
		xajax_emailSpecifications(clientName, clientPhone, clientEmail, clientProject, clientNotes, null, true);

	});

	$("#confirmandclosebtn").live('click', function() {
		$('#askaspecialistdiv').dialog('close');
	});

	$("#askaspecialistbtn").live('mouseover', function() {
		$("#askaspecialistbtn").css({
			'cursor' : 'pointer'
		});

	});

	$("#directloginbtn").live('click', function() {
		login = $("#loginfield").val();
		password = $("#passwordfield").val();
		//**below will append directly the result in div
		xajax_directLogin(login, password)
		
	});

	$("#tabs").bind("tabsselect", function(event, ui) {
		//alert (ui.index);
		//**First tab selected
		if (ui.index == 2) {
			xajax_renderComponentsTab();
		} else if (ui.index == 4) {
			xajax_renderPricingTab();
		}

	});

	function Create2DArray(rows) {
		var arr = [];

		for (var i = 0; i < rows; i++) {
			arr[i] = [];
		}

		return arr;
	}


	$.fn.warnUser = function(msg, btnOk, actionOK, btnCancel, actionCancel, in_width, session_link) {
		in_width = 1;
		$("#div_warnUser").html(msg);
		//**Script ok
		if (actionOK == "close") {
			scriptOK = "$(this).dialog('close');";
		} else if (actionOK == "clearspecs") {
			scriptOK = "xajax_clearCurrentSpecifications();$(this).dialog('close');";
		}
		//**script close
		if (actionCancel == "clearspecs") {
			scriptCancel = "xajax_clearCurrentSpecifications();$(this).dialog('close')";
		} else if (actionCancel == "gotoPreviousSpecs") {
			scriptCancel = "$(location).attr('href',session_link)";
		} else {
			scriptCancel = "$(this).dialog('close')";
		}

		var warn_buttons = {};
		if (btnOk != "") {
			warn_buttons[btnOk] = function() {
				eval(scriptOK)
			}
		}

		if (btnCancel != "" && btnCancel != "null") {
			warn_buttons[btnCancel] = function() {
				eval(scriptCancel)
			}
		}

		//**maincontent position
		var position = $("#maincontent").offset();
		var xpos = position.left + ($("#maincontent").width() / 2) - 150;
		var ypos = position.tope + 150;
		
		$("#div_warnUser").dialog({
			closeOnEscape : true,
			open : function(event, ui) {
				$(".ui-dialog").addClass("ui-dialog-shadow");
			},
			modal : true,
			draggable : false,
			resizable : false,
			minWidth : 250,
			minWidth : 250,
			minHeight : 100,
			buttons : warn_buttons,
			position : [xpos, ypos]
		});
	}

	$.fn.requestPostcode = function(whichForm) {

		serializedData = $("#form_material :input[value!='']").serialize();

		$("#getrepinfo").dialog({
			closeOnEscape : true,
			open : function(event, ui) {
				//$(".ui-dialog-titlebar-close").hide();
			},
			modal : true,
			draggable : false,
			resizable : false,
			width : 410,
			buttons : {
				"Ok" : function() {
					postcode = $("#clientPostcodeMaterials").val();
					repDetails = xajax_showRepDetails(null, postcode, whichForm, serializedData);
					$(this).dialog("close");

				},
				"Cancel" : function() {
					$(this).dialog("close");

				}
			}
		});

	}

	$.fn.showTimer = function(currentElt, decalageX, decalageY, appendToDiv) {
		d = document.createElement('div');

		if (appendToDiv == null) {
			var currentEltPos = currentElt.offset();
		} else {
			var currentEltPos = currentElt.position();
		}

		var currentEltWidth = currentElt.width();
		var currentEltHeight = currentElt.height();

		if (appendToDiv == null) {
			appendToDiv = $("#maincontent");
		}

		if (decalageY > 0) {
			decalY = currentEltHeight + decalageY;
		} else {
			decalY = 5;
		}
		if (decalageX > 0) {
			decalX = currentEltWidth + decalageX;
		} else {
			decalX = 5;
		}
		var currentEltXpos = currentEltPos.left + decalX;
		var currentEltYpos = currentEltPos.top + decalY;

		$(d).prependTo(appendToDiv)
		.css({
			position : "absolute",
			top : currentEltYpos,
			left : currentEltXpos
		}).html("<img src='http://www.3form.eu/designguide/images/timer.gif' width='24px' />").click(function() {
			$(this).remove();
		})
		.attr("id", "timer")
	}

	$.fn.hideTimer = function() {
		$("#timer").remove();
	};

	$.fn.swapMoreDetailsImage = function() {
		loop = 0
		$('img[src*="moreDetail"]').each(function(index) {
			tempID = "md_" + loop;
			$(this).attr('id', tempID);
			$(this).attr('src', '');
			xajax_swapMoreDetailsImage(tempID);
			loop++;
		});
	}
	$(this).swapMoreDetailsImage();

	$(".deleteSpecSection").live('click', function() {
		xajax_removeFromSpecifications(null, $(this).attr('section'));
		xajax_renderSpecificationsTab();
	});

	$("#btn_material_reference").live('click', function() {
		//**First check if postcode or repid know
		$(this).showTimer($(this));
		xajax_checkPostcodeOrRepKnown(null, 'sample reference');
	});

	//**Save material form
	$("#btn_material_save").live('click', function() {
		$(this).showTimer($(this));
		serializedData = $("#form_material :input[value!='']").serialize();
		xajax_checkMaterialFormBeforeSave(serializedData);
	});

	//**Coming soon overlay
	//** Put an overlay over each image with coming soon class
	$(window).load(function() {
		$(".coming_soon").each(function(index) {
			var pos = $(this).offset();

			var imgWidth = $(this).width();
			var imgHeight = $(this).height();
			var xpos = pos.left;
			var ypos = pos.top;
			clonedOverlay = $(".coming_soon_overlay").clone().attr("id", "coming_soon_" + index).show().appendTo($(this).parent()).addClass("coming_soon_" + index).removeClass("coming_soon_overlay");

			//**Duplicated div
			currentDivTarget = "$('.coming_soon_" + index + "')";

			//**resize img inside div
			csImgWidth = eval(currentDivTarget).find("img").width();
			//**do not resize coming soon image if the placeholder is a bigger image
			if (imgWidth < csImgWidth) {
				//**resize
				eval(currentDivTarget).find("img").css({
					'width' : imgWidth / 1.5
				});
				newCsImgWidth = eval(currentDivTarget).find("img").width();
			} else {
				newCsImgWidth = csImgWidth;
			}

			//**Resize and position div
			eval(currentDivTarget).css({
				position : "absolute",
				top : ypos + (imgHeight / 2) - (newCsImgWidth / 2) + "px",
				left : xpos + (imgWidth / 2) - (newCsImgWidth / 2) + "px"
			});


		});
	});

	//**Materials search
	$("#interlayer_1,#interlayer_2").live('focus', function(e) {
		$("#search_result").hide();
	});

	$("#interlayer_1,#interlayer_2").live('keyup', function(e) {

		currentFieldId = $(this).attr("id");
		materialSelected = $("#materialSelected").val();
		var search_string = $(this).val();

		// Do Search
		if (search_string !== "") {
			result = xajax_lookupInterlayers(search_string, currentFieldId, materialSelected);
			$("#search_result").html("");
			$("#search_result").show();

			var pos = $(this).offset();
			var xpos = pos.left;
			var ypos = pos.top + 20;
			$("#search_result").css({
				position : "absolute",
				top : ypos + "px",
				left : xpos + "px",
				zIndex : 3000
			});

		} else {
			$("ul#results").fadeOut();
		}
	});

	$("#texture").live('keyup', function(e) {

		currentFieldId = $(this).attr("id");
		materialSelected = $("#materialSelected").val();
		var search_string = $(this).val();

		// Do Search
		if (search_string !== "") {
			result = xajax_lookupTextures(search_string, currentFieldId, materialSelected);
			$("#search_result").html("");
			$("#search_result").show();

			var pos = $(this).offset();
			var xpos = pos.left;
			var ypos = pos.top + 20;
			$("#search_result").css({
				position : "absolute",
				top : ypos + "px",
				left : xpos + "px",
				zIndex : 3000
			});

		} else {
			$("ul#results").fadeOut();
		}
	});


	$("#createAccount").live('click', function() {
		$("#askaspecialistdiv").dialog("close");
		window.location.replace("<?php echo $serverHost; ?>/my3form.php?create=1");
	});

});

