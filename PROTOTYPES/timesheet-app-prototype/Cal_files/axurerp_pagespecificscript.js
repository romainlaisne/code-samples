
var PageName = 'Cal';
var PageId = 'p8c2c78d9a89c466293329c68d4cb6fa9'
var PageUrl = 'Cal.html'
document.title = 'Cal';

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

var u80 = document.getElementById('u80');

var u81 = document.getElementById('u81');
gv_vAlignTable['u81'] = 'top';
var u82 = document.getElementById('u82');

var u83 = document.getElementById('u83');
gv_vAlignTable['u83'] = 'top';
var u84 = document.getElementById('u84');

var u85 = document.getElementById('u85');
gv_vAlignTable['u85'] = 'top';
var u86 = document.getElementById('u86');

var u87 = document.getElementById('u87');
gv_vAlignTable['u87'] = 'top';
var u88 = document.getElementById('u88');

var u89 = document.getElementById('u89');
gv_vAlignTable['u89'] = 'top';
var u90 = document.getElementById('u90');

var u91 = document.getElementById('u91');
gv_vAlignTable['u91'] = 'top';
var u92 = document.getElementById('u92');
gv_vAlignTable['u92'] = 'top';
var u10 = document.getElementById('u10');

var u11 = document.getElementById('u11');
gv_vAlignTable['u11'] = 'top';
var u12 = document.getElementById('u12');

var u13 = document.getElementById('u13');
gv_vAlignTable['u13'] = 'top';
var u14 = document.getElementById('u14');

var u15 = document.getElementById('u15');
gv_vAlignTable['u15'] = 'top';
var u16 = document.getElementById('u16');

var u17 = document.getElementById('u17');
gv_vAlignTable['u17'] = 'top';
var u18 = document.getElementById('u18');

var u19 = document.getElementById('u19');
gv_vAlignTable['u19'] = 'top';
var u20 = document.getElementById('u20');

var u21 = document.getElementById('u21');
gv_vAlignTable['u21'] = 'top';
var u22 = document.getElementById('u22');

var u23 = document.getElementById('u23');
gv_vAlignTable['u23'] = 'top';
var u24 = document.getElementById('u24');

var u25 = document.getElementById('u25');
gv_vAlignTable['u25'] = 'top';
var u26 = document.getElementById('u26');

var u27 = document.getElementById('u27');
gv_vAlignTable['u27'] = 'top';
var u28 = document.getElementById('u28');

var u29 = document.getElementById('u29');
gv_vAlignTable['u29'] = 'top';
var u30 = document.getElementById('u30');

var u31 = document.getElementById('u31');
gv_vAlignTable['u31'] = 'top';
var u32 = document.getElementById('u32');

var u33 = document.getElementById('u33');
gv_vAlignTable['u33'] = 'top';
var u34 = document.getElementById('u34');

var u35 = document.getElementById('u35');
gv_vAlignTable['u35'] = 'top';
var u36 = document.getElementById('u36');

var u37 = document.getElementById('u37');
gv_vAlignTable['u37'] = 'top';
var u38 = document.getElementById('u38');

var u39 = document.getElementById('u39');
gv_vAlignTable['u39'] = 'top';
var u93 = document.getElementById('u93');
gv_vAlignTable['u93'] = 'top';
var u94 = document.getElementById('u94');
gv_vAlignTable['u94'] = 'top';
var u95 = document.getElementById('u95');
gv_vAlignTable['u95'] = 'top';
var u96 = document.getElementById('u96');
gv_vAlignTable['u96'] = 'top';
var u97 = document.getElementById('u97');
gv_vAlignTable['u97'] = 'top';
var u98 = document.getElementById('u98');
gv_vAlignTable['u98'] = 'top';
var u99 = document.getElementById('u99');
gv_vAlignTable['u99'] = 'top';
var u40 = document.getElementById('u40');

var u41 = document.getElementById('u41');
gv_vAlignTable['u41'] = 'top';
var u42 = document.getElementById('u42');

var u43 = document.getElementById('u43');
gv_vAlignTable['u43'] = 'top';
var u44 = document.getElementById('u44');

var u45 = document.getElementById('u45');
gv_vAlignTable['u45'] = 'top';
var u46 = document.getElementById('u46');

var u47 = document.getElementById('u47');
gv_vAlignTable['u47'] = 'top';
var u48 = document.getElementById('u48');

var u49 = document.getElementById('u49');
gv_vAlignTable['u49'] = 'top';
var u0 = document.getElementById('u0');

var u1 = document.getElementById('u1');
gv_vAlignTable['u1'] = 'center';
var u2 = document.getElementById('u2');

var u3 = document.getElementById('u3');
gv_vAlignTable['u3'] = 'center';
var u4 = document.getElementById('u4');

var u5 = document.getElementById('u5');
gv_vAlignTable['u5'] = 'center';
var u6 = document.getElementById('u6');
gv_vAlignTable['u6'] = 'top';
var u7 = document.getElementById('u7');

var u8 = document.getElementById('u8');

var u9 = document.getElementById('u9');
gv_vAlignTable['u9'] = 'top';
var u50 = document.getElementById('u50');

var u51 = document.getElementById('u51');
gv_vAlignTable['u51'] = 'top';
var u52 = document.getElementById('u52');

var u53 = document.getElementById('u53');
gv_vAlignTable['u53'] = 'top';
var u54 = document.getElementById('u54');

var u55 = document.getElementById('u55');
gv_vAlignTable['u55'] = 'top';
var u56 = document.getElementById('u56');

var u57 = document.getElementById('u57');
gv_vAlignTable['u57'] = 'top';
var u58 = document.getElementById('u58');

var u59 = document.getElementById('u59');
gv_vAlignTable['u59'] = 'top';
var u60 = document.getElementById('u60');

var u61 = document.getElementById('u61');
gv_vAlignTable['u61'] = 'top';
var u62 = document.getElementById('u62');

var u63 = document.getElementById('u63');
gv_vAlignTable['u63'] = 'top';
var u64 = document.getElementById('u64');

var u65 = document.getElementById('u65');
gv_vAlignTable['u65'] = 'top';
var u66 = document.getElementById('u66');

var u67 = document.getElementById('u67');
gv_vAlignTable['u67'] = 'top';
var u68 = document.getElementById('u68');

var u69 = document.getElementById('u69');
gv_vAlignTable['u69'] = 'top';
var u70 = document.getElementById('u70');

var u71 = document.getElementById('u71');
gv_vAlignTable['u71'] = 'top';
var u72 = document.getElementById('u72');

var u73 = document.getElementById('u73');
gv_vAlignTable['u73'] = 'top';
var u74 = document.getElementById('u74');

var u75 = document.getElementById('u75');
gv_vAlignTable['u75'] = 'top';
var u76 = document.getElementById('u76');

var u77 = document.getElementById('u77');
gv_vAlignTable['u77'] = 'top';
var u78 = document.getElementById('u78');

var u79 = document.getElementById('u79');
gv_vAlignTable['u79'] = 'top';
if (window.OnLoad) OnLoad();
