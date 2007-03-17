<cfsilent>
<!--- 
This code uses a CF User Defined Function and should work in CF version 5.0
and up without alteration.

Also if you are hosting your site at an ISP, you will have to check with them
to see if the use of <CFEXECUTE> is allowed. In most cases ISP will not allow
the use of that tag for security reasons. Clients would be able to access each
others files in certain cases.
--->
 
<!--- 
The following variables values must reflect your installation needs.
--->
<cfset apsell_dir	= "c:\aspell\bin">

<cfset lang			= "en_US">
<cfset aspell_opts	= "-a --lang=#lang# --encoding=utf-8 -H">

<!--- Be sure the temporary folder exists --->
<cfset tempFolder	= "c:\aspell\temp">
<cfset tempfile		= "spell_#randrange(1,10000)#">

<cfset spellercss	= "../spellerStyle.css">
<cfset word_win_src	= "../wordWindow.js">

<cfset form.checktext = form["textinputs[]"]>

<cfscript>
  function LastIndexOf(subs, str)
  {
    return Len(str) - Find(subs, Reverse(str)) + 1;
  }
</cfscript>

<!--- Takes care of those pesky smart quotes from MS apps, replaces them with regular quotes --->
<cfparam name="url.checktext" default="">
<cfparam name="form.checktext" default="#url.checktext#">
<cfset submitted_text = replacelist(form.checktext,"%u201C,%u201D","%22,%22")> 

<!--- submitted_text now is ready for processing --->

<!--- use carat on each line to escape possible aspell commands --->
<cfset text = "">
<cfset crlf = Chr(13) & Chr(10)>

<cfloop list="#submitted_text#" index="field" delimiters=",">
  <cfset text = text & "%" & crlf
                     & "^A" & crlf
                     & "!" & crlf>
  <cfset field = URLDecode(field)>
  <cfloop list="#field#" index="line" delimiters="#crlf#">
<!---     <cfset submitted_text = replace(submitted_text,"'","\'","All")>
    <cfset submitted_text = replace(submitted_text,"""","\""","All")> --->
  	<cfset text = text & "^" & Trim(JSStringFormat(line)) & "#crlf#">
  </cfloop>
</cfloop>


<!--- need to escape special javascript characters such as ' --->
<cfset unaltered_text = submitted_text>

<!--- create temp file from the submitted text, this will be passed to aspell to be check for misspelled words --->
<cffile action="write" file="#tempFolder#\#tempfile#.txt" output="#text#" charset="utf-8">

<!--- cfsavecontent is used to set the variable that will be returned with the results from aspell.
If your using the new version of mx 6.1 you can  use the following cfexecute tag instead:
<cfexecute name="C:\WINDOWS\SYSTEM32\cmd.exe" arguments="/c type c:\test\#tempfile#.txt | c:\aspell\bin\aspell #aspell_opts#" timeout="100" variable="results"></cfexecute> --->



<cfsavecontent variable="food">
<cfexecute name="C:\WINDOWS\SYSTEM32\cmd.exe" arguments="/c type #tempFolder#\#tempfile#.txt | #apsell_dir#\aspell #aspell_opts#" timeout="100"></cfexecute>
</cfsavecontent>



<!--- remove temp file --->
<cffile action="delete" file="#tempFolder#\#tempfile#.txt">

<cfset texts = StructNew()>
<cfset texts.textinputs = "">
<cfset texts.words = "">
<cfset texts.abort = "">

<!--- Generate Text Inputs --->

<cfset i = "0">
<cfloop index="text" list="#form.checktext#">
  <cfset texts.textinputs = ListAppend(texts.textinputs, 'textinputs[#i#] = decodeURIComponent("#text#");', '#Chr(13)##Chr(10)#')>
  <cfset i = i + "1">
</cfloop>

<!--- Generate Words Lists --->

<cfset cnt = "1">
<cfset word_cnt = "0">
<cfset input_cnt = "-1">
<cfloop list="#food#" index="list" delimiters="#chr(10)##chr(13)#">
	<!--- removes the first line of the aspell output "@(#) International Ispell Version 3.1.20 (but really Aspell 0.50.3)" --->
	<cfif NOT cnt IS "1">
		<cfif Find("&", list) OR Find("##", list)>
			<!--- word that misspelled --->
			<cfset bad_word = listGetAt(list, "2", " ")>
			<!--- sugestions --->
			<cfset wrdList = mid(list,(LastIndexOf(':', list) + 2),(len(list) - (LastIndexOf(':', list) + 2)))>
			<cfset wrdsList = "">
			<cfloop list="#wrdList#" index="idx">
				<cfset wrdsList = ListAppend(wrdsList, " '" & trim(replace(idx,"'","\'","All")) & "'", ", ")>
			</cfloop>
      <cfset wrdsList = Right(wrdsList, Len(wrdsList) - 1)>
			<!--- javascript --->
			<cfset texts.words = ListAppend(texts.words, "words[#input_cnt#][#word_cnt#] = '#trim(replace(bad_word,"'","\'","All"))#';", "#Chr(13)##Chr(10)#")>
			<cfset texts.words = ListAppend(texts.words, "suggs[#input_cnt#][#word_cnt#] = [#trim(wrdsList)#];", "#Chr(13)##Chr(10)#")>
			<cfset word_cnt = word_cnt + 1>
		<cfelseif find("*", list)>
      <cfset input_cnt = input_cnt + "1">
      <cfset word_cnt = "0">
      <cfset texts.words = ListAppend(texts.words, "words[#input_cnt#] = [];", "#crlf#")>
      <cfset texts.words = ListAppend(texts.words, "suggs[#input_cnt#] = [];", "#crlf#")>
		</cfif>			
	</cfif>
	<cfset cnt = cnt + 1>
</cfloop>

<cfif texts.words IS "">
  <cfset texts.abort = "alert('Spell check complete.\n\nNo misspellings found.');#chrlf#top.window.close();">
</cfif>

</cfsilent><cfoutput><cfcontent type="text/html"><html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="stylesheet" type="text/css" href="#spellercss#" />
<script language="javascript" src="#word_win_src#"></script>
<script language="javascript">
var suggs = new Array();
var words = new Array();
var textinputs = new Array();
var error;

#texts.textinputs##Chr(13)##Chr(10)#
#texts.words#
#texts.abort#

var wordWindowObj = new wordWindow();
wordWindowObj.originalSpellings = words;
wordWindowObj.suggestions = suggs;
wordWindowObj.textInputs = textinputs;

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

<script type="text/javascript">
wordWindowObj.writeBody();
</script>

</body>
</html></cfoutput>
