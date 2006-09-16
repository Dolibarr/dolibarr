#!/usr/bin/env python

"""
FCKeditor - The text editor for internet
Copyright (C) 2003-2006 Frederico Caldeira Knabben

Licensed under the terms of the GNU Lesser General Public License:
		http://www.opensource.org/licenses/lgpl-license.php

For further information visit:
		http://www.fckeditor.net/

"Support Open Source software. What about a donation today?"

File Name: connector.py
	Connector for Python.
	
	Tested With:
	Standard:
		Python 2.3.3
	Zope:
		Zope Version: (Zope 2.8.1-final, python 2.3.5, linux2)
		Python Version: 2.3.5 (#4, Mar 10 2005, 01:40:25) 
			[GCC 3.3.3 20040412 (Red Hat Linux 3.3.3-7)]
		System Platform: linux2 

File Authors:
		Andrew Liu (andrew@liuholdings.com)
"""

"""
Author Notes (04 December 2005):
This module has gone through quite a few phases of change.  Obviously,
I am only supporting that part of the code that I use.  Initially
I had the upload directory as a part of zope (ie. uploading files
directly into Zope), before realising that there were too many 
complex intricacies within Zope to deal with.  Zope is one ugly piece
of code.  So I decided to complement Zope by an Apache server (which
I had running anyway, and doing nothing).  So I mapped all uploads
from an arbitrary server directory to an arbitrary web directory.
All the FCKeditor uploading occurred this way, and I didn't have to
stuff around with fiddling with Zope objects and the like (which are
terribly complex and something you don't want to do - trust me).

Maybe a Zope expert can touch up the Zope components.  In the end, 
I had FCKeditor loaded in Zope (probably a bad idea as well), and
I replaced the connector.py with an alias to a server module.
Right now, all Zope components will simple remain as is because
I've had enough of Zope.

See notes right at the end of this file for how I aliased out of Zope.

Anyway, most of you probably wont use Zope, so things are pretty
simple in that regard.

Typically, SERVER_DIR is the root of WEB_DIR (not necessarily).
Most definitely, SERVER_USERFILES_DIR points to WEB_USERFILES_DIR.
"""

import cgi
import re
import os
import string

"""
escape

Converts the special characters '<', '>', and '&'.

RFC 1866 specifies that these characters be represented
in HTML as &lt; &gt; and &amp; respectively. In Python
1.5 we use the new string.replace() function for speed.
"""
def escape(text, replace=string.replace):
    text = replace(text, '&', '&amp;') # must be done 1st
    text = replace(text, '<', '&lt;')
    text = replace(text, '>', '&gt;')
    text = replace(text, '"', '&quot;')
    return text

"""
getFCKeditorConnector

Creates a new instance of an FCKeditorConnector, and runs it
"""
def getFCKeditorConnector(context=None):
	# Called from Zope.  Passes the context through
	connector = FCKeditorConnector(context=context)
	return connector.run()


"""
FCKeditorRequest

A wrapper around the request object
Can handle normal CGI request, or a Zope request
Extend as required
"""
class FCKeditorRequest(object):
	def __init__(self, context=None):
		if (context is not None):
			r = context.REQUEST
		else:
			r = cgi.FieldStorage()
		self.context = context
		self.request = r

	def isZope(self):
		if (self.context is not None):
			return True
		return False

	def has_key(self, key):
		return self.request.has_key(key)

	def get(self, key, default=None):
		value = None
		if (self.isZope()):
			value = self.request.get(key, default)
		else:
			if key in self.request.keys():
				value = self.request[key].value
			else:
				value = default
		return value

