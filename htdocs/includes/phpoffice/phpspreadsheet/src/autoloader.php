<?php
spl_autoload_register(function ($class_name) {
	$preg_match = preg_match('/^PhpOffice\\\PhpSpreadsheet\\\/', $class_name);

	if (1 === $preg_match) {
		$class_name = preg_replace('/\\\/', '/', $class_name);
		$class_name = preg_replace('/^PhpOffice\\/PhpSpreadsheet\\//', '', $class_name);
		require_once __DIR__ . '/PhpSpreadsheet/' . $class_name . '.php';
	}
});
