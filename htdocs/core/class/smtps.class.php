<?php
/*
 * Copyright (C)           Walter Torres        <walter@torres.ws> [with a *lot* of help!]
 * Copyright (C) 2005-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006-2011 Regis Houssin
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/core/class/smtps.class.php
 *	\brief      Class to construct and send SMTP compliant email, even to a secure
 *              SMTP server, regardless of platform.
 * Goals:
 *  - mime compliant
 *  - multiple Reciptiants
 *    - TO
 *    - CC
 *    - BCC
 *  - multi-part message
 *    - plain text
 *    - HTML
 *    - inline attachments
 *    - attachments
 *  - GPG access
 * This Class is based off of 'SMTP PHP MAIL' by Dirk Paehl, http://www.paehl.de
 */


/**
 * 	Class to construct and send SMTP compliant email, even
 * 	to a secure SMTP server, regardless of platform.
 */
class SMTPs
{
	/**
	 * Host Name or IP of SMTP Server to use
	 */
	var $_smtpsHost = 'localhost';

	/**
	 * SMTP Server Port definition. 25 is default value
	 * This can be defined via a INI file or via a setter method
	 */
	var $_smtpsPort = '25';

	/**
	 * Secure SMTP Server access ID
	 * This can be defined via a INI file or via a setter method
	 */
	var $_smtpsID = null;

	/**
	 * Secure SMTP Server access Password
	 * This can be defined via a INI file or via a setter method
	 */
	var $_smtpsPW = null;

	/**
	 * Who sent the Message
	 * This can be defined via a INI file or via a setter method
	 */
	var $_msgFrom = null;

	/**
	 * Where are replies and errors to be sent to
	 * This can be defined via a INI file or via a setter method
	 */
	var $_msgReplyTo = null;

	/**
	 * Who will the Message be sent to; TO, CC, BCC
	 * Multi-diminsional array containg addresses the message will
	 * be sent TO, CC or BCC
	 */
	var $_msgRecipients = null;

	/**
	 * Message Subject
	 */
	var $_msgSubject = null;

	/**
	 * Message Content
	 */
	var $_msgContent = null;

	/**
	 * Custom X-Headers
	 */
	var $_msgXheader = null;

	/**
	 * Character set
	 * Defaulted to 'iso-8859-1'
	 */
	var $_smtpsCharSet = 'iso-8859-1';

	/**
	 * Message Sensitivity
	 * Defaults to ZERO - None
	 */
	var $_msgSensitivity = 0;

	/**
	 * Message Sensitivity
	 */
	var $_arySensitivity = array ( false,
                                  'Personal',
                                  'Private',
                                  'Company Confidential' );

	/**
	 * Message Sensitivity
	 * Defaults to 3 - Normal
	 */
	var $_msgPriority = 3;

	/**
	 * Message Priority
	 */
	var $_aryPriority = array ( 'Bulk',
                                'Highest',
                                'High',
                                'Normal',
                                'Low',
                                'Lowest' );

	/**
	 * Content-Transfer-Encoding
	 * Defaulted to 0 - 7bit
	 */
	var $_smtpsTransEncodeType = 0;

	/**
	 * Content-Transfer-Encoding
	 */
	var $_smtpsTransEncodeTypes = array( '7bit',               // Simple 7-bit ASCII
                                         '8bit',               // 8-bit coding with line termination characters
                                         'base64',             // 3 octets encoded into 4 sextets with offset
                                         'binary',             // Arbitrary binary stream
                                         'mac-binhex40',       // Macintosh binary to hex encoding
                                         'quoted-printable',   // Mostly 7-bit, with 8-bit characters encoded as "=HH"
                                         'uuencode' );         // UUENCODE encoding

	/**
	 * Content-Transfer-Encoding
	 * Defaulted to '7bit'
	 */
	var $_smtpsTransEncode = '7bit';

	/**
	 * Boundary String for MIME seperation
	 */
	var $_smtpsBoundary = null;

	/**
	 * Related Boundary
	 */
	var $_smtpsRelatedBoundary = null;

	/**
	 * Alternative Boundary
	 */
	var $_smtpsAlternativeBoundary = null;

	/**
	 * Determines the method inwhich the message are to be sent.
	 * - 'sockets' [0] - conect via network to SMTP server - default
	 * - 'pipe     [1] - use UNIX path to EXE
	 * - 'phpmail  [2] - use the PHP built-in mail function
	 * NOTE: Only 'sockets' is implemented
	 */
	var $_transportType = 0;

	/**
	 * If '$_transportType' is set to '1', then this variable is used
	 * to define the UNIX file system path to the sendmail execuable
	 */
	var $_mailPath = '/usr/lib/sendmail';

	/**
	 * Sets the SMTP server timeout in seconds.
	 */
	var $_smtpTimeout = 10;

	/**
	 * Determines whether to calculate message MD5 checksum.
	 */
	var $_smtpMD5 = false;

	/**
	 * Class error codes and messages
	 */
	var $_smtpsErrors = null;

	/**
	 * Defines log level
	 *  0 - no logging
	 *  1 - connectivity logging
	 *  2 - message generation logging
	 *  3 - detail logging
	 */
	var $_log_level = 0;

	/**
	 * Place Class in" debug" mode
	 */
	var $_debug = false;


	// DOL_CHANGE LDR
	var $log = '';
	var $_errorsTo = '';
	var $_deliveryReceipt = 0;



    /**
     * Set delivery receipt
     *
     * @param	int		$_val		Value
     * @return	void
     */
	function setDeliveryReceipt($_val = 0)
	{
		$this->_deliveryReceipt = $_val;
	}

    /**
     * get delivery receipt
     *
     * @return	int		Delivery receipt
     */
	function getDeliveryReceipt()
	{
		return $this->_deliveryReceipt;
	}

    /**
     * Set errors to
     *
     * @param	string		$_strErrorsTo		Errors to
     * @return	void
     */
	function setErrorsTo($_strErrorsTo)
	{
		if ( $_strErrorsTo )
		$this->_errorsTo = $this->_strip_email($_strErrorsTo);
	}

    /**
     * Get errors to
     *
     * @param	boolean		$_part		Variant
     * @return	string					Errors to
     */
	function getErrorsTo($_part = true )
	{
		$_retValue = '';

		if ( $_part === true )
		$_retValue = $this->_errorsTo;
		else
		$_retValue = $this->_errorsTo[$_part];

		return $_retValue;
	}

	/**
	 * Set debug
	 *
	 * @param	boolean		$_vDebug		Value for debug
	 * @return 	void
	 */
	function setDebug($_vDebug = false )
	{
		$this->_debug = $_vDebug;
	}

	/**
	 * build RECIPIENT List, all addresses who will recieve this message
	 *
	 * @return void
	 */
	function buildRCPTlist()
	{
		// Pull TO list
		$_aryToList = $this->getTO();
	}

