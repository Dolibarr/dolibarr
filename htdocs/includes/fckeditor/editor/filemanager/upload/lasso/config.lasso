[//lasso
/*
 * FCKeditor - The text editor for Internet - http://www.fckeditor.net
 * Copyright (C) 2003-2007 Frederico Caldeira Knabben
 * 
 * == BEGIN LICENSE ==
 * 
 * Licensed under the terms of any of the following licenses at your
 * choice:
 * 
 *  - GNU General Public License Version 2 or later (the "GPL")
 *    http://www.gnu.org/licenses/gpl.html
 * 
 *  - GNU Lesser General Public License Version 2.1 or later (the "LGPL")
 *    http://www.gnu.org/licenses/lgpl.html
 * 
 *  - Mozilla Public License Version 1.1 or later (the "MPL")
 *    http://www.mozilla.org/MPL/MPL-1.1.html
 * 
 * == END LICENSE ==
 * 
 * File Name: config.lasso
 * 	Configuration file for the Lasso File Uploader.
 * 
 * File Authors:
 * 		Jason Huck (jason.huck@corefive.com)
 */

    /*.....................................................................     
    The connector uses the file tags, which require authentication. Enter a
    valid username and password from Lasso admin for a group with file tags
    permissions for uploads and the path you define in UserFilesPath below.                                                                        
    */ 
    
	var('connection') = array(
		-username='xxxxxxxx',
		-password='xxxxxxxx'
	);


    /*.....................................................................     
    Set the base path for files that users can upload and browse (relative
    to server root).
    
    Set which file extensions are allowed and/or denied for each file type.                                                                           
    */                                                                          
	var('config') = map(
		'Enabled' = false,
		'UserFilesPath' = '/userfiles/',
		'Subdirectories' = map(
			'File' = 'File/',
			'Image' = 'Image/',
			'Flash' = 'Flash/',
			'Media' = 'Media/'
		),
		'AllowedExtensions' = map(
			'File' = array(),
			'Image' = array('jpg','gif','jpeg','png'),
			'Flash' = array('swf','fla'),
			'Media' = array('swf','fla','jpg','gif','jpeg','png','avi','mpg','mpeg')
		),
		'DeniedExtensions' = map(
			'File' = array('html','htm','php','php2','php3','php4','php5','phtml','pwml','inc','asp','aspx','ascx','jsp','cfm','cfc','pl','bat','exe','com','dll','vbs','js','reg','cgi','lasso','lassoapp','htaccess','asis'),
			'Image' = array(),
			'Flash' = array(),
			'Media' = array()
		)
	);
]
