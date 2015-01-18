
var PageName = 'enter login';
var PageId = 'p32934ad2b74b49918c64e35030c6bd88'
var PageUrl = 'enter_login.html'
document.title = 'enter login';

if (top.location != self.location)
{
	if (parent.HandleMainFrameChanged) {
		parent.HandleMainFrameChanged();
	}
}

var $OnLoadVariable = '';

var $LoginValue = '';

var $PassValue = '';

var $ProjectSelected = '';

var $PreferenceSet = '';

var $CSUM;

var hasQuery = false;
var query = window.location.hash.substring(1);
if (query.length > 0) hasQuery = true;
var vars = query.split("&");
for (var i = 0; i < vars.length; i++) {
    var pair = vars[i].split("=");
    if (pair[0].length > 0) eval("$" + pair[0] + " = decodeURIComponent(pair[1]);");
} 

if (hasQuery && $CSUM != 1) {
alert('Prototype Warning: The variable values were too long to pass to this page.\nIf you are using IE, using Firefox will support more data.');
}

function GetQuerystring() {
    return '#OnLoadVariable=' + encodeURIComponent($OnLoadVariable) + '&LoginValue=' + encodeURIComponent($LoginValue) + '&PassValue=' + encodeURIComponent($PassValue) + '&ProjectSelected=' + encodeURIComponent($ProjectSelected) + '&PreferenceSet=' + encodeURIComponent($PreferenceSet) + '&CSUM=1';
}

function PopulateVariables(value) {
  value = value.replace(/\[\[OnLoadVariable\]\]/g, $OnLoadVariable);
  value = value.replace(/\[\[LoginValue\]\]/g, $LoginValue);
  value = value.replace(/\[\[PassValue\]\]/g, $PassValue);
  value = value.replace(/\[\[ProjectSelected\]\]/g, $ProjectSelected);
  value = value.replace(/\[\[PreferenceSet\]\]/g, $PreferenceSet);
  value = value.replace(/\[\[PageName\]\]/g, PageName);
  return value;
}

function OnLoad(e) {

}

var u20 = document.getElementById('u20');

var u21 = document.getElementById('u21');
gv_vAlignTable['u21'] = 'center';
var u22 = document.getElementById('u22');

var u23 = document.getElementById('u23');
gv_vAlignTable['u23'] = 'center';
var u24 = document.getElementById('u24');

var u25 = document.getElementById('u25');
gv_vAlignTable['u25'] = 'center';
var u26 = document.getElementById('u26');

var u27 = document.getElementById('u27');
gv_vAlignTable['u27'] = 'center';
var u28 = document.getElementById('u28');

u28.style.cursor = 'pointer';
if (bIE) u28.attachEvent("onclick", Clicku28);
else u28.addEventListener("click", Clicku28, true);
function Clicku28(e)
{

if (((GetWidgetFormText('u15')) == (PopulateVariables('test'))) && ((GetWidgetFormText('u16')) == (PopulateVariables('test')))) {

	self.location.href="Time.html" + GetQuerystring();

}

}

var u29 = document.getElementById('u29');
gv_vAlignTable['u29'] = 'center';
var u30 = document.getElementById('u30');

var u31 = document.getElementById('u31');
gv_vAlignTable['u31'] = 'center';
var u0 = document.getElementById('u0');

var u1 = document.getElementById('u1');
gv_vAlignTable['u1'] = 'center';
var u2 = document.getElementById('u2');
gv_vAlignTable['u2'] = 'top';
var u3 = document.getElementById('u3');
gv_vAlignTable['u3'] = 'top';
var u4 = document.getElementById('u4');

var u5 = document.getElementById('u5');
gv_vAlignTable['u5'] = 'center';
var u6 = document.getElementById('u6');
gv_vAlignTable['u6'] = 'top';
var u7 = document.getElementById('u7');

u7.style.cursor = 'pointer';
if (bIE) u7.attachEvent("onclick", Clicku7);
else u7.addEventListener("click", Clicku7, true);
function Clicku7(e)
{

if (true) {

	self.location.href="Intro.html" + GetQuerystring();

}

}

var u8 = document.getElementById('u8');

var u9 = document.getElementById('u9');
gv_vAlignTable['u9'] = 'center';
var u10 = document.getElementById('u10');

u10.style.cursor = 'pointer';
if (bIE) u10.attachEvent("onclick", Clicku10);
else u10.addEventListener("click", Clicku10, true);
function Clicku10(e)
{

if (true) {

	self.location.href="Select_projects.html" + GetQuerystring();

}

}

var u11 = document.getElementById('u11');
gv_vAlignTable['u11'] = 'center';
var u12 = document.getElementById('u12');

u12.style.cursor = 'pointer';
if (bIE) u12.attachEvent("onclick", Clicku12);
else u12.addEventListener("click", Clicku12, true);
function Clicku12(e)
{

if (true) {

	self.location.href="Reports.html" + GetQuerystring();

}

}

var u13 = document.getElementById('u13');
gv_vAlignTable['u13'] = 'center';
var u14 = document.getElementById('u14');
gv_vAlignTable['u14'] = 'top';
var u15 = document.getElementById('u15');

if (bIE) u15.attachEvent("onfocus", Focusu15);
else u15.addEventListener("focus", Focusu15, true);
function Focusu15(e)
{

if (true) {

SetGlobalVariableValue('$LoginValue', GetWidgetFormText('u15'));

SetWidgetFormText('u15', PopulateVariables(''));

}

}

if (bIE) u15.attachEvent("onblur", LostFocusu15);
else u15.addEventListener("blur", LostFocusu15, true);
function LostFocusu15(e)
{

if ((GetWidgetFormText('u15')) > Number(PopulateVariables(''))) {

SetGlobalVariableValue('$LoginValue', GetWidgetFormText('u15'));

SetWidgetFormText('u15', GetGlobalVariableValue('$LoginValue'));

}
else
if ((GetWidgetFormText('u15')) <= Number(PopulateVariables(''))) {

SetWidgetFormText('u15', GetGlobalVariableValue('$LoginValue'));

}

}

var u16 = document.getElementById('u16');

if (bIE) u16.attachEvent("onfocus", Focusu16);
else u16.addEventListener("focus", Focusu16, true);
function Focusu16(e)
{

if (true) {

SetGlobalVariableValue('$PassValue', GetWidgetFormText('u16'));

SetWidgetFormText('u16', PopulateVariables(''));

}

}

var u17 = document.getElementById('u17');

var u18 = document.getElementById('u18');
gv_vAlignTable['u18'] = 'center';
var u19 = document.getElementById('u19');
gv_vAlignTable['u19'] = 'top';
if (window.OnLoad) OnLoad();