	/**
	 * Attempt a connection to mail server
	 *
	 * @return mixed  $_retVal   Boolean indicating success or failure on connection
	 */
	function _server_connect()
	{
		// Default return value
		$_retVal = true;

		// We have to make sure the HOST given is valid
		// This is done here because '@fsockopen' will not give me this
		// information if it failes to connect because it can't find the HOST
		$host=$this->getHost();
		$host=preg_replace('@tcp://@i','',$host);	// Remove prefix
		$host=preg_replace('@ssl://@i','',$host);	// Remove prefix

		// DOL_CHANGE LDR
		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		if ( (! is_ip($host)) && ((gethostbyname($host)) == $host))
		{
			$this->_setErr(99, $host . ' is either offline or is an invalid host name.');
			$_retVal = false;
		}
		else
		{
			//See if we can connect to the SMTP server
			if ($this->socket = @fsockopen(
    			$this->getHost(),       // Host to 'hit', IP or domain
    			$this->getPort(),       // which Port number to use
    			$this->errno,           // actual system level error
    			$this->errstr,          // and any text that goes with the error
    			$this->_smtpTimeout
			))  // timeout for reading/writing data over the socket
			{
				// Fix from PHP SMTP class by 'Chris Ryan'
				// Sometimes the SMTP server takes a little longer to respond
				// so we will give it a longer timeout for the first read
				// Windows still does not have support for this timeout function
				if (function_exists('stream_set_timeout')) stream_set_timeout($this->socket, $this->_smtpTimeout, 0);

				// Check response from Server
				if ( $_retVal = $this->server_parse($this->socket, "220") )
				$_retVal = $this->socket;
			}
			// This connection attempt failed.
			else
			{
				// DOL_CHANGE LDR
				if (empty($this->errstr)) $this->errstr='Failed to connect with fsockopen host='.$this->getHost().' port='.$this->getPort();
				$this->_setErr($this->errno, $this->errstr);
				$_retVal = false;
			}
		}

		return $_retVal;
	}

	/**
	 * Attempt mail server authentication for a secure connection
	 *
	 * @return mixed  $_retVal   Boolean indicating success or failure of authentication
	 */
	function _server_authenticate()
	{
		// Send the RFC2554 specified EHLO.
		// This improvment as provided by 'SirSir' to
		// accomodate both SMTP AND ESMTP capable servers
		$host=$this->getHost();
		$host=preg_replace('@tcp://@i','',$host);	// Remove prefix
		$host=preg_replace('@ssl://@i','',$host);	// Remove prefix
		if ( $_retVal = $this->socket_send_str('EHLO ' . $host, '250') )
		{
			// Send Authentication to Server
			// Check for errors along the way
			$this->socket_send_str('AUTH LOGIN', '334');

			// User name will not return any error, server will take anything we give it.
			$this->socket_send_str(base64_encode($this->_smtpsID), '334');

			// The error here just means the ID/password combo doesn't work.
			// There is not a method to determine which is the problem, ID or password
			if ( ! $_retVal = $this->socket_send_str(base64_encode($this->_smtpsPW), '235') )
			$this->_setErr(130, 'Invalid Authentication Credentials.');
		}
		else
		{
			$this->_setErr(126, '"' . $host . '" does not support authenticated connections.');
		}

		return $_retVal;
	}

	/**
	 * Now send the message
	 *
	 * @param  boolean $_bolTestMsg  whether to run this method in 'Test' mode.
	 * @param  boolean $_bolDebug    whether to log all communication between this Class and the Mail Server.
	 * @return mixed   void
	 *                 $_strMsg      If this is run in 'Test' mode, the actual message structure will be returned
	 */
	function sendMsg($_bolTestMsg = false, $_bolDebug = false)
	{
		/**
		 * Default return value
		 */
		$_retVal = false;

		// Connect to Server
		if ( $this->socket = $this->_server_connect() )
		{
			// If a User ID *and* a password is given, assume Authentication is desired
			if( !empty($this->_smtpsID) && !empty($this->_smtpsPW) )
			{
				// Send the RFC2554 specified EHLO.
				$_retVal = $this->_server_authenticate();
			}

			// This is a "normal" SMTP Server "handshack"
			else
			{
				// Send the RFC821 specified HELO.
				$host=$this->getHost();
				$host=preg_replace('@tcp://@i','',$host);	// Remove prefix
				$host=preg_replace('@ssl://@i','',$host);	// Remove prefix
				$_retVal = $this->socket_send_str('HELO ' . $host, '250');
			}

			// Well, did we get to the server?
			if ( $_retVal )
			{
				// From this point onward most server response codes should be 250
				// Specify who the mail is from....
				// This has to be the raw email address, strip the "name" off
				$this->socket_send_str('MAIL FROM: ' . $this->getFrom('addr'), '250');

				// 'RCPT TO:' must be given a single address, so this has to loop
				// through the list of addresses, regardless of TO, CC or BCC
				// and send it out "single file"
				foreach ( $this->get_RCPT_list() as $_address )
				{
					/*
					 * TODO
					* After each 'RCPT TO:' is sent, we need to make sure it was kosher,
					* if not, the whole message will fail
					* If any email address fails, we will need to RESET the connection,
					* mark the last address as "bad" and start the address loop over again.
					* If any address fails, the entire message fails.
					*/
					$this->socket_send_str('RCPT TO: <' . $_address . '>', '250');
				}

				// Tell the server we are ready to start sending data
				// with any custom headers...
				// This is the last response code we look for until the end of the message.
				$this->socket_send_str('DATA', '354');

				// Now we are ready for the message...
				// Ok, all the ingredients are mixed in let's cook this puppy...
				$this->socket_send_str($this->getHeader().$this->getBodyContent() . "\r\n" . '.', '250');

				// Now tell the server we are done and close the socket...
				fputs($this->socket, 'QUIT');
				fclose($this->socket);
			}
		}

		return $_retVal;
	}

	// =============================================================
	// ** Setter & Getter methods

	// ** Basic System configuration

	/**
	 * setConfig() is used to populate select class properties from either
	 * a user defined INI file or the systems 'php.ini' file
	 *
	 * If a user defined INI is to be used, the files complete path is passed
	 * as the method single parameter. The INI can define any class and/or
	 * user properties. Only properties defined within this file will be setter
	 * and/or orverwritten
	 *
	 * If the systems 'php.ini' file is to be used, the method is called without
	 * parameters. In this case, only HOST, PORT and FROM properties will be set
	 * as they are the only properties that are defined within the 'php.ini'.
	 *
	 * If secure SMTP is to be used, the user ID and Password can be defined with
	 * the user INI file, but the properties are not defined with the systems
	 * 'php.ini'file, they must be defined via their setter methods
	 *
	 * This method can be called twice, if desired. Once without a parameter to
	 * load the properties as defined within the systems 'php.ini' file, and a
	 * second time, with a path to a user INI file for other properties to be
	 * defined.
	 *
	 * @param mixed $_strConfigPath path to config file or VOID
	 * @return void
	 */
	function setConfig ( $_strConfigPath = null )
	{
		/**
		 * Returns constructed SELECT Object string or boolean upon failure
		 * Default value is set at TRUE
		 */
		$_retVal = true;

		// if we have a path...
		if ( ! empty ($_strConfigPath) )
		{
			// If the path is not valid, this will NOT generate an error,
			// it will simply return FALSE.
			if ( ! @include ( $_strConfigPath ) )
			{
				$this->_setErr(110, '"' . $_strConfigPath . '" is not a valid path.');
				$_retVal = false;
			}
		}

		// Read the Systems php.ini file
		else
		{
			// Set these properties ONLY if they are set in the php.ini file.
			// Otherwise the default values will be used.
			if ( $_host = ini_get('SMTPs') )
			$this->setHost($_host);

			if ( $_port = ini_get('smtp_port') )
			$this->setPort($_port);

			if ( $_from = ini_get('sendmail_from') )
			$this->setFrom($_from);
		}

		// Send back what we have
		return $_retVal;
	}

