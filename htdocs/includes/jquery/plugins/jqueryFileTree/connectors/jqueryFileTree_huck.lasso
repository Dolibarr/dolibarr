[
	//
	// jQuery File Tree Lasso Connector
	//
	// Version 1.00
	//
	// Jason Huck
	// http://devblog.jasonhuck.com/
	// 1 May 2008
	//
	// History:
	//
	// 1.00 - released (1 May 2008)
	//
	// Output a list of files for jQuery File Tree
	//

	!action_param('dir') ? abort;
	var('dir') = action_param('dir');
	var('files') = file_listdirectory($dir);

	'<ul class="jqueryFileTree" style="display: none;">';

	iterate($files, local('file'));
		#file->beginswith('.') ? loop_continue;
	
		if(#file->endswith('/'));
			'<li class="directory collapsed"><a href="#" rel="' + $dir + #file + '">' + #file + '</a></li>';
		else;
			local('ext') = #file->split('.')->last;			
			'<li class="file ext_' + #ext + '"><a href="#" rel="' + $dir + #file + '">' + #file + '</a></li>';
		/if;
	/iterate;
	
	'</ul>';
]
