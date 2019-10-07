<?php




global $globalbbb;
$globalbbb = 'globalbbb';

function fbbb()
{
	return 'fbbb';
}

class Bbb
{
	const BBB='bbb';
	public function do()
	{
		global $globalaaa, $globalbbb;
		echo 'dobbb'."\n";
		$globalaaa.='+bbb';
		$globalbbb.='+bbb';
	}
}
