
var PageName = 'New Page 2';
var PageId = 'p9c59083e51bc4611a9dd56c37f1e3cfe'
var PageUrl = 'New_Page_2.html'
document.title = 'New Page 2';

if (top.location != self.location)
{
	if (parent.HandleMainFrameChanged) {
		parent.HandleMainFrameChanged();
	}
}

if (window.OnLoad) OnLoad();
