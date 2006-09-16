#####
#  FCKeditor - The text editor for internet
#  Copyright (C) 2003-2006 Frederico Caldeira Knabben
#  
#  Licensed under the terms of the GNU Lesser General Public License:
#  		http://www.opensource.org/licenses/lgpl-license.php
#  
#  For further information visit:
#  		http://www.fckeditor.net/
#  
#  "Support Open Source software. What about a donation today?"
#  
#  File Name: commands.pl
#  	This is the File Manager Connector for Perl.
#  
#  File Authors:
#  		Takashi Yamaguchi (jack@omakase.net)
#####

sub GetFolders
{

	local($resourceType, $currentFolder) = @_;

	# Map the virtual path to the local server path.
	$sServerDir = &ServerMapFolder($resourceType, $currentFolder);
	print "<Folders>";			# Open the "Folders" node.

	opendir(DIR,"$sServerDir");
	@files = grep(!/^\.\.?$/,readdir(DIR));
	closedir(DIR);

	foreach $sFile (@files) {
		if($sFile != '.' && $sFile != '..' && (-d "$sServerDir$sFile")) {
			$cnv_filename = &ConvertToXmlAttribute($sFile);
			print '<Folder name="' . $cnv_filename . '" />';
		}
	}
	print "</Folders>";			# Close the "Folders" node.
}

sub GetFoldersAndFiles
{

	local($resourceType, $currentFolder) = @_;
	# Map the virtual path to the local server path.
	$sServerDir = &ServerMapFolder($resourceType,$currentFolder);

	# Initialize the output buffers for "Folders" and "Files".
	$sFolders	= '<Folders>';
	$sFiles		= '<Files>';

	opendir(DIR,"$sServerDir");
	@files = grep(!/^\.\.?$/,readdir(DIR));
	closedir(DIR);

	foreach $sFile (@files) {
		if($sFile ne '.' && $sFile ne '..') {
			if(-d "$sServerDir$sFile") {
				$cnv_filename = &ConvertToXmlAttribute($sFile);
				$sFolders .= '<Folder name="' . $cnv_filename . '" />' ;
			} else {
				($iFileSize,$refdate,$filedate,$fileperm) = (stat("$sServerDir$sFile"))[7,8,9,2];
				if($iFileSize > 0) {
					$iFileSize = int($iFileSize / 1024);
					if($iFileSize < 1) {
						$iFileSize = 1;
					}
				}
				$cnv_filename = &ConvertToXmlAttribute($sFile);
				$sFiles	.= '<File name="' . $cnv_filename . '" size="' . $iFileSize . '" />' ;
			}
		}
	}
	print $sFolders ;
	print '</Folders>';			# Close the "Folders" node.
	print $sFiles ;
	print '</Files>';			# Close the "Files" node.
}

sub CreateFolder
{

	local($resourceType, $currentFolder) = @_;
	$sErrorNumber	= '0' ;
	$sErrorMsg		= '' ;

	if($FORM{'NewFolderName'} ne "") {
		$sNewFolderName = $FORM{'NewFolderName'};
		# Map the virtual path to the local server path of the current folder.
		$sServerDir = &ServerMapFolder($resourceType, $currentFolder);
		if(-w $sServerDir) {
			$sServerDir .= $sNewFolderName;
			$sErrorMsg = &CreateServerFolder($sServerDir);
			if($sErrorMsg == 0) {
				$sErrorNumber = '0';
			} elsif($sErrorMsg eq 'Invalid argument' || $sErrorMsg eq 'No such file or directory') {
				$sErrorNumber = '102';		#// Path too long.
			} else {
				$sErrorNumber = '110';
			}
		} else {
			$sErrorNumber = '103';
		}
	} else {
		$sErrorNumber = '102' ;
	}
	# Create the "Error" node.
	$cnv_errmsg = &ConvertToXmlAttribute($sErrorMsg);
	print '<Error number="' . $sErrorNumber . '" originalDescription="' . $cnv_errmsg . '" />';
}

sub FileUpload
{
eval("use File::Copy;");

	local($resourceType, $currentFolder) = @_;

	$sErrorNumber = '0' ;
	$sFileName = '' ;
	if($new_fname) {
		# Map the virtual path to the local server path.
		$sServerDir = &ServerMapFolder($resourceType,$currentFolder);

		# Get the uploaded file name.
		$sFileName = $new_fname;
		$sOriginalFileName = $sFileName;

		$iCounter = 0;
		while(1) {
			$sFilePath = $sServerDir . $sFileName;
			if(-e $sFilePath) {
				$iCounter++ ;
				($path,$BaseName,$ext) = &RemoveExtension($sOriginalFileName);
				$sFileName = $BaseName . '(' . $iCounter . ').' . $ext;
				$sErrorNumber = '201';
			} else {
				copy("$img_dir/$new_fname","$sFilePath");
				chmod(0777,$sFilePath);
				unlink("$img_dir/$new_fname");
				last;
			}
		}
	} else {
		$sErrorNumber = '202' ;
	}
	$sFileName	=~ s/"/\\"/g;
	print "Content-type: text/html\n\n";
	print '<script type="text/javascript">';
	print 'window.parent.frames["frmUpload"].OnUploadCompleted(' . $sErrorNumber . ',"' . $sFileName . '") ;';
	print '</script>';
	exit ;
}
1;
