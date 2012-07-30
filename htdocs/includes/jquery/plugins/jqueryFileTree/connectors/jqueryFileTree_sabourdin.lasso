<?LassoScript
//
// jQuery File Tree LASSO Connector
//
// Version 1.00
//
// Marc Sabourdin
// CysNET (http://www.marcsabourdin.com/)
// 23 May 2008
//
// History:
//
// 1.00 - released (23 May 2008)
//
// Output a list of files for jQuery File Tree
//
Encode_set:-EncodeNone;

Variable:'root' = 'path_to_desired_and_Lasso_allowed_root';
Variable:'_POST.dir' = (action_param:'dir');
Variable:'files';


if:( file_exists: ($root + $_POST.dir) )&&( File_IsDirectory:($root + $_POST.dir) );
	$files = (File_ListDirectory:($root + $_POST.dir));
	$files->(Sort);
	if:( $files->(Size) > 0 );
		output:'<ul class="jqueryFileTree" style="display: none;">';
		// All dirs
		Iterate:($files),(Local:'file');
			if:( file_exists:($root + $_POST.dir + #file) )&&( #file != '.' )&&( #file != '..' )&&( File_IsDirectory:($root + $_POST.dir + #file) );
				output:'<li class="directory collapsed"><a href="#" rel="' + (String_replace:($_POST.dir + #file),-Find=' ',-Replace='__') + '">' + (Encode_HTML:(#file)) + '</a></li>';
			/if;
		/Iterate;
		// All files
		Local:'ext';
		Iterate:($files),(Local:'file');
			if:( file_exists:($root + $_POST.dir + #file) )&&( #file != '.' )&&( #file != '..' )&&( (File_IsDirectory:($root + $_POST.dir + #file))==false );
				#ext = (#file)->(Split:'.')->Last;
				output:'<li class="file ext_' + (#ext) + '"><a href="' + ($_POST.dir + #file) + '">' + (Encode_HTML:(#file)) + '</a></li>';
			/if;
		/Iterate;
		output:'</ul>';	
	/if;
/if;

/Encode_set;
?>