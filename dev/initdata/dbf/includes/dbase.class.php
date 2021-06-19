<?php
/**
 * \file        dev/initdata/dbf/includes/dbase.class.php
 * \ingroup     dev
 * \brief       Class to manage DBF databases
 */

// source : https://github.com/donfbecker/php-dbase

define('DBASE_RDONLY', 0);
define('DBASE_WRONLY', 1);
define('DBASE_RDWR', 2);
define('DBASE_TYPE_DBASE', 0);
define('DBASE_TYPE_FOXPRO', 1);

/**
 * Class for DBase
 */
class DBase
{
	private $fd;
	private $headerLength = 0;
	private $fields = array();
	private $fieldCount = 0;
	private $recordLength = 0;
	private $recordCount = 0;

	/**
	 * resource dbase_open
	 * @param	string	$filename 	filename
	 * @param	int		$mode		mode
	 * @return 	DBase
	 */
	public static function open($filename, $mode)
	{
		if (!file_exists($filename)) {
			return false;
		}
		$modes = array('r', 'w', 'r+');
		$mode = $modes[$mode];
		$fd = fopen($filename, $mode);
		if (!$fd) {
			return false;
		}
		return new DBase($fd);
	}

	/**
	 * resource dbase_create
	 * @param	string 	$filename	filename
	 * @param	array 	$fields		fields
	 * @param 	int 	$type 		DBASE_TYPE_DBASE
	 * @return	DBase
	 */
	public static function create($filename, $fields, $type = DBASE_TYPE_DBASE)
	{
		if (file_exists($filename)) {
			return false;
		}
		$fd = fopen($filename, 'c+');
		if (!$fd) {
			return false;
		}
		// Byte 0 (1 byte): Valid dBASE for DOS file; bits 0-2 indicate version number, bit 3
		// indicates the presence of a dBASE for DOS memo file, bits 4-6 indicate the
		// presence of a SQL table, bit 7 indicates the presence of any memo file
		// (either dBASE m PLUS or dBASE for DOS)
		self::putChar8($fd, 5);
		// Byte 1-3 (3 bytes): Date of last update; formatted as YYMMDD
		self::putChar8($fd, date('Y') - 1900);
		self::putChar8($fd, date('m'));
		self::putChar8($fd, date('d'));
		// Byte 4-7 (32-bit number): Number of records in the database file.  Currently 0
		self::putInt32($fd, 0);
		// Byte 8-9 (16-bit number): Number of bytes in the header.
		self::putInt16($fd, 32 + (32 * count($fields)) + 1);
		// Byte 10-11 (16-bit number): Number of bytes in record.
		// Make sure the include the byte for deleted flag
		$len = 1;
		foreach ($fields as &$field) {
			$len += self::length($field);
		}
		self::putInt16($fd, $len);
		// Byte 12-13 (2 bytes): Reserved, 0 filled.
		self::putInt16($fd, 0);
		// Byte 14 (1 byte): Flag indicating incomplete transaction
		// The ISMARKEDO function checks this flag. BEGIN TRANSACTION sets it to 1, END TRANSACTION and ROLLBACK reset it to 0.
		self::putChar8($fd, 0);
		// Byte 15 (1 byte): Encryption flag. If this flag is set to 1, the message Database encrypted appears. Changing this flag to 0 removes the message, but does not decrypt the file.
		self::putChar8($fd, 0);
		// Byte 16-27 (12 bytes): Reserved for dBASE for DOS in a multi-user environment
		self::putInt32($fd, 0);
		self::putInt32($fd, 0);
		self::putInt32($fd, 0);
		// Byte 28 (1 byte): Production .mdx file flag; 0x01 if there is a production .mdx file, 0x00 if not
		self::putChar8($fd, 0);
		// Byte 29 (1 byte): Language driver ID
		// (no clue what this is)
		self::putChar8($fd, 0);
		// Byte 30-31 (2 bytes): Reserved, 0 filled.
		self::putInt16($fd, 0);
		// Byte 32 - n (32 bytes each): Field descriptor array
		foreach ($fields as &$field) {
			self::putString($fd, $field[0], 11);       // Byte 0 - 10 (11 bytes): Field name in ASCII (zero-filled)
			self::putString($fd, $field[1], 1);       // Byte 11 (1 byte): Field type in ASCII (C, D, F, L, M, or N)
			self::putInt32($fd, 0);                    // Byte 12 - 15 (4 bytes): Reserved
			self::putChar8($fd, self::length($field)); // Byte 16 (1 byte): Field length in binary. The maximum length of a field is 254 (0xFE).
			self::putChar8($fd, $field[3]);            // Byte 17 (1 byte): Field decimal count in binary
			self::putInt16($fd, 0);                    // Byte 18 - 19 (2 bytes): Work area ID
			self::putChar8($fd, 0);                    // Byte 20 (1 byte): Example (??)
			self::putInt32($fd, 0);                    // Byte 21 - 30 (10 bytes): Reserved
			self::putInt32($fd, 0);
			self::putInt16($fd, 0);
			self::putChar8($fd, 0);                    // Byte 31 (1 byte): Production MDX field flag; 1 if field has an index tag in the production MDX file, 0 if not
		}
		// Byte n + 1 (1 byte): 0x0D as the field descriptor array terminator
		self::putChar8($fd, 0x0D);
		return new DBase($fd);
	}

