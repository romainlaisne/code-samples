
var PageName = 'Confirmation';
var PageId = 'pc4220a4ece4e4a0f9cec7522eb6b9323'
var PageUrl = 'Confirmation.html'
document.title = 'Confirmation';

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

if (true) {
function waitue2008ca1a86649989315d099c3d000601() {

	self.location.href="Select_projects.html" + GetQuerystring();
}
setTimeout(waitue2008ca1a86649989315d099c3d000601, 1000);

}

}

var u18 = document.getElementById('u18');
gv_vAlignTable['u18'] = 'top';
var u17 = document.getElementById('u17');
gv_vAlignTable['u17'] = 'center';
var u16 = document.getElementById('u16');

var u15 = document.getElementById('u15');
gv_vAlignTable['u15'] = 'center';
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
if (window.OnLoad) OnLoad();