	/**
	 * Determines the method inwhich the messages are to be sent.
	 * - 'sockets' [0] - conect via network to SMTP server
	 * - 'pipe     [1] - use UNIX path to EXE
	 * - 'phpmail  [2] - use the PHP built-in mail function
	 *
	 * @param int $_type  Interger value representing Mail Transport Type
	 * @return void
	 */
	function setTransportType($_type = 0)
	{
		if ( ( is_numeric($_type) ) &&
		( ( $_type >= 0 ) && ( $_type <= 3 ) ) )
		$this->_transportType = $_type;
	}

	/**
	 * Return the method inwhich the message is to be sent.
	 * - 'sockets' [0] - conect via network to SMTP server
	 * - 'pipe     [1] - use UNIX path to EXE
	 * - 'phpmail  [2] - use the PHP built-in mail function
	 *
	 * @return int $_strHost Host Name or IP of the Mail Server to use
	 */
	function getTransportType()
	{
		return $this->_transportType;
	}

	/**
	 * Path to the sendmail execuable
	 *
	 * @param string $_path Path to the sendmail execuable
	 * @return void
	 *
	 */
	function setMailPath($_path)
	{
		// This feature is not yet implemented
		return true;

		//if ( $_path ) $this->_mailPath = $_path;
	}

	/**
	 * Defines the Host Name or IP of the Mail Server to use.
	 * This is defaulted to 'localhost'
	 * This is  used only with 'socket' based mail transmission
	 *
	 * @param 	string 	$_strHost 		Host Name or IP of the Mail Server to use
	 * @return 	void
	 */
	function setHost($_strHost)
	{
		if ( $_strHost )
		$this->_smtpsHost = $_strHost;
	}

	/**
	 * Retrieves the Host Name or IP of the Mail Server to use
	 * This is  used only with 'socket' based mail transmission
	 *
	 * @return 	string 	$_strHost 		Host Name or IP of the Mail Server to use
	 */
	function getHost()
	{
		return $this->_smtpsHost;
	}

	/**
	 * Defines the Port Number of the Mail Server to use
	 * This is defaulted to '25'
	 * This is  used only with 'socket' based mail transmission
	 *
	 * @param 	int 	$_intPort 		Port Number of the Mail Server to use
	 * @return 	void
	 */
	function setPort($_intPort)
	{
		if ( ( is_numeric($_intPort) ) &&
		( ( $_intPort >= 1 ) && ( $_intPort <= 65536 ) ) )
		$this->_smtpsPort = $_intPort;
	}

	/**
	 * Retrieves the Port Number of the Mail Server to use
	 * This is  used only with 'socket' based mail transmission
	 *
	 * @return 	string 		Port Number of the Mail Server to use
	 */
	function getPort()
	{
		return $this->_smtpsPort;
	}

	/**
	 * User Name for authentication on Mail Server
	 *
	 * @param 	string 	$_strID 	User Name for authentication on Mail Server
	 * @return 	void
	 */
	function setID($_strID)
	{
		$this->_smtpsID = $_strID;
	}

	/**
	 * Retrieves the User Name for authentication on Mail Server
	 *
	 * @return string 	User Name for authentication on Mail Server
	 */
	function getID()
	{
		return $this->_smtpsID;
	}

	/**
	 * User Password for authentication on Mail Server
	 *
	 * @param 	string 	$_strPW 	User Password for authentication on Mail Server
	 * @return 	void
	 */
	function setPW($_strPW)
	{
		$this->_smtpsPW = $_strPW;
	}

	/**
	 * Retrieves the User Password for authentication on Mail Server
	 *
	 * @return 	string 		User Password for authentication on Mail Server
	 */
	function getPW()
	{
		return $this->_smtpsPW;
	}

	/**
	 * Character set used for current message
	 * Character set is defaulted to 'iso-8859-1';
	 *
	 * @param string $_strCharSet Character set used for current message
	 * @return void
	 */
	function setCharSet($_strCharSet)
	{
		if ( $_strCharSet )
		$this->_smtpsCharSet = $_strCharSet;
	}

	/**
	 * Retrieves the Character set used for current message
	 *
	 * @return string $_smtpsCharSet Character set used for current message
	 */
	function getCharSet()
	{
		return $this->_smtpsCharSet;
	}

	/**
	 * Content-Transfer-Encoding, Defaulted to '7bit'
	 * This can be changed for 2byte characers sets
	 * Known Encode Types
	 *  - 7bit               Simple 7-bit ASCII
	 *  - 8bit               8-bit coding with line termination characters
	 *  - base64             3 octets encoded into 4 sextets with offset
	 *  - binary             Arbitrary binary stream
	 *  - mac-binhex40       Macintosh binary to hex encoding
	 *  - quoted-printable   Mostly 7-bit, with 8-bit characters encoded as "=HH"
	 *  - uuencode           UUENCODE encoding
	 *
	 * @param string $_strTransEncode Content-Transfer-Encoding
	 * @return void
	 */
	function setTransEncode ( $_strTransEncode )
	{
		if (array_search($_strTransEncode, $this->_smtpsTransEncodeTypes))
		$this->_smtpsTransEncode = $_strTransEncode;
	}

	/**
	 * Retrieves the Content-Transfer-Encoding
	 *
	 * @return string $_smtpsTransEncode Content-Transfer-Encoding
	 */
	function getTransEncode()
	{
		return $this->_smtpsTransEncode;
	}

	/**
	 * Content-Transfer-Encoding, Defaulted to '0' [ZERO]
	 * This can be changed for 2byte characers sets
	 * Known Encode Types
	 *  - [0] 7bit               Simple 7-bit ASCII
	 *  - [1] 8bit               8-bit coding with line termination characters
	 *  - [2] base64             3 octets encoded into 4 sextets with offset
	 *  - [3] binary             Arbitrary binary stream
	 *  - [4] mac-binhex40       Macintosh binary to hex encoding
	 *  - [5] quoted-printable   Mostly 7-bit, with 8-bit characters encoded as "=HH"
	 *  - [6] uuencode           UUENCODE encoding
	 *
	 * @param string $_strTransEncodeType Content-Transfer-Encoding
	 * @return void
	 *
	 */
	function setTransEncodeType($_strTransEncodeType)
	{
		if (array_search($_strTransEncodeType, $this->_smtpsTransEncodeTypes))
		$this->_smtpsTransEncodeType = $_strTransEncodeType;
	}

	/**
	 * Retrieves the Content-Transfer-Encoding
	 *
	 * @return 	string 		Content-Transfer-Encoding
	 */
	function getTransEncodeType()
	{
		return $this->_smtpsTransEncodeTypes[$this->_smtpsTransEncodeType];
	}


