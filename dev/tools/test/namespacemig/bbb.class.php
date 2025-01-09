<?php

global $globalbbb;
$globalbbb = 'globalbbb';

/**
 * fbbb
 *
 * @return string
 */
function fbbb()
{
	return 'fbbb';
}

/**
 * Class Bbb
 */
class Bbb
{
	const BBB='bbb';

	/**
	 * do
	 *
	 * @return void
	 */
	public function do()
	{
		global $globalaaa, $globalbbb;
		echo 'dobbb'."\n";
		$globalaaa.='+bbb';
		$globalbbb.='+bbb';
	}
}