"""
FCKeditorConnector

The connector class
"""
class FCKeditorConnector(object):
	# Configuration for FCKEditor
	# can point to another server here, if linked correctly
	#WEB_HOST = "http://127.0.0.1/" 
	WEB_HOST = ""
	SERVER_DIR = "/var/www/html/"

	WEB_USERFILES_FOLDER = WEB_HOST + "upload/"
	SERVER_USERFILES_FOLDER = SERVER_DIR + "upload/"

	# Allow access (Zope)
	__allow_access_to_unprotected_subobjects__ = 1
	# Class Attributes
	parentFolderRe = re.compile("[\/][^\/]+[\/]?$")

	"""
	Constructor
	"""
	def __init__(self, context=None):
		# The given root path will NOT be shown to the user
		# Only the userFilesPath will be shown

		# Instance Attributes
		self.context = context
		self.request = FCKeditorRequest(context=context)
		self.rootPath = self.SERVER_DIR
		self.userFilesFolder = self.SERVER_USERFILES_FOLDER
		self.webUserFilesFolder = self.WEB_USERFILES_FOLDER

		# Enables / Disables the connector
		self.enabled = False # Set to True to enable this connector

		# These are instance variables
		self.zopeRootContext = None
		self.zopeUploadContext = None

		# Copied from php module =)
		self.allowedExtensions = {
				"File": None,
				"Image": None,
				"Flash": None,
				"Media": None
				}
		self.deniedExtensions = {
				"File": [ "php","php2","php3","php4","php5","phtml","pwml","inc","asp","aspx","ascx","jsp","cfm","cfc","pl","bat","exe","com","dll","vbs","js","reg","cgi" ],
				"Image": [ "php","php2","php3","php4","php5","phtml","pwml","inc","asp","aspx","ascx","jsp","cfm","cfc","pl","bat","exe","com","dll","vbs","js","reg","cgi" ],
				"Flash": [ "php","php2","php3","php4","php5","phtml","pwml","inc","asp","aspx","ascx","jsp","cfm","cfc","pl","bat","exe","com","dll","vbs","js","reg","cgi" ],
				"Media": [ "php","php2","php3","php4","php5","phtml","pwml","inc","asp","aspx","ascx","jsp","cfm","cfc","pl","bat","exe","com","dll","vbs","js","reg","cgi" ]
				}

	"""
	Zope specific functions
	"""
	def isZope(self):
		# The context object is the zope object
		if (self.context is not None):
			return True
		return False

	def getZopeRootContext(self):
		if self.zopeRootContext is None:
			self.zopeRootContext = self.context.getPhysicalRoot()
		return self.zopeRootContext

	def getZopeUploadContext(self):
		if self.zopeUploadContext is None:
			folderNames = self.userFilesFolder.split("/")
			c = self.getZopeRootContext()
			for folderName in folderNames:
				if (folderName <> ""):
					c = c[folderName]
			self.zopeUploadContext = c
		return self.zopeUploadContext

	"""
	Generic manipulation functions
	"""
	def getUserFilesFolder(self):
		return self.userFilesFolder

	def getWebUserFilesFolder(self):
		return self.webUserFilesFolder

	def getAllowedExtensions(self, resourceType):
		return self.allowedExtensions[resourceType]

	def getDeniedExtensions(self, resourceType):
		return self.deniedExtensions[resourceType]

	def removeFromStart(self, string, char):
		return string.lstrip(char)

	def removeFromEnd(self, string, char):
		return string.rstrip(char)

	def convertToXmlAttribute(self, value):
		if (value is None):
			value = ""
		return escape(value)

	def convertToPath(self, path):
		if (path[-1] <> "/"):
			return path + "/"
		else:
			return path

	def getUrlFromPath(self, resourceType, path):
		if (resourceType is None) or (resourceType == ''):
			url = "%s%s" % (
					self.removeFromEnd(self.getUserFilesFolder(), '/'),
					path
					)
		else:
			url = "%s%s%s" % (
					self.getUserFilesFolder(),
					resourceType,
					path
					)
		return url

	def getWebUrlFromPath(self, resourceType, path):
		if (resourceType is None) or (resourceType == ''):
			url = "%s%s" % (
					self.removeFromEnd(self.getWebUserFilesFolder(), '/'),
					path
					)
		else:
			url = "%s%s%s" % (
					self.getWebUserFilesFolder(),
					resourceType,
					path
					)
		return url

	def removeExtension(self, fileName):
		index = fileName.rindex(".")
		newFileName = fileName[0:index]
		return newFileName

	def getExtension(self, fileName):
		index = fileName.rindex(".") + 1
		fileExtension = fileName[index:]
		return fileExtension
		
	def getParentFolder(self, folderPath):
		parentFolderPath = self.parentFolderRe.sub('', folderPath)
		return parentFolderPath
	
	"""
	serverMapFolder

	Purpose: works out the folder map on the server
	"""
	def serverMapFolder(self, resourceType, folderPath):
		# Get the resource type directory
		resourceTypeFolder = "%s%s/" % (
				self.getUserFilesFolder(),
				resourceType
				)
		# Ensure that the directory exists
		self.createServerFolder(resourceTypeFolder)

		# Return the resource type directory combined with the
		# required path
		return "%s%s" % (
				resourceTypeFolder,
				self.removeFromStart(folderPath, '/')
				)

	"""
	createServerFolder

	Purpose: physically creates a folder on the server
	"""
	def createServerFolder(self, folderPath):
		# Check if the parent exists
		parentFolderPath = self.getParentFolder(folderPath)
		if not(os.path.exists(parentFolderPath)):
			errorMsg = self.createServerFolder(parentFolderPath)
			if errorMsg is not None:
				return errorMsg
		# Check if this exists
		if not(os.path.exists(folderPath)):
			os.mkdir(folderPath)
			os.chmod(folderPath, 0755)
			errorMsg = None
		else:
			if os.path.isdir(folderPath):
				errorMsg = None
			else:
				raise "createServerFolder: Non-folder of same name already exists"
		return errorMsg


	"""
	getRootPath

	Purpose: returns the root path on the server
	"""
	def getRootPath(self):
		return self.rootPath
		
	"""
	setXmlHeaders

	Purpose: to prepare the headers for the xml to return
	"""
	def setXmlHeaders(self):
		#now = self.context.BS_get_now()
		#yesterday = now - 1
		self.setHeader("Content-Type", "text/xml")
		#self.setHeader("Expires", yesterday)
		#self.setHeader("Last-Modified", now)
		#self.setHeader("Cache-Control", "no-store, no-cache, must-revalidate")
		self.printHeaders()
		return

	def setHeader(self, key, value):
		if (self.isZope()):
			self.context.REQUEST.RESPONSE.setHeader(key, value)
		else:
			print "%s: %s" % (key, value)
		return

	def printHeaders(self):
		# For non-Zope requests, we need to print an empty line
		# to denote the end of headers
		if (not(self.isZope())):
			print ""

	"""
	createXmlFooter

	Purpose: returns the xml header
	"""
	def createXmlHeader(self, command, resourceType, currentFolder):
		self.setXmlHeaders()
		s = ""
		# Create the XML document header
		s += """<?xml version="1.0" encoding="utf-8" ?>"""
		# Create the main connector node
		s += """<Connector command="%s" resourceType="%s">""" % (
				command,
				resourceType
				)
		# Add the current folder node
		s += """<CurrentFolder path="%s" url="%s" />""" % (
				self.convertToXmlAttribute(currentFolder),
				self.convertToXmlAttribute(
					self.getWebUrlFromPath(
						resourceType, 
						currentFolder
						)
					),
				)
		return s

	"""
	createXmlFooter

	Purpose: returns the xml footer
	"""
	def createXmlFooter(self):
		s = """</Connector>"""
		return s

	"""
	sendError

	Purpose: in the event of an error, return an xml based error
	"""
	def sendError(self, number, text):
		self.setXmlHeaders()
		s = ""
		# Create the XML document header
		s += """<?xml version="1.0" encoding="utf-8" ?>"""
		s += """<Connector>"""
		s += """<Error number="%s" text="%s" />""" % (number, text)
		s += """</Connector>"""
		return s

	"""
	getFolders

	Purpose: command to recieve a list of folders
	"""
	def getFolders(self, resourceType, currentFolder):
		if (self.isZope()):
			return self.getZopeFolders(resourceType, currentFolder)
		else:
			return self.getNonZopeFolders(resourceType, currentFolder)

	def getZopeFolders(self, resourceType, currentFolder):
		# Open the folders node
		s = ""
		s += """<Folders>"""
		zopeFolder = self.findZopeFolder(resourceType, currentFolder)
		for (name, o) in zopeFolder.objectItems(["Folder"]):
			s += """<Folder name="%s" />""" % (
					self.convertToXmlAttribute(name)
					)
		# Close the folders node
		s += """</Folders>"""
		return s

	def getNonZopeFolders(self, resourceType, currentFolder):
		# Map the virtual path to our local server
		serverPath = self.serverMapFolder(resourceType, currentFolder)
		# Open the folders node
		s = ""
		s += """<Folders>"""
		for someObject in os.listdir(serverPath):
			someObjectPath = os.path.join(serverPath, someObject)
			if os.path.isdir(someObjectPath):
				s += """<Folder name="%s" />""" % (
						self.convertToXmlAttribute(someObject)
						)
		# Close the folders node
		s += """</Folders>"""
		return s
		
	"""
	getFoldersAndFiles

	Purpose: command to recieve a list of folders and files
	"""
	def getFoldersAndFiles(self, resourceType, currentFolder):
		if (self.isZope()):
			return self.getZopeFoldersAndFiles(resourceType, currentFolder)
		else:
			return self.getNonZopeFoldersAndFiles(resourceType, currentFolder)

	def getNonZopeFoldersAndFiles(self, resourceType, currentFolder):
		# Map the virtual path to our local server
		serverPath = self.serverMapFolder(resourceType, currentFolder)
		# Open the folders / files node
		folders = """<Folders>"""
		files = """<Files>"""
		for someObject in os.listdir(serverPath):
			someObjectPath = os.path.join(serverPath, someObject)
			if os.path.isdir(someObjectPath):
				folders += """<Folder name="%s" />""" % (
						self.convertToXmlAttribute(someObject)
						)
			elif os.path.isfile(someObjectPath):
				size = os.path.getsize(someObjectPath)
				files += """<File name="%s" size="%s" />""" % (
						self.convertToXmlAttribute(someObject),
						os.path.getsize(someObjectPath)
						)
		# Close the folders / files node
		folders += """</Folders>"""
		files += """</Files>"""
		# Return it
		s = folders + files
		return s

	def getZopeFoldersAndFiles(self, resourceType, currentFolder):
		folders = self.getZopeFolders(resourceType, currentFolder)
		files = self.getZopeFiles(resourceType, currentFolder)
		s = folders + files
		return s

	def getZopeFiles(self, resourceType, currentFolder):
		# Open the files node
		s = ""
		s += """<Files>"""
		zopeFolder = self.findZopeFolder(resourceType, currentFolder)
		for (name, o) in zopeFolder.objectItems(["File","Image"]):
			s += """<File name="%s" size="%s" />""" % (
					self.convertToXmlAttribute(name),
					((o.get_size() / 1024) + 1)
					)
		# Close the files node
		s += """</Files>"""
		return s
		
	def findZopeFolder(self, resourceType, folderName):
		# returns the context of the resource / folder
		zopeFolder = self.getZopeUploadContext()
		folderName = self.removeFromStart(folderName, "/")
		folderName = self.removeFromEnd(folderName, "/")
		if (resourceType <> ""):
			try:
				zopeFolder = zopeFolder[resourceType]
			except:
				zopeFolder.manage_addProduct["OFSP"].manage_addFolder(id=resourceType, title=resourceType)
				zopeFolder = zopeFolder[resourceType]
		if (folderName <> ""):
			folderNames = folderName.split("/")
			for folderName in folderNames:
				zopeFolder = zopeFolder[folderName]
		return zopeFolder

	"""
	createFolder

	Purpose: command to create a new folder
	"""
	def createFolder(self, resourceType, currentFolder):
		if (self.isZope()):
			return self.createZopeFolder(resourceType, currentFolder)
		else:
			return self.createNonZopeFolder(resourceType, currentFolder)

	def createZopeFolder(self, resourceType, currentFolder):
		# Find out where we are
		zopeFolder = self.findZopeFolder(resourceType, currentFolder)
		errorNo = 0
		errorMsg = ""
		if self.request.has_key("NewFolderName"):
			newFolder = self.request.get("NewFolderName", None)
			zopeFolder.manage_addProduct["OFSP"].manage_addFolder(id=newFolder, title=newFolder)
		else:
			errorNo = 102
		error = """<Error number="%s" originalDescription="%s" />""" % (
				errorNo,
				self.convertToXmlAttribute(errorMsg)
				)
		return error

	def createNonZopeFolder(self, resourceType, currentFolder):
		errorNo = 0
		errorMsg = ""
		if self.request.has_key("NewFolderName"):
			newFolder = self.request.get("NewFolderName", None)
			currentFolderPath = self.serverMapFolder(
					resourceType, 
					currentFolder
					)
			try:
				newFolderPath = currentFolderPath + newFolder
				errorMsg = self.createServerFolder(newFolderPath)
				if (errorMsg is not None):
					errorNo = 110
			except:
				errorNo = 103
		else:
			errorNo = 102
		error = """<Error number="%s" originalDescription="%s" />""" % (
				errorNo,
				self.convertToXmlAttribute(errorMsg)
				)
		return error

	"""
	getFileName

	Purpose: helper function to extrapolate the filename
	"""
	def getFileName(self, filename):
		for splitChar in ["/", "\\"]:
			array = filename.split(splitChar)
			if (len(array) > 1):
				filename = array[-1]
		return filename

	"""
	fileUpload

	Purpose: command to upload files to server
	"""
	def fileUpload(self, resourceType, currentFolder):
		if (self.isZope()):
			return self.zopeFileUpload(resourceType, currentFolder)
		else:
			return self.nonZopeFileUpload(resourceType, currentFolder)

	def zopeFileUpload(self, resourceType, currentFolder, count=None):
		zopeFolder = self.findZopeFolder(resourceType, currentFolder)
		file = self.request.get("NewFile", None)
		fileName = self.getFileName(file.filename)
		fileNameOnly = self.removeExtension(fileName)
		fileExtension = self.getExtension(fileName).lower()
		if (count):
			nid = "%s.%s.%s" % (fileNameOnly, count, fileExtension)
		else:
			nid = fileName
		title = nid
		try:
			zopeFolder.manage_addProduct['OFSP'].manage_addFile(
					id=nid,
					title=title,
					file=file.read()
					)
		except:
			if (count):
				count += 1
			else:
				count = 1
			self.zopeFileUpload(resourceType, currentFolder, count)
		return
		
	def nonZopeFileUpload(self, resourceType, currentFolder):
		errorNo = 0
		errorMsg = ""
		if self.request.has_key("NewFile"):
			# newFile has all the contents we need
			newFile = self.request.get("NewFile", "")
			# Get the file name
			newFileName = newFile.filename
			newFileNameOnly = self.removeExtension(newFileName)
			newFileExtension = self.getExtension(newFileName).lower()
			allowedExtensions = self.getAllowedExtensions(resourceType)
			deniedExtensions = self.getDeniedExtensions(resourceType)
			if (allowedExtensions is not None):
				# Check for allowed
				isAllowed = False
				if (newFileExtension in allowedExtensions):
					isAllowed = True
			elif (deniedExtensions is not None):
				# Check for denied
				isAllowed = True
				if (newFileExtension in deniedExtensions):
					isAllowed = False
			else:
				# No extension limitations
				isAllowed = True

			if (isAllowed):
				if (self.isZope()):
					# Upload into zope
					self.zopeFileUpload(resourceType, currentFolder)
				else:
					# Upload to operating system
					# Map the virtual path to the local server path
					currentFolderPath = self.serverMapFolder(
							resourceType, 
							currentFolder
							)
					i = 0
					while (True):
						newFilePath = "%s%s" % (
								currentFolderPath,
								newFileName
								)
						if os.path.exists(newFilePath):
							i += 1
							newFilePath = "%s%s(%s).%s" % (
									currentFolderPath,
									newFileNameOnly,
									i,
									newFileExtension
									)
							errorNo = 201
							break
						else:
							fileHandle = open(newFilePath,'w')
							linecount = 0
							while (1):
								#line = newFile.file.readline()
								line = newFile.readline()
								if not line: break
								fileHandle.write("%s" % line)
								linecount += 1
							os.chmod(newFilePath, 0777)
							break
			else:
				newFileName = "Extension not allowed"
				errorNo = 203
		else:
			newFileName = "No File"
			errorNo = 202
	
		string = """
<script type="text/javascript">
window.parent.frames["frmUpload"].OnUploadCompleted(%s,"%s");
</script>
				""" % (
						errorNo,
						newFileName.replace('"',"'")
						)
		return string

	def run(self):
		s = ""
		try:
			# Check if this is disabled
			if not(self.enabled):
				return self.sendError(1, "This connector is disabled.  Please check the connector configurations and try again")
			# Make sure we have valid inputs
			if not(
					(self.request.has_key("Command")) and 
					(self.request.has_key("Type")) and 
					(self.request.has_key("CurrentFolder"))
					):
				return 
			# Get command
			command = self.request.get("Command", None)
			# Get resource type
			resourceType = self.request.get("Type", None)
			# folder syntax must start and end with "/"
			currentFolder = self.request.get("CurrentFolder", None)
			if (currentFolder[-1] <> "/"):
				currentFolder += "/"
			if (currentFolder[0] <> "/"):
				currentFolder = "/" + currentFolder
			# Check for invalid paths
			if (".." in currentFolder):
				return self.sendError(102, "")
			# File upload doesn't have to return XML, so intercept
			# her:e
			if (command == "FileUpload"):
				return self.fileUpload(resourceType, currentFolder)
			# Begin XML
			s += self.createXmlHeader(command, resourceType, currentFolder)
			# Execute the command
			if (command == "GetFolders"):
				f = self.getFolders
			elif (command == "GetFoldersAndFiles"):
				f = self.getFoldersAndFiles
			elif (command == "CreateFolder"):
				f = self.createFolder
			else:
				f = None
			if (f is not None):
				s += f(resourceType, currentFolder)
			s += self.createXmlFooter()
		except Exception, e:
			s = "ERROR: %s" % e
		return s
			
# Running from command line
if __name__ == '__main__':
	# To test the output, uncomment the standard headers
	#print "Content-Type: text/html"
	#print ""
	print getFCKeditorConnector()

"""
Running from zope, you will need to modify this connector. 
If you have uploaded the FCKeditor into Zope (like me), you need to 
move this connector out of Zope, and replace the "connector" with an
alias as below.  The key to it is to pass the Zope context in, as
we then have a like to the Zope context.

## Script (Python) "connector.py"
##bind container=container
##bind context=context
##bind namespace=
##bind script=script
##bind subpath=traverse_subpath
##parameters=*args, **kws
##title=ALIAS
##
import Products.connector as connector
return connector.getFCKeditorConnector(context=context).run()
"""
			
	
