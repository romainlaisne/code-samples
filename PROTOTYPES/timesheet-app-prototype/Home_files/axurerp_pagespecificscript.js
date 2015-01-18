
var PageName = 'Home';
var PageId = 'p48a942bd0c2b4b61b0f941c040246aa1'
var PageUrl = 'Home.html'
document.title = 'Home';

if (top.location != self.location)
{
	if (parent.HandleMainFrameChanged) {
		parent.HandleMainFrameChanged();
	}
}

var $OnLoadVariable = '';

var $LoginValue = '';

var $PassValue = '';

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
    return '#OnLoadVariable=' + encodeURIComponent($OnLoadVariable) + '&LoginValue=' + encodeURIComponent($LoginValue) + '&PassValue=' + encodeURIComponent($PassValue) + '&CSUM=1';
}

function PopulateVariables(value) {
  value = value.replace(/\[\[OnLoadVariable\]\]/g, $OnLoadVariable);
  value = value.replace(/\[\[LoginValue\]\]/g, $LoginValue);
  value = value.replace(/\[\[PassValue\]\]/g, $PassValue);
  value = value.replace(/\[\[PageName\]\]/g, PageName);
  return value;
}

function OnLoad(e) {

}

var u0 = document.getElementById('u0');
gv_vAlignTable['u0'] = 'top';
var u1 = document.getElementById('u1');

if (bIE) u1.attachEvent("onfocus", Focusu1);
else u1.addEventListener("focus", Focusu1, true);
function Focusu1(e)
{

if (true) {

SetGlobalVariableValue('$LoginValue', GetWidgetFormText('u1'));

SetWidgetFormText('u1', PopulateVariables(''));

}

}

if (bIE) u1.attachEvent("onblur", LostFocusu1);
else u1.addEventListener("blur", LostFocusu1, true);
function LostFocusu1(e)
{

if ((GetWidgetFormText('u1')) > Number(PopulateVariables(''))) {

SetGlobalVariableValue('$LoginValue', GetWidgetFormText('u1'));

SetWidgetFormText('u1', GetGlobalVariableValue('$LoginValue'));

}
else
if ((GetWidgetFormText('u1')) <= Number(PopulateVariables(''))) {

SetWidgetFormText('u1', GetGlobalVariableValue('$LoginValue'));

}

}

var u2 = document.getElementById('u2');

if (bIE) u2.attachEvent("onfocus", Focusu2);
else u2.addEventListener("focus", Focusu2, true);
function Focusu2(e)
{

if (true) {

SetGlobalVariableValue('$PassValue', GetWidgetFormText('u2'));

SetWidgetFormText('u2', PopulateVariables(''));

}

}

var u3 = document.getElementById('u3');

u3.style.cursor = 'pointer';
if (bIE) u3.attachEvent("onclick", Clicku3);
else u3.addEventListener("click", Clicku3, true);
function Clicku3(e)
{

if (((GetWidgetFormText('u1')) == (PopulateVariables('test'))) && ((GetWidgetFormText('u2')) == (PopulateVariables('test')))) {

	self.location.href="Page_2.html" + GetQuerystring();

}

}

var u4 = document.getElementById('u4');

var u5 = document.getElementById('u5');
gv_vAlignTable['u5'] = 'center';
var u6 = document.getElementById('u6');
gv_vAlignTable['u6'] = 'top';
if (window.OnLoad) OnLoad();