	// ** Message Construction

	/**
	 * FROM Address from which mail will be sent
	 *
	 * @param 	string 	$_strFrom 	Address from which mail will be sent
	 * @return 	void
	 */
	function setFrom($_strFrom)
	{
		if ( $_strFrom )
		$this->_msgFrom = $this->_strip_email($_strFrom);
	}

	/**
	 * Retrieves the Address from which mail will be sent
	 *
	 * @param  	boolean $_part		To "strip" 'Real name' from address
	 * @return 	string 				Address from which mail will be sent
	 */
	function getFrom($_part = true)
	{
		$_retValue = '';

		if ( $_part === true )
		$_retValue = $this->_msgFrom;
		else
		$_retValue = $this->_msgFrom[$_part];

		return $_retValue;
	}


	/**
	 * Inserts given addresses into structured format.
	 * This method takes a list of given addresses, via an array
	 * or a COMMA delimted string, and inserts them into a highly
	 * structured array. This array is designed to remove duplicate
	 * addresses and to sort them by Domain.
	 *
	 * @param 	string 	$_type 			TO, CC, or BCC lists to add addrresses into
	 * @param 	mixed 	$_addrList 		Array or COMMA delimited string of addresses
	 * @return void
	 *
	 */
	function _buildAddrList( $_type, $_addrList )
	{
		// Pull existing list
		$aryHost = $this->_msgRecipients;

		// Only run this if we have something
		if ( !empty ($_addrList ))
		{
			// $_addrList can be a STRING or an array
			if ( is_string($_addrList) )
			{
				// This could be a COMMA delimited string
				if ( strstr($_addrList, ',') )
				// "explode "list" into an array
				$_addrList = explode(',', $_addrList);

				// Stick it in an array
				else
				$_addrList = array($_addrList);
			}

			// take the array of addresses and split them further
			foreach ( $_addrList as $_strAddr )
			{
				// Strip off the end '>'
				$_strAddr = str_replace('>', '', $_strAddr);

				// Seperate "Real Name" from eMail address
				$_tmpaddr = null;
				$_tmpaddr = explode('<', $_strAddr);

				// We have a "Real Name" and eMail address
				if ( count($_tmpaddr) == 2 )
				{
					$_tmpHost = explode('@', $_tmpaddr[1]);
					$_tmpaddr[0] = trim($_tmpaddr[0], ' ">');
					$aryHost[$_tmpHost[1]][$_type][$_tmpHost[0]] = $_tmpaddr[0];
				}
				// We only have an eMail address
				else
				{
					// Strip off the beggining '<'
					$_strAddr = str_replace('<', '', $_strAddr);

					$_tmpHost = explode('@', $_strAddr);
					$_tmpHost[0] = trim($_tmpHost[0]);
					$_tmpHost[1] = trim($_tmpHost[1]);

					$aryHost[$_tmpHost[1]][$_type][$_tmpHost[0]] = '';
				}
			}
		}
		// replace list
		$this->_msgRecipients = $aryHost;
	}

	/**
	 * Returns an array of the various parts of an email address
	 * This assumes a well formed address:
	 * - "Real name" <username@domain.tld>
	 * - "Real Name" is optional
	 * - if "Real Name" does not exist, the angle brackets are optional
	 * This will split an email address into 4 or 5 parts.
	 * - $_aryEmail[org]  = orignal string
	 * - $_aryEmail[real] = "real name" - if there is one
	 * - $_aryEmail[addr] = address part "username@domain.tld"
	 * - $_aryEmail[host] = "domain.tld"
	 * - $_aryEmail[user] = "userName"
	 *
	 *	@param		string		$_strAddr		Email address
	 * 	@return 	array	 					An array of the various parts of an email address
	 */
	function _strip_email($_strAddr)
	{
		// Keep the orginal
		$_aryEmail['org'] = $_strAddr;

		// Set entire string to Lower Case
		$_strAddr = strtolower($_strAddr);

		// Drop "stuff' off the end
		$_strAddr = trim($_strAddr, ' ">');

		// Seperate "Real Name" from eMail address, if we have one
		$_tmpAry = explode('<', $_strAddr);

		// Do we have a "Real name"
		if ( count($_tmpAry) == 2 )
		{
			// We may not really have a "Real Name"
			if ( $_tmpAry[0])
			$_aryEmail['real'] = trim($_tmpAry[0], ' ">');

			$_aryEmail['addr'] = $_tmpAry[1];
		}
		else
		$_aryEmail['addr'] = $_tmpAry[0];

		// Pull User Name and Host.tld apart
		list($_aryEmail['user'], $_aryEmail['host'] ) = explode('@', $_aryEmail['addr']);

		// Put the brackets back around the address
		$_aryEmail['addr'] = '<' . $_aryEmail['addr'] . '>';

		return $_aryEmail;
	}

	/**
	 * Returns an array of bares addresses for use with 'RCPT TO:'
	 * This is a "build as you go" method. Each time this method is called
	 * the underlaying array is destroyed and reconstructed.
	 *
	 * @return 		array		Returns an array of bares addresses
	 */
	function get_RCPT_list()
	{
		/**
		 * An array of bares addresses for use with 'RCPT TO:'
		 */
		$_RCPT_list=array();

		// walk down Recipients array and pull just email addresses
		foreach ( $this->_msgRecipients as $_host => $_list )
		{
			foreach ( $_list as $_subList )
			{
				foreach ( $_subList as $_name => $_addr )
				{
					// build RCPT list
					$_RCPT_list[] = $_name . '@' . $_host;
				}
			}
		}

		return $_RCPT_list;
	}

	/**
	 * Returns an array of addresses for a specific type; TO, CC or BCC
	 *
	 * @param 		mixed 	$_which 	Which collection of adresses to return
	 * @return 		array 				Array of emaill address
	 */
	function get_email_list($_which = null)
	{
		// We need to know which address segment to pull
		if ( $_which )
		{
			// Make sure we have addresses to process
			if ( $this->_msgRecipients )
			{
				$_RCPT_list=array();
				// walk down Recipients array and pull just email addresses
				foreach ( $this->_msgRecipients as $_host => $_list )
				{
					if ( $this->_msgRecipients[$_host][$_which] )
					{
						foreach ( $this->_msgRecipients[$_host][$_which] as $_addr => $_realName )
						{
							if ( $_realName )	// DOL_CHANGE FIX
							{
								$_realName = '"' . $_realName . '"';
								$_RCPT_list[] = $_realName . ' <' . $_addr . '@' . $_host . '>';
							}
							else
							{
								$_RCPT_list[] = $_addr . '@' . $_host;
							}
						}
					}
				}

				return implode(', ', $_RCPT_list);
			}
			else
			{
				$this->_setErr(101, 'No eMail Address for message to be sent to.');
				return false;
			}
		}
		else
		{
			$this->_setErr(102, 'eMail type not defined.');
			return false;
		}

	}

	/**
	 * TO Address[es] inwhich to send mail to
	 *
	 * @param 	mixed 	$_addrTo 	TO Address[es] inwhich to send mail to
	 * @return 	void
	 */
	function setTO($_addrTo)
	{
		if ( $_addrTo )
		$this->_buildAddrList('to', $_addrTo);
	}

