/*
   Copyright (c) 2007-9, iUI Project Members
   See LICENSE.txt for licensing terms
 */

iui.ts = {
	themeSelect: function(select)
	{
		var curTheme = iui.ts.getTheme();
		var index = select.selectedIndex;
		var newTheme = select.options[index].value;
		iui.ts.setTheme(newTheme);
		return false;
	},

	getTheme: function()
	{
		var i, a, main;
		for(i=0; (a = document.getElementsByTagName("link")[i]); i++)
		{
			if(a.getAttribute("rel").indexOf("style") != -1
			&& a.getAttribute("title"))
			{
				if (a.disabled == false) return a.getAttribute("title");
			}
		}
	},
	
	setTheme: function(title)
	{
		var i, a, main;
		for(i=0; (a = document.getElementsByTagName("link")[i]); i++)
		{
			if(a.getAttribute("rel").indexOf("style") != -1
			&& a.getAttribute("title"))
			{
				a.disabled = true;
				if(a.getAttribute("title") == title) a.disabled = false;
			}
		}
	}
};