	/**
	 * Create DBase instance
	 * @param mixed $fd file descriptor
	 * @return void
	 */
	private function __construct($fd)
	{
		$this->fd = $fd;
		// Byte 4-7 (32-bit number): Number of records in the database file.  Currently 0
		fseek($this->fd, 4, SEEK_SET);
		$this->recordCount = self::getInt32($fd);
		// Byte 8-9 (16-bit number): Number of bytes in the header.
		fseek($this->fd, 8, SEEK_SET);
		$this->headerLength = self::getInt16($fd);
		// Number of fields is (headerLength - 33) / 32)
		$this->fieldCount = ($this->headerLength - 33) / 32;
		// Byte 10-11 (16-bit number): Number of bytes in record.
		fseek($this->fd, 10, SEEK_SET);
		$this->recordLength = self::getInt16($fd);
		// Byte 32 - n (32 bytes each): Field descriptor array
		fseek($fd, 32, SEEK_SET);
		for ($i = 0; $i < $this->fieldCount; $i++) {
			$data = fread($this->fd, 32);
			$field = array_map('trim', unpack('a11name/a1type/c4/c1length/c1precision/s1workid/c1example/c10/c1production', $data));
			$this->fields[] = $field;
		}
	}

	/**
	 * dbase_close
	 * @return void
	 */
	public function close()
	{
		fclose($this->fd);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * dbase_get_header_info
	 * @return array
	 */
	public function get_header_info()
	{
		// phpcs:disable
		return $this->fields;
	}

	/**
	 * dbase_numfields
	 * @return int
	 */
	public function numfields()
	{
		return $this->fieldCount;
	}

	/**
	 * dbase_numrecords
	 * @return int
	 */
	public function numrecords()
	{
		return $this->recordCount;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * dbase_add_record
	 * @param array $record record
	 * @return bool
	 */
	public function add_record($record)
	{
		// phpcs:enable
		if (count($record) != $this->fieldCount) {
			return false;
		}
		// Seek to end of file, minus the end of file marker
		fseek($this->fd, 0, SEEK_END);
		// Put the deleted flag
		self::putChar8($this->fd, 0x20);
		// Put the record
		if (!$this->putRecord($record)) {
			return false;
		}
		// Update the record count
		fseek($this->fd, 4);
		self::putInt32($this->fd, ++$this->recordCount);
		return true;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * dbase_replace_record
	 * @param array $record record
	 * @param int $record_number record number
	 * @return bool
	 */
	public function replace_record($record, $record_number)
	{
		// phpcs:enable
		if (count($record) != $this->fieldCount) {
			return false;
		}
		if ($record_number < 1 || $record_number > $this->recordCount) {
			return false;
		}
		// Skip to the record location, plus the 1 byte for the deleted flag
		fseek($this->fd, $this->headerLength + ($this->recordLength * ($record_number - 1)) + 1);
		return $this->putRecord($record);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * dbase_delete_record
	 * @param int $record_number record number
	 * @return bool
	 */
	public function delete_record($record_number)
	{
		// phpcs:enable
		if ($record_number < 1 || $record_number > $this->recordCount) {
			return false;
		}
		fseek($this->fd, $this->headerLength + ($this->recordLength * ($record_number - 1)));
		self::putChar8($this->fd, 0x2A);
		return true;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * dbase_get_record
	 * @param int $record_number record number
	 * @return array
	 */
	public function get_record($record_number)
	{
		// phpcs:enable
		if ($record_number < 1 || $record_number > $this->recordCount) {
			return false;
		}
		fseek($this->fd, $this->headerLength + ($this->recordLength * ($record_number - 1)));
		$record = array(
			'deleted' => self::getChar8($this->fd) == 0x2A ? 1 : 0
		);
		foreach ($this->fields as $i => &$field) {
			$value = trim(fread($this->fd, $field['length']));
			if ($field['type'] == 'L') {
				$value = strtolower($value);
				if ($value == 't' || $value == 'y') {
					$value = true;
				} elseif ($value == 'f' || $value == 'n') {
					$value = false;
				} else {
					$value = null;
				}
			}
			$record[$i] = $value;
		}
		return $record;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * dbase_get_record_with_names
	 * @param int $record_number record number
	 * @return array
	 */
	public function get_record_with_names($record_number)
	{
		// phpcs:enable
		if ($record_number < 1 || $record_number > $this->recordCount) {
			return false;
		}
		$record = $this->get_record($record_number);
		foreach ($this->fields as $i => &$field) {
			$record[$field['name']] = $record[$i];
			unset($record[$i]);
		}
		return $record;
	}

	/**
	 * dbase_pack
	 * @return void
	 */
	public function pack()
	{
		$in_offset = $out_offset = $this->headerLength;
		$new_count = 0;
		$rec_count = $this->recordCount;
		while ($rec_count > 0) {
			fseek($this->fd, $in_offset, SEEK_SET);
			$record = fread($this->fd, $this->recordLength);
			$deleted = substr($record, 0, 1);
			if ($deleted != '*') {
				fseek($this->fd, $out_offset, SEEK_SET);
				fwrite($this->fd, $record);
				$out_offset += $this->recordLength;
				$new_count++;
			}
			$in_offset += $this->recordLength;
			$rec_count--;
		}
		ftruncate($this->fd, $out_offset);
		// Update the record count
		fseek($this->fd, 4);
		self::putInt32($this->fd, $new_count);
	}

	/*
	 * A few utilitiy functions
	 */

	 /**
	 * @param string $field field
	 * @return int
	 */
	private static function length($field)
	{
		switch ($field[1]) {
			case 'D': // Date: Numbers and a character to separate month, day, and year (stored internally as 8 digits in YYYYMMDD format)
				return 8;
			case 'T': // DateTime (YYYYMMDDhhmmss.uuu) (FoxPro)
				return 18;
			case 'M': // Memo (ignored): All ASCII characters (stored internally as 10 digits representing a .dbt block number, right justified, padded with whitespaces)
			case 'N': // Number: -.0123456789 (right justified, padded with whitespaces)
			case 'F': // Float: -.0123456789 (right justified, padded with whitespaces)
			case 'C': // String: All ASCII characters (padded with whitespaces up to the field's length)
				return $field[2];
			case 'L': // Boolean: YyNnTtFf? (? when not initialized)
				return 1;
		}
		return 0;
	}

	/*
	 * Functions for reading and writing bytes
	 */

	 /**
	 * getChar8
	 * @param mixed $fd file descriptor
	 * @return int
	 */
	private static function getChar8($fd)
	{
		return ord(fread($fd, 1));
	}

	/**
	 * putChar8
	 * @param mixed $fd file descriptor
	 * @param mixed $value value
	 * @return bool
	 */
	private static function putChar8($fd, $value)
	{
		return fwrite($fd, chr($value));
	}

	/**
	 * getInt16
	 * @param mixed $fd file descriptor
	 * @param int $n n
	 * @return bool
	 */
	private static function getInt16($fd, $n = 1)
	{
		$data = fread($fd, 2 * $n);
		$i = unpack("S$n", $data);
		if ($n == 1) {
			return (int) $i[1];
		} else {
			return array_merge($i);
		}
	}

	/**
	 * putInt16
	 * @param mixed $fd file descriptor
	 * @param mixed $value value
	 * @return bool
	 */
	private static function putInt16($fd, $value)
	{
		return fwrite($fd, pack('S', $value));
	}

	/**
	 * getInt32
	 * @param mixed $fd file descriptor
	 * @param int $n n
	 * @return bool
	 */
	private static function getInt32($fd, $n = 1)
	{
		$data = fread($fd, 4 * $n);
		$i = unpack("L$n", $data);
		if ($n == 1) {
			return (int) $i[1];
		} else {
			return array_merge($i);
		}
	}

	/**
	 * putint32
	 * @param mixed $fd file descriptor
	 * @param mixed $value value
	 * @return bool
	 */
	private static function putInt32($fd, $value)
	{
		return fwrite($fd, pack('L', $value));
	}

	/**
	 * putString
	 * @param mixed $fd file descriptor
	 * @param mixed $value value
	 * @param int $length length
	 * @return bool
	 */
	private static function putString($fd, $value, $length = 254)
	{
		$ret = fwrite($fd, pack('A' . $length, $value));
	}

	/**
	 * putRecord
	 * @param mixed $record record
	 * @return bool
	 */
	private function putRecord($record)
	{
		foreach ($this->fields as $i => &$field) {
			$value = $record[$i];
			// Number types are right aligned with spaces
			if ($field['type'] == 'N' || $field['type'] == 'F' && strlen($value) < $field['length']) {
				$value = str_repeat(' ', $field['length'] - strlen($value)) . $value;
			}
			self::putString($this->fd, $value, $field['length']);
		}
		return true;
	}
}

if (!function_exists('dbase_open')) {
	/**
	 * dbase_open
	 * @param string $filename filename
	 * @param int $mode mode
	 * @return DBase
	 */
	function dbase_open($filename, $mode)
	{
		return DBase::open($filename, $mode);
	}

	/**
	 * dbase_create
	 * @param string $filename filename
	 * @param array $fields fields
	 * @param int $type type
	 * @return DBase
	 */
	function dbase_create($filename, $fields, $type = DBASE_TYPE_DBASE)
	{
		return DBase::create($filename, $fields, $type);
	}

	/**
	 * dbase_close
	 * @param Resource $dbase_identifier dbase identifier
	 * @return bool
	 */
	function dbase_close($dbase_identifier)
	{
		return $dbase_identifier->close();
	}

	/**
	 * dbase_get_header_info
	 * @param Resource $dbase_identifier dbase identifier
	 * @return string
	 */
	function dbase_get_header_info($dbase_identifier)
	{
		return $dbase_identifier->get_header_info();
	}

	/**
	 * dbase_numfields
	 * @param Resource $dbase_identifier dbase identifier
	 * @return int
	 */
	function dbase_numfields($dbase_identifier)
	{
		$dbase_identifier->numfields();
	}

	/**
	 * dbase_numrecords
	 * @param Resource $dbase_identifier dbase identifier
	 * @return int
	 */
	function dbase_numrecords($dbase_identifier)
	{
		return $dbase_identifier->numrecords();
	}

	/**
	 * dbase_add_record
	 * @param Resource $dbase_identifier dbase identifier
	 * @param array $record record
	 * @return bool
	 */
	function dbase_add_record($dbase_identifier, $record)
	{
		return $dbase_identifier->add_record($record);
	}

	/**
	 * dbase_delete_record
	 * @param Resource $dbase_identifier dbase identifier
	 * @param int $record_number record number
	 * @return bool
	 */
	function dbase_delete_record($dbase_identifier, $record_number)
	{
		return $dbase_identifier->delete_record($record_number);
	}

	/**
	 * dbase_replace_record
	 * @param Resource $dbase_identifier dbase identifier
	 * @param array $record record
	 * @param int $record_number record number
	 * @return bool
	 */
	function dbase_replace_record($dbase_identifier, $record, $record_number)
	{
		return $dbase_identifier->replace_record($record, $record_number);
	}

	/**
	 * dbase_get_record
	 * @param Resource $dbase_identifier dbase identifier
	 * @param int $record_number record number
	 * @return bool
	 */
	function dbase_get_record($dbase_identifier, $record_number)
	{
		return $dbase_identifier->get_record($record_number);
	}

	/**
	 * dbase_get_record_with_names
	 * @param Resource $dbase_identifier dbase identifier
	 * @param int $record_number record number
	 * @return bool
	 */
	function dbase_get_record_with_names($dbase_identifier, $record_number)
	{
		return $dbase_identifier->get_record_with_names($record_number);
	}

	/**
	 * dbase_pack
	 * @param Resource $dbase_identifier dbase identifier
	 * @return bool
	 */
	function dbase_pack($dbase_identifier)
	{
		return $dbase_identifier->pack();
	}
}