	/**
	 * Retrieves the TO Address[es] inwhich to send mail to
	 *
	 * @return 	string 	TO Address[es] inwhich to send mail to
	 */
	function getTo()
	{
		return $this->get_email_list('to');
	}

	/**
	 * CC Address[es] inwhich to send mail to
	 *
	 * @param 	string	$_strCC		CC Address[es] inwhich to send mail to
	 * @return 	void
	 */
	function setCC($_strCC)
	{
		if ( $_strCC )
		$this->_buildAddrList('cc', $_strCC);
	}

	/**
	 * Retrieves the CC Address[es] inwhich to send mail to
	 *
	 * @return 	string 		CC Address[es] inwhich to send mail to
	 */
	function getCC()
	{
		return $this->get_email_list('cc');
	}

	/**
	 * BCC Address[es] inwhich to send mail to
	 *
	 * @param 	string		$_strBCC	Recipients BCC Address[es] inwhich to send mail to
	 * @return 	void
	 */
	function setBCC($_strBCC)
	{
		if ( $_strBCC )
		$this->_buildAddrList('bcc', $_strBCC);
	}

	/**
	 * Retrieves the BCC Address[es] inwhich to send mail to
	 *
	 * @return 	string		BCC Address[es] inwhich to send mail to
	 */
	function getBCC()
	{
		return $this->get_email_list('bcc');
	}

	/**
	 * Message Subject
	 *
	 * @param 	string 	$_strSubject	Message Subject
	 * @return 	void
	 */
	function setSubject($_strSubject = '')
	{
		if ( $_strSubject )
		$this->_msgSubject = $_strSubject;
	}

	/**
	 * Retrieves the Message Subject
	 *
	 * @return 	string 		Message Subject
	 */
	function getSubject()
	{
		return $this->_msgSubject;
	}

	/**
	 * Constructes and returns message header
	 *
	 * @return string Complete message header
	 */
	function getHeader()
	{
		$_header = 'From: '       . $this->getFrom('org') . "\r\n"
		. 'To: '         . $this->getTO()          . "\r\n";

		if ( $this->getCC() )
		$_header .= 'Cc: ' . $this->getCC()  . "\r\n";

		if ( $this->getBCC() )
		$_header .= 'Bcc: ' . $this->getBCC()  . "\r\n";

		$host=$this->getHost();
		$host=preg_replace('@tcp://@i','',$host);	// Remove prefix
		$host=preg_replace('@ssl://@i','',$host);	// Remove prefix

		//NOTE: Message-ID should probably contain the username of the user who sent the msg
		$_header .= 'Subject: '    . $this->getSubject()     . "\r\n"
		.  'Date: '       . date("r")               . "\r\n"
		.  'Message-ID: <' . time() . '.SMTPs@' . $host . ">\r\n";
		//                 . 'Read-Receipt-To: '   . $this->getFrom( 'org' ) . "\r\n"
		//                 . 'Return-Receipt-To: ' . $this->getFrom( 'org' ) . "\r\n";

		if ( $this->getSensitivity() )
		$_header .= 'Sensitivity: ' . $this->getSensitivity()  . "\r\n";

		if ( $this->_msgPriority != 3 )
		$_header .= $this->getPriority();


		// DOL_CHANGE LDR
		if ( $this->getDeliveryReceipt() )
		$_header .= 'Disposition-Notification-To: '.$this->getFrom('addr') . "\r\n";
		if ( $this->getErrorsTo() )
		$_header .= 'Errors-To: '.$this->getErrorsTo('addr') . "\r\n";


		$_header .= 'X-Mailer: Dolibarr version ' . DOL_VERSION .' (using SMTPs Mailer)'                   . "\r\n"
		.  'Mime-Version: 1.0'                            . "\r\n";

		return $_header;
	}

	/**
	 * Message Content
	 *
	 * @param 	string 	$strContent		Message Content
	 * @param	string	$strType		Type
	 * @return 	void
	 */
	function setBodyContent($strContent, $strType = 'plain')
	{
		//if ( $strContent )
		//{
		if ( $strType == 'html' )
		$strMimeType = 'text/html';
		else
		$strMimeType = 'text/plain';

		// Make RFC821 Compliant, replace bare linefeeds
		$strContent = preg_replace("/(?<!\r)\n/si", "\r\n", $strContent);

		$strContent = rtrim(wordwrap($strContent, 75, "\r\n"));

		$this->_msgContent[$strType] = array();

		$this->_msgContent[$strType]['mimeType'] = $strMimeType;
		$this->_msgContent[$strType]['data']     = $strContent;

		if ( $this->getMD5flag() )
		$this->_msgContent[$strType]['md5']      = dol_hash($strContent);
		//}
	}

