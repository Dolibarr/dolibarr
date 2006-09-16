<!--- Coldfusion MX uses java on the server to process tags. So it is save to use most java attributes. For example below
I use list.lastindexOf(), lastindexOf() is a java string attribute. If you plan on using this tag with earlier versions
of Coldfusion, you will have to replace the lastindexOf with a pure coldfusion function. By replacing the lastindexOf, spellchecker.cfm
script should be compatible with all cf version 4.5 and up.

Also if you are hosting your site at an ISP, you will have to check with them to see if the use of <CFEXECUTE> is allowed. 
In most cases ISP will not allow the use of that tag for security reasons. Clients would be able to access each others files in certain cases.
 --->


<!--- Set up variables --->
<cfset tempFolder = "c:\test">
<cfset tempfile = "spell_#randrange(1,1000)#">
<cfset apsell_dir = "c:\aspell\bin">
<!--- <cfset spellercss = "/speller/spellerStyle.css">		by FredCK --->
<cfset spellercss = "../spellerStyle.css">
<!--- <cfset word_win_src = "/speller/wordWindow.js">		by FredCK --->
<cfset word_win_src = "../wordWindow.js">


<!--- Takes care of those pesky smart quotes from MS apps, replaces them with regular quotes --->
<cfset submitted_text = replacelist(form.checktext,"%u201C,%u201D","%22,%22")> 
<cfset submitted_text = urlDecode(submitted_text)>




<!--- need to escape special javascript characters such as ' --->
<cfset unaltered_text = submitted_text>
<cfset submitted_text = replace(submitted_text,"'","\'","All")>
<cfset submitted_text = replace(submitted_text,"""","\""","All")>

<!--- use carat on each line to escape possible aspell commands --->
<cfset text = "">
<cfloop list="#submitted_text#" index="idx" delimiters="#chr(10)##chr(13)#">
	<cfset text =text&"^"&idx&"#chr(10)##chr(13)#">
</cfloop>



<!--- create temp file from the submitted text, this will be passed to aspell to be check for misspelled words --->
<cffile action="write" file="#tempFolder#\#tempfile#.txt" output="#text#" charset="utf-8">


<!--- cfsavecontent is used to set the variable that will be returned with the results from aspell.
If your using the new version of mx 6.1 you can  use the following cfexecute tag instead:
<cfexecute name="C:\WINDOWS\SYSTEM32\cmd.exe" arguments="/c type c:\test\#tempfile#.txt | c:\aspell\bin\aspell -a" timeout="100" variable="results"></cfexecute> --->

<cfsavecontent variable="food">
<cfexecute name="C:\WINDOWS\SYSTEM32\cmd.exe" arguments="/c type #tempFolder#\#tempfile#.txt | #apsell_dir#\aspell -a" timeout="100"></cfexecute>
</cfsavecontent>

<!--- remove temp file --->
<cffile action="delete" file="#tempFolder#\#tempfile#.txt">




<cfoutput>
<html>
<head>
<link rel="stylesheet" type="text/css" href="speller/spellerStyle.css">
<script src="/speller/wordWindow.js"></script>
<script language="javascript">
var suggs = new Array();
var words = new Array();
var error;
var wordtext = unescape('#urlencodedFormat(unaltered_text)#');

<cfset cnt = 1>
<cfset word_cnt = 0>
<cfloop list="#food#" index="list" delimiters="#chr(10)##chr(13)#">
	<!--- removes the first line of the aspell output "@(#) International Ispell Version 3.1.20 (but really Aspell 0.50.3)" --->
	<cfif NOT cnt EQ 1>
		<cfif find("&",list) OR find("##",list)>
			<!--- word that misspelled --->
			<cfset bad_word = listGetAt(list,"2"," ")>
			<!--- sugestions --->
			<cfset wrdList = mid(list,(list.lastindexOf(':') + 2),(len(list) - (list.lastindexOf(':') + 2)))>
			<cfset wrdsList = "">
			<cfloop list=#wrdList# index="idx">
				<cfset wrdsList =wrdsList&"'"&trim(replace(idx,"'","\'","All"))&"',">
			</cfloop>
			<!--- javascript --->
			words[#word_cnt#] = '#trim(replace(bad_word,"'","\'","All"))#';
			suggs[#word_cnt#] = [#trim(wrdsList)#];
			<cfset word_cnt = word_cnt + 1>
		<cfelseif find("*",list)>
		</cfif>			
	</cfif>
	<cfset cnt = cnt + 1>
</cfloop>






var wordWindowObj = new wordWindow();
wordWindowObj.originalSpellings = words;
wordWindowObj.suggestions = suggs;
wordWindowObj.text = wordtext;


function init_spell() {
	// check if any error occured during server-side processing
	if( error ) {
		alert( error );
	} else {
		// call the init_spell() function in the parent frameset
		if (parent.frames.length) {
			parent.init_spell( wordWindowObj );
		} else {
			alert('This page was loaded outside of a frameset. It might not display properly');
		}
	}
}



</script>

</head>
<body onLoad="init_spell();">

<script>
wordWindowObj.writeBody();
</script>

</body>
</html>
</cfoutput>