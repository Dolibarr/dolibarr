[//lasso
/*
 * FCKeditor - The text editor for internet
 * Copyright (C) 2003-2006 Frederico Caldeira Knabben
 * 
 * Licensed under the terms of the GNU Lesser General Public License:
 * 		http://www.opensource.org/licenses/lgpl-license.php
 * 
 * For further information visit:
 * 		http://www.fckeditor.net/
 * 
 * "Support Open Source software. What about a donation today?"
 * 
 * File Name: connector.lasso
 * 	This is the File Manager Connector for Lasso.
 * 
 * File Authors:
 * 		Jason Huck (jason.huck@corefive.com)
 */

    /*.....................................................................     
    Include global configuration. See config.lasso for details.                                                                           
    */                                                                          
	include('config.lasso');

		
    /*.....................................................................     
    Translate current date/time to GMT for custom header.                                                                          
    */                                                                          
	var('headerDate') = date_localtogmt(date)->format('%a, %d %b %Y %T GMT');


    /*.....................................................................     
    Convert query string parameters to variables and initialize output.                                                                           
    */                                                                          
	var(
		'Command'		=	action_param('Command'),
		'Type'			=	action_param('Type'),
		'CurrentFolder'	=	action_param('CurrentFolder'),
		'ServerPath'	=	action_param('ServerPath'),
		'NewFolderName'	=	action_param('NewFolderName'),
		'NewFile'		=	null,
		'NewFileName'	=	string,
		'OrigFilePath'	=	string,
		'NewFilePath'	=	string,
		'commandData'	=	string,
		'folders'		=	'\t<Folders>\n',
		'files'			=	'\t<Files>\n',
		'errorNumber'	=	integer,
		'responseType'	=	'xml',
		'uploadResult'	=	'0'
	);


    /*.....................................................................     
    Calculate the path to the current folder.                                                                           
    */                                                                          
	$ServerPath == '' ? $ServerPath = $config->find('UserFilesPath');
		
	var('currentFolderURL' = $ServerPath 
		+ $config->find('Subdirectories')->find(action_param('Type'))
		+ action_param('CurrentFolder')
	);


    /*.....................................................................     
    Build the appropriate response per the 'Command' parameter. Wrap the
    entire process in an inline for file tag permissions.                                                                         
    */                                                                          
	inline($connection);
		select($Command);	
            /*.............................................................     
            List all subdirectories in the 'Current Folder' directory.                                                                   
            */                                                                  
			case('GetFolders');
				$commandData += '\t<Folders>\n';
			
				iterate(file_listdirectory($currentFolderURL), local('this'));
					#this->endswith('/') ? $commandData += '\t\t<Folder name="' + #this->removetrailing('/')& + '" />\n';
				/iterate;
				
				$commandData += '\t</Folders>\n';


            /*.............................................................     
            List both files and folders in the 'Current Folder' directory.
            Include the file sizes in kilobytes.                                                                   
            */                                                                  				
			case('GetFoldersAndFiles');
				iterate(file_listdirectory($currentFolderURL), local('this'));
					if(#this->endswith('/'));
						$folders += '\t\t<Folder name="' + #this->removetrailing('/')& + '" />\n';
					else;
						local('size') = file_getsize($currentFolderURL + #this) / 1024;
						$files += '\t\t<File name="' + #this + '" size="' + #size + '" />\n';
					/if;					
				/iterate;

				$folders += '\t</Folders>\n';
				$files += '\t</Files>\n';
				
				$commandData += $folders + $files;


            /*.............................................................     
            Create a directory 'NewFolderName' within the 'Current Folder.'                                                                 
            */                                                                  				
			case('CreateFolder');
				var('newFolder' = $currentFolderURL + $NewFolderName + '/');			
				file_create($newFolder);			
				
				
                /*.........................................................     
                Map Lasso's file error codes to FCKEditor's error codes.                                                              
                */                                                              				
				select(file_currenterror( -errorcode));
					case(0);
						$errorNumber = 0;
					case( -9983);
						$errorNumber = 101;
					case( -9976);
						$errorNumber = 102;
					case( -9977);
						$errorNumber = 102;
					case( -9961);
						$errorNumber = 103;
					case;
						$errorNumber = 110;
				/select;
				
				$commandData += '<Error number="' + $errorNumber + '" />\n';


            /*.............................................................     
            Process an uploaded file.                                                                  
            */                                                                  				
			case('FileUpload');		
                /*.........................................................     
                This is the only command that returns an HTML response.                                                              
                */                                                              			
				$responseType = 'html';
				
				
                /*.........................................................     
                Was a file actually uploaded?                                                              
                */                                                              
				file_uploads->size ? $NewFile = file_uploads->get(1) | $uploadResult = '202';
								
				if($uploadResult == '0');
                    /*.....................................................     
                    Split the file's extension from the filename in order
                    to follow the API's naming convention for duplicate
                    files. (Test.txt, Test(1).txt, Test(2).txt, etc.)                                                          
                    */                                                          
					$NewFileName = $NewFile->find('OrigName');													
					$OrigFilePath = $currentFolderURL + $NewFileName;
					$NewFilePath = $OrigFilePath;
					local('fileExtension') = '.' + $NewFile->find('OrigExtension');					
					local('shortFileName') = $NewFileName->removetrailing(#fileExtension)&;


                    /*.....................................................     
                    Make sure the file extension is allowed.                                                          
                    */                                                          
					if($config->find('DeniedExtensions')->find($Type) >> $NewFile->find('OrigExtension'));
						$uploadResult = '202';
					else;
                        /*.................................................     
                        Rename the target path until it is unique.                                                    
                        */                                                      										
						while(file_exists($NewFilePath));
							$NewFilePath = $currentFolderURL + #shortFileName + '(' + loop_count + ')' + #fileExtension;
						/while;
						
						
                        /*.................................................     
                        Copy the uploaded file to its final location.                                                     
                        */                                                      
						file_copy($NewFile->find('path'), $NewFilePath);


                        /*.................................................     
                        Set the error code for the response. Note whether
                        the file had to be renamed.                                                      
                        */                                                      						
						select(file_currenterror( -errorcode));
							case(0);
								$OrigFilePath != $NewFilePath ? $uploadResult = '201, \'' + $NewFilePath->split('/')->last + '\'';
							case;
								$uploadResult = '202';
						/select;
					/if;
				/if;


                /*.........................................................     
                Set the HTML response.                                                               
                */                                                              				
				$__html_reply__ = '\
<script type="text/javascript">
	window.parent.frames[\'frmUpload\'].OnUploadCompleted(' + $uploadResult + ');
</script>
				';
		/select;
	/inline;


    /*.....................................................................     
    Send a custom header for xml responses.                                                                          
    */                                                                          
	if($responseType == 'xml');
		header;
]
HTTP/1.0 200 OK
Date: [$headerDate]
Server: Lasso Professional [lasso_version( -lassoversion)]
Expires: Mon, 26 Jul 1997 05:00:00 GMT
Last-Modified: [$headerDate]
Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0
Pragma: no-cache
Keep-Alive: timeout=15, max=98
Connection: Keep-Alive
Content-Type: text/xml; charset=utf-8
[//lasso
		/header;


        /*.................................................................     
        Set the content type encoding for Lasso.                                                                      
        */                                                                      
		content_type('text/xml; charset=utf-8');


        /*.................................................................     
        Wrap the response as XML and output.                                                                      
        */                                                                      
		$__html_reply__ = '\
<?xml version="1.0" encoding="utf-8" ?>
<Connector command="' + $Command + '" resourceType="' + $Type + '">
	<CurrentFolder path="' + $CurrentFolder + '" url="' + $currentFolderURL + '" />
' + $commandData + '
</Connector>
		';
	/if;
]	