	/**
	 * Retrieves the Message Content
	 *
	 * @return 	string			Message Content
	 */
	function getBodyContent()
	{
		// Generate a new Boundary string
		$this->_setBoundary();

		// What type[s] of content do we have
		$_types = array_keys($this->_msgContent);

		// How many content types do we have
		$keyCount = count($_types);

		// If we have ZERO, we have a problem
		if( $keyCount === 0 )
		die ("Sorry, no content");

		// If we have ONE, we can use the simple format
		else if( $keyCount === 1 )
		{
			$_msgData = $this->_msgContent;
			$_msgData = $_msgData[$_types[0]];

			$content = 'Content-Type: ' . $_msgData['mimeType'] . '; charset="' . $this->getCharSet() . '"' . "\r\n"
			. 'Content-Transfer-Encoding: ' . $this->getTransEncodeType() . "\r\n"
			. 'Content-Disposition: inline'  . "\r\n"
			. 'Content-Description: message' . "\r\n";

			if ( $this->getMD5flag() )
			$content .= 'Content-MD5: ' . $_msgData['md5'] . "\r\n";

			$content .= "\r\n"
			.  $_msgData['data'] . "\r\n";
		}

		// If we have more than ONE, we use the multi-part format
		else if( $keyCount > 1 )
		{
			// Since this is an actual multi-part message
			// We need to define a content message Boundary
			// NOTE: This was 'multipart/alternative', but Windows based
			//       mail servers have issues with this.
			/*
			 * TODO  Investigate "nested" boundary message parts
			*/
			//$content = 'Content-Type: multipart/related; boundary="' . $this->_getBoundary() . '"'   . "\r\n";
			$content = 'Content-Type: multipart/mixed; boundary="' . $this->_getBoundary('mixed') . '"'   . "\r\n";

			//                     . "\r\n"
			//                     . 'This is a multi-part message in MIME format.' . "\r\n";
			$content .= "Content-Transfer-Encoding: 8bit\r\n";
			$content .= "\r\n";

			$content .= "--" . $this->_getBoundary('mixed') . "\r\n";

			if (key_exists('image', $this->_msgContent))
			{
				$content.= 'Content-Type: multipart/alternative; boundary="'.$this->_getBoundary('alternative').'"' . "\r\n";
				$content .= "\r\n";
				$content .= "--" . $this->_getBoundary('alternative') . "\r\n";
			}

			// Loop through message content array
			foreach ($this->_msgContent as $type => $_content )
			{
				if ( $type == 'attachment' )
				{
					// loop through all attachments
					foreach ( $_content as $_file => $_data )
					{
						$content .= "--" . $this->_getBoundary('mixed') . "\r\n"
						.  'Content-Disposition: attachment; filename="' . $_data['fileName'] . '"' . "\r\n"
						.  'Content-Type: ' . $_data['mimeType'] . '; name="' . $_data['fileName'] . '"' . "\r\n"
						.  'Content-Transfer-Encoding: base64' . "\r\n"
						.  'Content-Description: File Attachment' . "\r\n";

						if ( $this->getMD5flag() )
						$content .= 'Content-MD5: ' . $_data['md5'] . "\r\n";

						$content .= "\r\n" .  $_data['data'] . "\r\n\r\n";
					}
				}
				// DOL_CHANGE LDR
				else if ( $type == 'image' )
				{
					// loop through all images
					foreach ( $_content as $_image => $_data )
					{
						$content .= "--" . $this->_getBoundary('related') . "\r\n";  // always related for an inline image

						$content .= 'Content-Type: ' . $_data['mimeType'] . '; name="' . $_data['imageName'] . '"' . "\r\n"
						.  'Content-Transfer-Encoding: base64' . "\r\n"
						.  'Content-Disposition: inline; filename="' . $_data['imageName'] . '"' . "\r\n"
						.  'Content-ID: <' . $_data['cid'] . '> ' . "\r\n";

						if ( $this->getMD5flag() )
						$content .= 'Content-MD5: ' . $_data['md5'] . "\r\n";

						$content .= "\r\n"
						. $_data['data'] . "\r\n";
					}

					// always end related and end alternative after inline images
					$content.= "--" . $this->_getBoundary('related') . "--" . "\r\n";
					$content.= "\r\n" . "--" . $this->_getBoundary('alternative') . "--" . "\r\n";
					$content.= "\r\n";
				}
				else
				{
					if (key_exists('image', $this->_msgContent))
					{
						$content.= "Content-Type: text/plain; charset=" . $this->getCharSet() . "\r\n";
						$content.= "\r\n" . strip_tags($_content['data']) . "\r\n"; // Add plain text message
						$content.= "--" . $this->_getBoundary('alternative') . "\r\n";
						$content.= 'Content-Type: multipart/related; boundary="' . $this->_getBoundary('related') . '"' . "\r\n";
						$content.= "\r\n";
						$content.= "--" . $this->_getBoundary('related') . "\r\n";
					}

					$content .= 'Content-Type: ' . $_content['mimeType'] . '; '
					//                             . 'charset="' . $this->getCharSet() . '"';
					. 'charset=' . $this->getCharSet() . '';

					//                    $content .= ( $type == 'html') ? '; name="HTML Part"' : '';
					$content .=  "\r\n";
					//                    $content .= 'Content-Transfer-Encoding: ';
					//                    $content .= ($type == 'html') ? 'quoted-printable' : $this->getTransEncodeType();
					//                    $content .=  "\r\n"
					//                             . 'Content-Disposition: inline'  . "\r\n"
					//                             . 'Content-Description: ' . $type . ' message' . "\r\n";

					if ( $this->getMD5flag() )
					$content .= 'Content-MD5: ' . $_content['md5'] . "\r\n";

					$content .= "\r\n"	. $_content['data'] . "\r\n\r\n";
				}
			}

			// Close message boundries
			//            $content .= "\r\n--" . $this->_getBoundary() . '--' . "\r\n" ;
			$content .= "--" . $this->_getBoundary('mixed') . '--' . "\r\n" ;
		}

		return $content;
	}

	/**
	 * File attachments are added to the content array as sub-arrays,
	 * allowing for multiple attachments for each outbound email
	 *
	 * @param string $strContent  File data to attach to message
	 * @param string $strFileName File Name to give to attachment
	 * @param string $strMimeType File Mime Type of attachment
	 * @return void
	 */
	function setAttachment($strContent, $strFileName = 'unknown', $strMimeType = 'unknown')
	{
		if ( $strContent )
		{
			$strContent = rtrim(chunk_split(base64_encode($strContent), 76, "\r\n"));    // 76 max is defined into http://tools.ietf.org/html/rfc2047

			$this->_msgContent['attachment'][$strFileName]['mimeType'] = $strMimeType;
			$this->_msgContent['attachment'][$strFileName]['fileName'] = $strFileName;
			$this->_msgContent['attachment'][$strFileName]['data']     = $strContent;

			if ( $this->getMD5flag() )
			$this->_msgContent['attachment'][$strFileName]['md5']      = dol_hash($strContent);
		}
	}


	// DOL_CHANGE LDR

	/**
	 * Image attachments are added to the content array as sub-arrays,
	 * allowing for multiple images for each outbound email
	 *
	 * @param 	string $strContent  	Image data to attach to message
	 * @param 	string $strImageName 	Image Name to give to attachment
	 * @param 	string $strMimeType 	Image Mime Type of attachment
	 * @param 	string $strImageCid		CID
	 * @return 	void
	 */
	function setImageInline($strContent, $strImageName = 'unknown', $strMimeType = 'unknown', $strImageCid = 'unknown')
	{
		if ($strContent)
		{
			$this->_msgContent['image'][$strImageName]['mimeType'] = $strMimeType;
			$this->_msgContent['image'][$strImageName]['imageName'] = $strImageName;
			$this->_msgContent['image'][$strImageName]['cid']      = $strImageCid;
			$this->_msgContent['image'][$strImageName]['data']     = $strContent;

			if ( $this->getMD5flag() )
			$this->_msgContent['image'][$strImageName]['md5']      = dol_hash($strContent);
		}
	}
	// END DOL_CHANGE LDR


	/**
	 * Message Content Sensitivity
	 * Message Sensitivity values:
	 *   - [0] None - default
	 *   - [1] Personal
	 *   - [2] Private
	 *   - [3] Company Confidential
	 *
	 * @param 	string	$_value		Message Sensitivity
	 * @return 	void
	 */
	function setSensitivity($_value = 0)
	{
		if ( ( is_numeric($_value) ) &&
		( ( $_value >= 0 ) && ( $_value <= 3 ) ) )
		$this->_msgSensitivity = $_value;
	}

	/**
	 * Returns Message Content Sensitivity string
	 * Message Sensitivity values:
	 *   - [0] None - default
	 *   - [1] Personal
	 *   - [2] Private
	 *   - [3] Company Confidential
	 *
	 * @return 	void
	 */
	function getSensitivity()
	{
		return $this->_arySensitivity[$this->_msgSensitivity];
	}

	/**
	 * Message Content Priority
	 * Message Priority values:
	 *  - [0] 'Bulk'
	 *  - [1] 'Highest'
	 *  - [2] 'High'
	 *  - [3] 'Normal' - default
	 *  - [4] 'Low'
	 *  - [5] 'Lowest'
	 *
	 * @param 	string 	$_value 	Message Priority
	 * @return 	void
	 */
	function setPriority ( $_value = 3 )
	{
		if ( ( is_numeric($_value) ) &&
		( ( $_value >= 0 ) && ( $_value <= 5 ) ) )
		$this->_msgPriority = $_value;
	}

