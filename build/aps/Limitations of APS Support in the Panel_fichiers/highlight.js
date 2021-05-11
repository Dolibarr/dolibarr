function last(href)
{
  var ret = href.split("/");
  return ret[ret.length-1];
}

function StopProcess()
{
LeftFrame = parent.TOC.document.location.href;
LeftFrame = last(LeftFrame);
if (LeftFrame == "dhtml_search.htm") return 1
else return 0;
}



function highlightTOC(str) {
    
	
		
		 
	
    if (StopProcess()) return;
    try {
		
    str = str || parent.BODY.document.location.href;
    uri = last(str);
    list = parent.TOC.document.getElementsByTagName("a");
    for(i=0; i<list.length; i++)
    {
      if (last(list[i].href) == uri)
	{
        list[i].style.backgroundColor = "#6697cc";
        list[i].style.padding = "2px";
        list[i].style.color = "#ffffff";
		
	} else {
		list[i].style.backgroundColor = "#ffffff";
        list[i].style.color = "#003380";
	  }
    }
    } catch (e) {}
}

function FindCorrectTOCPage()
{
if (StopProcess()) return;
//Updated on 30.04.2008
list = parent.BODY.document.getElementsByTagName("a") || document.getElementsByTagName("a");
    for(i=0; i<list.length; i++)
    {
      if (list[i].target == "TOC")
	{

		if (last(list[i].href) != last(parent.TOC.document.location.href))
			{
				parent.TOC.document.location.href = list[i].href;
				return;
			}
	}
    }


}
