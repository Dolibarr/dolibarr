<?php
/* Copyright (C) 2026 Frédéric France      <frederic.france@free.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *      \file       test/phpunit/EmailSignatureLibTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test for the email signature quality scoring helper
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/emailsignature.lib.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class EmailSignatureLibTest extends CommonClassTest
{
	/**
	 * A well written plain-text signature with contact details and no image
	 * must score at the top of the range.
	 *
	 * @return void
	 */
	public function testPlainTextSignatureScoresExcellent()
	{
		$html = '<p>Frédéric France<br>Chief Technical Officer, Acme Corp<br>frederic.france@acme.example &mdash; +33 1 23 45 67 89<br><a href="https://acme.example">https://acme.example</a></p>';

		$res = dolCheckSignatureQuality($html);

		$this->assertGreaterThanOrEqual(85, $res['score'], 'Clean text signature should score >= 85, got '.$res['score']);
		$this->assertSame('excellent', $res['grade']);
		$this->assertSame('ok', $res['checks']['text_image_ratio']['status']);
	}

	/**
	 * A signature that is only a logo image with no text is a bad practice
	 * and must be graded "poor".
	 *
	 * @return void
	 */
	public function testImageOnlySignatureScoresPoor()
	{
		$html = '<img src="https://acme.example/logo.png">';

		$res = dolCheckSignatureQuality($html);

		$this->assertSame('poor', $res['grade'], 'Image-only signature must be poor, score was '.$res['score']);
		$this->assertSame('error', $res['checks']['text_image_ratio']['status']);
	}

	/**
	 * An empty signature is reported as poor with a content error, not as a
	 * perfect score.
	 *
	 * @return void
	 */
	public function testEmptySignatureIsPoor()
	{
		$res = dolCheckSignatureQuality('');

		$this->assertSame(0, $res['score']);
		$this->assertSame('poor', $res['grade']);
		$this->assertSame('error', $res['checks']['content']['status']);
	}

	/**
	 * A few gibberish characters is not a signature and must never be graded well.
	 *
	 * @return void
	 */
	public function testGibberishShortSignatureIsPoor()
	{
		$res = dolCheckSignatureQuality('azerty');

		$this->assertSame('poor', $res['grade'], 'Gibberish signature must be poor, score was '.$res['score']);
		$this->assertLessThanOrEqual(35, $res['score']);
		$this->assertSame('error', $res['checks']['content']['status']);
	}

	/**
	 * A single long token with no real words is still insufficient content.
	 *
	 * @return void
	 */
	public function testSingleWordSignatureIsPoor()
	{
		$res = dolCheckSignatureQuality(str_repeat('a', 40));

		$this->assertSame('poor', $res['grade']);
		$this->assertSame('error', $res['checks']['content']['status']);
	}

	/**
	 * A short but genuine signature (name, role, reachable email) stays acceptable.
	 *
	 * @return void
	 */
	public function testShortGenuineSignatureIsAcceptable()
	{
		$res = dolCheckSignatureQuality('<p>Jean Dupont, CEO, Acme Corp &mdash; jean.dupont@acme.example</p>');

		$this->assertContains($res['grade'], array('excellent', 'good'), 'grade was '.$res['grade'].' ('.$res['score'].')');
		$this->assertSame('ok', $res['checks']['content']['status']);
	}

	/**
	 * A <script> tag in a signature is a hard failure.
	 *
	 * @return void
	 */
	public function testScriptTagIsHeavilyPenalised()
	{
		$html = '<p>Kind regards,<br>the Acme support team, support@acme.example</p><script>alert(1)</script>';

		$res = dolCheckSignatureQuality($html);

		$this->assertSame('error', $res['checks']['script_tag']['status']);
		$this->assertLessThanOrEqual(40, $res['score']);
		$this->assertNotContains($res['grade'], array('excellent', 'good'));
	}

	/**
	 * Images without an alt attribute degrade badly when images are blocked,
	 * so a warning is raised.
	 *
	 * @return void
	 */
	public function testMissingImageAltRaisesWarning()
	{
		$html = '<img src="https://acme.example/logo.png"> <span>Frédéric France, CTO, Acme Corp, frederic.france@acme.example, +33 1 23 45 67 89</span>';

		$res = dolCheckSignatureQuality($html);

		$this->assertSame('warning', $res['checks']['image_alt']['status']);
	}

	/**
	 * A relative or non-https image URL breaks outside of Dolibarr and is an error.
	 *
	 * @return void
	 */
	public function testNonAbsoluteImageUrlIsError()
	{
		$html = '<img src="/viewimage.php?modulepart=mycompany&file=logos/logo.png" alt="Acme"> <span>Frédéric France, CTO, frederic.france@acme.example</span>';

		$res = dolCheckSignatureQuality($html);

		$this->assertSame('error', $res['checks']['non_absolute_url']['status']);
	}

	/**
	 * The score is always clamped to the 0..100 range even for a signature
	 * that trips every rule at once.
	 *
	 * @return void
	 */
	public function testScoreIsClampedToRange()
	{
		$html = '<script>x</script>'
			.str_repeat('<img src="/a.png">', 8);

		$res = dolCheckSignatureQuality($html);

		$this->assertGreaterThanOrEqual(0, $res['score']);
		$this->assertLessThanOrEqual(100, $res['score']);
		$this->assertSame('poor', $res['grade']);
	}

	/**
	 * Every documented check code is present in the result so the UI can list them.
	 *
	 * @return void
	 */
	public function testResultAlwaysCarriesAllCheckCodes()
	{
		$res = dolCheckSignatureQuality('<p>Frédéric France, frederic.france@acme.example</p>');

		$expected = array(
			'content', 'script_tag', 'style_block', 'non_absolute_url', 'text_image_ratio',
			'image_count', 'image_alt', 'image_weight', 'link_count', 'text_length',
			'contact_info', 'fixed_width',
		);
		foreach ($expected as $code) {
			$this->assertArrayHasKey($code, $res['checks'], 'Missing check code '.$code);
			$this->assertContains($res['checks'][$code]['status'], array('ok', 'warning', 'error'));
		}
	}

	/**
	 * Threshold constants are honoured: lowering the max image count turns an
	 * otherwise fine signature into a warning.
	 *
	 * @return void
	 */
	public function testMaxImageThresholdIsConfigurable()
	{
		global $conf;

		$html = '<img src="https://acme.example/a.png" alt="a"> <img src="https://acme.example/b.png" alt="b"> <span>Frédéric France, CTO, Acme Corp, frederic.france@acme.example, +33 1 23 45 67 89, https://acme.example</span>';

		$conf->global->SIGNATURE_QUALITY_MAX_IMG = 5;
		$resok = dolCheckSignatureQuality($html);
		$this->assertSame('ok', $resok['checks']['image_count']['status']);

		$conf->global->SIGNATURE_QUALITY_MAX_IMG = 1;
		$reswarn = dolCheckSignatureQuality($html);
		$this->assertNotSame('ok', $reswarn['checks']['image_count']['status']);

		unset($conf->global->SIGNATURE_QUALITY_MAX_IMG);
	}
}