	/**
	 * Message Content Priority
	 * Message Priority values:
	 *  - [0] 'Bulk'
	 *  - [1] 'Highest'
	 *  - [2] 'High'
	 *  - [3] 'Normal' - default
	 *  - [4] 'Low'
	 *  - [5] 'Lowest'
	 *
	 * @return void
	 */
	function getPriority()
	{
		return 'Importance: ' . $this->_aryPriority[$this->_msgPriority] . "\r\n"
		. 'Priority: '   . $this->_aryPriority[$this->_msgPriority] . "\r\n"
		. 'X-Priority: ' . $this->_msgPriority . ' (' . $this->_aryPriority[$this->_msgPriority] . ')' . "\r\n";
	}

	/**
	 * Set flag which determines whether to calculate message MD5 checksum.
	 *
	 * @param 	string 	$_flag		Message Priority
	 * @return 	void
	 */
	function setMD5flag($_flag = false)
	{
		$this->_smtpMD5 = $_flag;
	}

	/**
	 * Gets flag which determines whether to calculate message MD5 checksum.
	 *
	 * @return 	string 				Message Priority
	 */
	function getMD5flag()
	{
		return $this->_smtpMD5;
	}

	/**
	 * Message X-Header Content
	 * This is a simple "insert". Whatever is given will be placed
	 * "as is" into the Xheader array.
	 *
	 * @param string $strXdata Message X-Header Content
	 * @return void
	 */
	function setXheader($strXdata)
	{
		if ( $strXdata )
		$this->_msgXheader[] = $strXdata;
	}

	/**
	 * Retrieves the Message X-Header Content
	 *
	 * @return string $_msgContent Message X-Header Content
	 */
	function getXheader()
	{
		return $this->_msgXheader;
	}

	/**
	 * Generates Random string for MIME message Boundary
	 *
	 * @return void
	 */
	function _setBoundary()
	{
		$this->_smtpsBoundary = "multipart_x." . time() . ".x_boundary";
		$this->_smtpsRelatedBoundary = 'mul_'.dol_hash(uniqid("dolibarr2"));
		$this->_smtpsAlternativeBoundary = 'mul_'.dol_hash(uniqid("dolibarr3"));
	}

	/**
	 * Retrieves the MIME message Boundary
	 *
	 * @param  string $type				Type of boundary
	 * @return string $_smtpsBoundary 	MIME message Boundary
	 */
	function _getBoundary($type='mixed')
	{
		if ($type == 'mixed') return $this->_smtpsBoundary;
		else if ($type == 'related') return $this->_smtpsRelatedBoundary;
		else if ($type == 'alternative') return $this->_smtpsAlternativeBoundary;
	}

	/**
	 * This function has been modified as provided by SirSir to allow multiline responses when
	 * using SMTP Extensions
	 *
	 * @param	Handler		$socket			Socket handler
	 * @param	string		$response		Response
	 * @return	boolean						True or false
	 */
	function server_parse($socket, $response)
	{
		/**
		 * Returns constructed SELECT Object string or boolean upon failure
		 * Default value is set at TRUE
		 */
		$_retVal = true;

		$server_response = '';

		while ( substr($server_response,3,1) != ' ' )
		{
			if( !( $server_response = fgets($socket, 256) ) )
			{
				$this->_setErr(121, "Couldn't get mail server response codes");
				$_retVal = false;
			}
		}

		if( !( substr($server_response, 0, 3) == $response ) )
		{
			$this->_setErr(120, "Ran into problems sending Mail.\r\nResponse: $server_response");
			$_retVal = false;
		}

		return $_retVal;
	}

	/**
	 * Send str
	 *
	 * @param	string		$_strSend		String to send
	 * @param 	string		$_returnCode	Return code
	 * @param 	string		$CRLF			CRLF
	 * @return 	boolean						True or false
	 */
	function socket_send_str( $_strSend, $_returnCode = null, $CRLF = "\r\n" )
	{
		if ($this->_debug) $this->log.=$_strSend;	// DOL_CHANGE LDR for log
		fputs($this->socket, $_strSend . $CRLF);
		if ($this->_debug) $this->log.=' ('.$_returnCode.')' . $CRLF;

		if ( $_returnCode )
		return $this->server_parse($this->socket, $_returnCode);
	}

	// =============================================================
	// ** Error handling methods

	/**
	 * Defines errors codes and messages for Class
	 *
	 * @param  int    $_errNum  Error Code Number
	 * @param  string $_errMsg  Error Message
	 * @return void
	 */
	function _setErr ( $_errNum, $_errMsg )
	{
		$this->_smtpsErrors[] = array( 'num' => $_errNum,
                                       'msg' => $_errMsg );
	}

	/**
	 * Returns errors codes and messages for Class
	 *
	 * @return string $_errMsg  Error Message
	 */
	function getErrors()
	{
		$_errMsg = array();

		foreach ( $this->_smtpsErrors as $_err => $_info )
		{
			$_errMsg[] = 'Error [' . $_info['num'] .']: '. $_info['msg'];
		}

		return implode("\n", $_errMsg);
	}


}


// =============================================================
// ** CSV Version Control Info

