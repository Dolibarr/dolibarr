<?php
if (! defined('ODTPHP_PATHTOPCLZIP')) define('ODTPHP_PATHTOPCLZIP','pclzip/');
require_once ODTPHP_PATHTOPCLZIP.'pclzip.lib.php';
require_once 'ZipInterface.php';
class PclZipProxyException extends Exception
{ }
/**
 * Proxy class for the PclZip library
 * You need PHP 5.2 at least
 * You need Zip Extension or PclZip library
 * Encoding : ISO-8859-1
 *
 * @copyright  GPL License 2008 - Julien Pauli - Cyril PIERRE de GEYER - Anaska (http://www.anaska.com)
 * @copyright  GPL License 2010 - Laurent Destailleur - eldy@users.sourceforge.net
 * @license    http://www.gnu.org/copyleft/gpl.html  GPL License
 * @version 1.4
 */
class PclZipProxy implements ZipInterface
{
	protected $tmpdir = '/tmp';
	protected $openned = false;
	protected $filename;
	protected $pclzip;
    /**
     * Class constructor
     *
     * @throws PclZipProxyException
     */
	public function __construct($forcedir='')
	{
		if (! class_exists('PclZip')) {
			throw new PclZipProxyException('PclZip class not loaded - PclZip library
			 is required for using PclZipProxy'); ;
		}
		if ($forcedir) $this->tmpdir=preg_replace('|[//\/]$|','',$forcedir);	// $this->tmpdir must not contains / at the end
	}

	/**
	 * Open a Zip archive
	 *
	 * @param string $filename the name of the archive to open
	 * @return true if openning has succeeded
	 */
	public function open($filename)
	{
		if (true === $this->openned) {
			$this->close();
		}
		$this->filename = $filename;
		$this->pclzip = new PclZip($this->filename);
		$this->openned = true;
		return true;
	}

	/**
	 * Retrieve the content of a file within the archive from its name
	 *
	 * @param string $name the name of the file to extract
	 * @return the content of the file in a string
	 */
	public function getFromName($name)
	{
		if (false === $this->openned) {
			return false;
		}
		$name = preg_replace("/(?:\.|\/)*(.*)/", "\\1", $name);
		$extraction = $this->pclzip->extract(PCLZIP_OPT_BY_NAME, $name,
			PCLZIP_OPT_EXTRACT_AS_STRING);
		if (!empty($extraction)) {
			return $extraction[0]['content'];
		}
		return false;
	}

	/**
	 * Add a file within the archive from a string
	 *
	 * @param string $localname the local path to the file in the archive
	 * @param string $contents the content of the file
	 * @return true if the file has been successful added
	 */
	public function addFromString($localname, $contents)
	{
		if (false === $this->openned) {
			return false;
		}
		if (file_exists($this->filename) && !is_writable($this->filename)) {
			return false;
		}
		$localname = preg_replace("/(?:\.|\/)*(.*)/", "\\1", $localname);
		$localpath = dirname($localname);
		$tmpfilename = $this->tmpdir . '/' . basename($localname);
		if (false !== file_put_contents($tmpfilename, $contents)) {
			//print "tmpfilename=".$tmpfilename;
			//print "localname=".$localname;
			$res=$this->pclzip->delete(PCLZIP_OPT_BY_NAME, $localname);
			$add = $this->pclzip->add($tmpfilename,
				PCLZIP_OPT_REMOVE_PATH, $this->tmpdir,
				PCLZIP_OPT_ADD_PATH, $localpath);
			unlink($tmpfilename);
			if (!empty($add)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Add a file within the archive from a file
	 *
	 * @param string $filename the path to the file we want to add
	 * @param string $localname the local path to the file in the archive
	 * @return true if the file has been successful added
	 */
	public function addFile($filename, $localname = null)
	{
		if (false === $this->openned) {
			return false;
		}
		if ((file_exists($this->filename) && !is_writable($this->filename))
			|| !file_exists($filename)) {
			return false;
		}
		if (isSet($localname)) {
			$localname = preg_replace("/(?:\.|\/)*(.*)/", "\\1", $localname);
			$localpath = dirname($localname);
			$tmpfilename = $this->tmpdir . '/' . basename($localname);
		} else {
			$localname = basename($filename);
			$tmpfilename = $this->tmpdir . '/' . $localname;
			$localpath = '';
		}
		if (file_exists($filename)) {
			copy($filename, $tmpfilename);
			$this->pclzip->delete(PCLZIP_OPT_BY_NAME, $localname);
			$this->pclzip->add($tmpfilename,
				PCLZIP_OPT_REMOVE_PATH, $this->tmpdir,
				PCLZIP_OPT_ADD_PATH, $localpath);
			unlink($tmpfilename);
			return true;
		}
		return false;
	}

	/**
	 * Close the Zip archive
	 * @return true
	 */
	public function close()
	{
		if (false === $this->openned) {
			return false;
		}
		$this->pclzip = $this->filename = null;
		$this->openned = false;
		return true;
	}
}

?>