/**
 * Revision      2011/09/12 07:49:59  eldy
 * Doxygen
 *
 * Revision      2011/09/06 06:53:53  hregis
 * Fix: use dol_hash instead md5 php function
 *
 * Revision      2011/09/03 00:14:27  eldy
 * Doxygen
 *
 * Revision      2011/08/28 14:24:23  eldy
 * Doxygen
 *
 * Revision      2011/07/12 22:19:02  eldy
 * Fix: Attachment fails if content was empty
 *
 * Revision      2011/06/20 23:17:50  hregis
 * Fix: use best structure of mail
 *
 * Revision      2010/04/13 20:58:37  eldy
 * Fix: Can provide ip address on smtps. Better error reporting.
 *
 * Revision      2010/04/13 20:30:25  eldy
 * Fix: Can provide ip address on smtps. Better error reporting.
 *
 * Revision      2010/01/12 13:02:07  hregis
 * Fix: missing attach-files
 *
 * Revision      2009/11/01 14:16:30  eldy
 * Fix: Sending mail with SMTPS was not working.
 *
 * Revision      2009/10/20 13:14:47  hregis
 * Fix: function "split" is deprecated since php 5.3.0
 *
 * Revision      2009/05/13 19:10:07  eldy
 * New: Can use inline images.Everything seems to work with thunderbird and webmail gmail. New to be tested on other mail browsers.
 *
 * Revision      2009/05/13 14:49:30  eldy
 * Fix: Make code so much simpler and solve a lot of problem with new version.
 *
 * Revision      2009/02/09 00:04:35  eldy
 * Added support for SMTPS protocol
 *
 * Revision       2008/04/16 23:11:45  eldy
 * New: Add action "Test server connectivity"
 *
 * Revision 1.18  2007/01/12 22:17:08  ongardie
 * - Added full_http_site_root() to utils-misc.php
 * - Made SMTPs' getError() easier to use
 * - Improved activity modified emails
 *
 * Revision 1.17  2006/04/05 03:15:40  ongardie
 * -Fixed method name typo that resulted in a fatal error.
 *
 * Revision 1.16  2006/03/08 04:05:25  jswalter
 *  - '$_smtpsTransEncode' was removed and '$_smtpsTransEncodeType' is now used
 *  - '$_smtpsTransEncodeType' is defaulted to ZERO
 *  - corrected 'setCharSet()'  internal vars
 *  - defined '$_mailPath'
 *  - added '$_smtpMD5' as a class property
 *  - added 'setMD5flag()' to set above property
 *  - added 'getMD5flag()' to retrieve above property
 *  - 'setAttachment()' will add an MD5 checksum to attachements if above property is set
 *  - 'setBodyContent()' will add an MD5 checksum to message parts if above property is set
 *  - 'getBodyContent()' will insert the MD5 checksum for messages and attachments if above property is set
 *  - removed leading dashes from message boundry
 *  - added propery "Close message boundry" tomessage block
 *  - corrected some comments in various places
 *  - removed some incorrect comments in others
 *
 * Revision 1.15  2006/02/21 02:00:07  vanmer
 * - patch to add support for sending to exim mail server
 * - thanks to Diego Ongaro at ETSZONE (diego@etszone.com)
 *
 * Revision 1.14  2005/08/29 16:22:10  jswalter
 *  - change 'multipart/alternative' to 'multipart/mixed', but Windows based mail servers have issues with this.
 * Bug 594
 *
 * Revision 1.13  2005/08/21 01:57:30  vanmer
 * - added initialization for array if no recipients exist
 *
 * Revision 1.12  2005/08/20 12:04:30  braverock
 * - remove potentially binary characters from Message-ID
 * - add getHost to get the hostname of the mailserver
 * - add username to Message-ID header
 *
 * Revision 1.11  2005/08/20 11:49:48  braverock
 * - fix typos in boundary
 * - remove potentially illegal characters from boundary
 *
 * Revision 1.10  2005/08/19 20:39:32  jswalter
 *  - added _server_connect()' as a seperate method to handle server connectivity.
 *  - added '_server_authenticate()' as a seperate method to handle server authentication.
 *  - 'sendMsg()' now uses the new methods to handle server communication.
 *  - modified 'server_parse()' and 'socket_send_str()' to give error codes and messages.
 *
 * Revision 1.9  2005/08/19 15:40:18  jswalter
 *  - IMPORTANT: 'setAttachement()' is now spelled correctly: 'setAttachment()'
 *  - added additional comment to several methods
 *  - added '$_smtpsTransEncodeTypes' array to limit encode types
 *  - added parameters to 'sendMsg()' for future development around debugging and logging
 *  - added error code within 'setConfig()' if the given path is not found
 *  - 'setTransportType()' now has parameter validation
 *     [this still is not implemented]
 *  - 'setPort()' now does parameter validation
 *  - 'setTransEncode()' now has parameter validation against '$_smtpsTransEncodeTypes'
 *  - modified 'get_email_list()' to handle error handling
 *  - 'setSensitivity()' now has parameter validation
 *  - 'setPriority()' now has parameter validation
 *
 * Revision 1.8  2005/06/24 21:00:20  jswalter
 *   - corrected comments
 *   - corrected the defualt value for 'setPriority()'
 *   - modified 'setAttachement()' to process multiple attachments correctly
 *   - modified 'getBodyContent()' to handle multiple attachments
 * Bug 310
 *
 * Revision 1.7  2005/05/19 21:12:34  braverock
 * - replace chunk_split() with wordwrap() to fix funky wrapping of templates
 *
 * Revision 1.6  2005/04/25 04:55:06  jswalter
 *  - cloned from Master Version
 *
 * Revision 1.10  2005/04/25 04:54:10  walter
 *  - "fixed" 'getBodyContent()' to handle a "simple" text only message
 *
 * Revision 1.9  2005/04/25 03:52:01  walter
 *  - replace closing curly bracket. Removed it in last revision!
 *
 * Revision 1.8  2005/04/25 02:29:49  walter
 *  - added '$_transportType' and its getter/setter methods.
 *    for future use. NOT yet implemented.
 *  - in 'sendMsg()', added HOST validation check
 *  - added error check for initial Socket Connection
 *  - created new method 'socket_send_str()' to process socket
 *    communication in a unified means. Socket calls within
 *    'sendMsg()' have been modified to use this new method.
 *  - expanded comments in 'setConfig()'
 *  - added "error" check on PHP ini file properties. If these
 *    properties not set within the INI file, the default values
 *    will be used.
 *  - modified 'get_RCPT_list()' to reset itself each time it is called
 *  - modified 'setBodyContent()' to store data in a sub-array for better
 *    parsing within the 'getBodyContent()' method
 *  - modified 'getBodyContent()' to process contents array better.
 *    Also modified to handle attachements.
 *  - added 'setAttachement()' so files and other data can be attached
 *    to messages
 *  - added '_setErr()' and 'getErrors()' as an attempt to begin an error
 *    handling process within this class
 *
 * Revision 1.7  2005/04/13 15:23:50  walter
 *  - made 'CC' a conditional insert
 *  - made 'BCC' a conditional insert
 *  - fixed 'Message-ID'
 *  - corrected 'getSensitivity()'
 *  - modified '$_aryPriority[]' to proper values
 *  - updated 'setConfig()' to handle external Ini or 'php.ini'
 *
 * Revision 1.6  2005/03/15 17:34:06  walter
 *  - corrected Message Sensitivity property and method comments
 *  - added array to Message Sensitivity
 *  - added getSensitivity() method to use new Sensitivity array
 *  - created seters and getter for Priority with new Prioity value array property
 *  - changed config file include from 'include_once'
 *  - modified getHeader() to ustilize new Message Sensitivity and Priorty properties
 *
 * Revision 1.5  2005/03/14 22:25:27  walter
 *  - added references
 *  - added Message sensitivity as a property with Getter/Setter methods
 *  - boundary is now a property with Getter/Setter methods
 *  - added 'builtRCPTlist()'
 *  - 'sendMsg()' now uses Object properties and methods to build message
 *  - 'setConfig()' to load external file
 *  - 'setForm()' will "strip" the email address out of "address" string
 *  - modifed 'getFrom()' to handle "striping" the email address
 *  - '_buildArrayList()' creates a multi-dimensional array of addresses
 *    by domain, TO, CC & BCC and then by User Name.
 *  - '_strip_email()' pulls email address out of "full Address" string'
 *  - 'get_RCPT_list()' pulls out "bare" emaill address form address array
 *  - 'getHeader()' builds message Header from Object properties
 *  - 'getBodyContent()' builds full messsage body, even multi-part
 *
 * Revision 1.4  2005/03/02 20:53:35  walter
 *  - core Setters & Getters defined
 *  - added additional Class Properties
 *
 * Revision 1.3  2005/03/02 18:51:51  walter
 *  - added base 'Class Properties'
 *
 * Revision 1.2  2005/03/01 19:37:52  walter
 *  - CVS logging tags
 *  - more comments
 *  - more "shell"
 *  - some constants
 *
 * Revision 1.1  2005/03/01 19:22:49  walter
 *  - initial commit
 *  - basic shell with some commets
 *
 */

?>
