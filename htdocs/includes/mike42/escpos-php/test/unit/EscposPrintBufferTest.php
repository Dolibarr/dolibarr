<?php
/**
 * Example strings are pangrams using different character sets, and are
 * testing correct code-table switching.
 *
 * When printed, they should appear the same as in this source file.
 *
 * Many of these test strings are from:
 * - http://www.cl.cam.ac.uk/~mgk25/ucs/examples/quickbrown.txt
 * - http://clagnut.com/blog/2380/ (mirrored from the English Wikipedia)
 */
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\DummyPrintConnector;

class EscposPrintBufferTest extends PHPUnit_Framework_TestCase
{
    protected $buffer;
    protected $outputConnector;
    
    protected function setup()
    {
        $this -> outputConnector = new DummyPrintConnector();
        $printer = new Printer($this -> outputConnector);
        $this -> buffer = $printer -> getPrintBuffer();
    }
    
    protected function checkOutput($expected = null)
    {
        /* Check those output strings */
        $outp = $this -> outputConnector -> getData();
        if ($expected === null) {
            echo "\nOutput was:\n\"" . friendlyBinary($outp) . "\"\n";
        }
        $this -> assertEquals($expected, $outp);
    }

    protected function tearDown()
    {
        $this -> outputConnector -> finalize();
    }

    public function testRawTextNonprintable()
    {
        $this -> buffer -> writeTextRaw("Test" . Printer::ESC . "v1\n");
        $this -> checkOutput("\x1b@Test?v1\x0a"); // ASCII ESC character is substituted for '?'
    }

    public function testDanish()
    {
        $this -> buffer -> writeText("Quizdeltagerne spiste jordbær med fløde, mens cirkusklovnen Wolther spillede på xylofon.\n");
        $this -> checkOutput("\x1b@Quizdeltagerne spiste jordb\x91r med fl\x1bt\x02\x9bde, mens cirkusklovnen Wolther spillede p\x86 xylofon.\x0a");
    }

    public function testGerman()
    {
        $this -> buffer -> writeText("Falsches Üben von Xylophonmusik quält jeden größeren Zwerg.\n");
        $this -> checkOutput("\x1b@Falsches \x9aben von Xylophonmusik qu\x84lt jeden gr\x94\xe1eren Zwerg.\x0a");
    }

    public function testGreek()
    {
        $this -> buffer -> writeText("Ξεσκεπάζω την ψυχοφθόρα βδελυγμία");
        $this -> checkOutput("\x1b@\x1bt\x0e\x8d\x9c\xa9\xa1\x9c\xa7\xe1\x9d\xe0 \xab\x9e\xa4 \xaf\xac\xae\xa6\xad\x9f\xe6\xa8\x98 \x99\x9b\x9c\xa2\xac\x9a\xa3\xe5\x98");
    }

    public function testGreekWithDiacritics()
    {
        // This is a string which is known to be un-printable in ESC/POS (the grave-accented letters are not in any code page),
        // so we are checking the substitution '?' for unknown characters.
        $this -> buffer -> writeText("Γαζέες καὶ μυρτιὲς δὲν θὰ βρῶ πιὰ στὸ χρυσαφὶ ξέφωτο.\n");
        $this -> checkOutput("\x1b@\xe2\xe0\x1bt\x0e\x9d\xe2\x9c\xaa \xa1\x98? \xa3\xac\xa8\xab\xa0?\xaa \x9b?\xa4 \x9f? \x99\xa8? \xa7\xa0? \xa9\xab? \xae\xa8\xac\xa9\x98\xad? \xa5\xe2\xad\xe0\xab\xa6.\x0a");
    }

    public function testEnglish()
    {
        $this -> buffer -> writeText("The quick brown fox jumps over the lazy dog.\n");
        $this -> checkOutput("\x1b@The quick brown fox jumps over the lazy dog.\n");
    }

    public function testSpanish()
    {
        // This one does not require changing code-pages at all, so characters are just converted from Unicode to CP437.
        $this -> buffer -> writeText("El pingüino Wenceslao hizo kilómetros bajo exhaustiva lluvia y frío, añoraba a su querido cachorro.\n");
        $this -> checkOutput("\x1b@El ping\x81ino Wenceslao hizo kil\xa2metros bajo exhaustiva lluvia y fr\xa1o, a\xa4oraba a su querido cachorro.\x0a");
    }

    public function testFrench()
    {
        $this -> buffer -> writeText("Le cœur déçu mais l'âme plutôt naïve, Louÿs rêva de crapaüter en canoë au delà des îles, près du mälström où brûlent les novæ.\n");
        $this -> checkOutput("\x1b@Le c\x1bt\x10\x9cur d\xe9\xe7u mais l'\xe2me plut\xf4t na\xefve, Lou\xffs r\xeava de crapa\xfcter en cano\xeb au del\xe0 des \xeeles, pr\xe8s du m\xe4lstr\xf6m o\xf9 br\xfblent les nov\xe6.\x0a");
    }

    public function testIrishGaelic()
    {
        // Note that some letters with diacritics cannot be printed for Irish Gaelic text, so text may need to be simplified.
        $this -> buffer -> writeText("D'fhuascail Íosa, Úrmhac na hÓighe Beannaithe, pór Éava agus Ádhaimh.\n");
        $this -> checkOutput("\x1b@D'fhuascail \x1bt\x02\xd6osa, \xe9rmhac na h\xe0ighe Beannaithe, p\xa2r \x90ava agus \xb5dhaimh.\x0a");
    }

    public function testHungarian()
    {
        $this -> buffer -> writeText("Árvíztűrő tükörfúrógép.\n");
        $this -> checkOutput("\x1b@\x1bt\x02\xb5rv\xa1zt\x1bt\x12\xfbr\x8b t\x81k\x94rf\xa3r\xa2g\x82p.\x0a");
    }
    
    public function testIcelandic()
    {
        $this -> buffer -> writeText("Kæmi ný öxi hér ykist þjófum nú bæði víl og ádrepa.");
        $this -> checkOutput("\x1b@K\x91mi n\x1bt\x02\xec \x94xi h\x82r ykist \xe7j\xa2fum n\xa3 b\x91\xd0i v\xa1l og \xa0drepa.");
    }

    public function testJapaneseHiragana()
    {
        $this -> markTestIncomplete("Non-ASCII character sets not yet supported.");
        $this -> buffer -> writeText(implode("\n", array("いろはにほへとちりぬるを",  " わかよたれそつねならむ", "うゐのおくやまけふこえて",  "あさきゆめみしゑひもせす")) . "\n");
        $this -> checkOutput();
    }

    public function testJapaneseKatakana()
    {
        $this -> markTestIncomplete("Non-ASCII character sets not yet supported.");
        $this -> buffer -> writeText(implode("\n", array("イロハニホヘト チリヌルヲ ワカヨタレソ ツネナラム", "ウヰノオクヤマ ケフコエテ アサキユメミシ ヱヒモセスン")) . "\n");
        $this -> checkOutput("\x1b@\x1bt\x01\xb2\xdb\xca\xc6\xce\xcd\xc4 \xc1\xd8\xc7\xd9\xa6 \xdc\xb6\xd6\xc0\xda\xbf \xc2\xc8\xc5\xd7\xd1\x0a\xb3\xb2\xc9\xb5\xb8\xd4\xcf \xb9\xcc\xba\xb4\xc3 \xb1\xbb\xb7\xd5\xd2\xd0\xbc \xb4\xcb\xd3\xbe\xbd\xdd\x0a");
    }

    public function testJapaneseKataKanaHalfWidth()
    {
        $this -> buffer -> writeText(implode("\n", array("ｲﾛﾊﾆﾎﾍﾄ ﾁﾘﾇﾙｦ ﾜｶﾖﾀﾚｿ ﾂﾈﾅﾗﾑ", "ｳｲﾉｵｸﾔﾏ ｹﾌｺｴﾃ ｱｻｷﾕﾒﾐｼ ｴﾋﾓｾｽﾝ")) . "\n");
        $this -> checkOutput("\x1b@\x1bt\x01\xb2\xdb\xca\xc6\xce\xcd\xc4 \xc1\xd8\xc7\xd9\xa6 \xdc\xb6\xd6\xc0\xda\xbf \xc2\xc8\xc5\xd7\xd1\x0a\xb3\xb2\xc9\xb5\xb8\xd4\xcf \xb9\xcc\xba\xb4\xc3 \xb1\xbb\xb7\xd5\xd2\xd0\xbc \xb4\xcb\xd3\xbe\xbd\xdd\x0a");
    }
    
    public function testLatvian()
    {
        $this -> buffer -> writeText("Glāžšķūņa rūķīši dzērumā čiepj Baha koncertflīģeļu vākus.\n");
        $this -> checkOutput("\x1b@Gl\x1bt!\x83\xd8\xd5\xe9\xd7\xeca r\xd7\xe9\x8c\xd5i dz\x89rum\x83 \xd1iepj Baha koncertfl\x8c\x85e\xebu v\x83kus.\x0a");
    }

    public function testPolish()
    {
        $this -> buffer -> writeText("Pchnąć w tę łódź jeża lub ośm skrzyń fig.\n");
        $this -> checkOutput("\x1b@Pchn\x1bt\x12\xa5\x86 w t\xa9 \x88\xa2d\xab je\xbea lub o\x98m skrzy\xe4 fig.\x0a");
    }

    public function testRussian()
    {
        $this -> buffer -> writeText("В чащах юга жил бы цитрус? Да, но фальшивый экземпляр!\n");
        $this -> checkOutput("\x1b@\x1bt\x11\x82 \xe7\xa0\xe9\xa0\xe5 \xee\xa3\xa0 \xa6\xa8\xab \xa1\xeb \xe6\xa8\xe2\xe0\xe3\xe1? \x84\xa0, \xad\xae \xe4\xa0\xab\xec\xe8\xa8\xa2\xeb\xa9 \xed\xaa\xa7\xa5\xac\xaf\xab\xef\xe0!\x0a");
    }

    public function testThai()
    {
        $this -> markTestIncomplete("Non-ASCII character sets not yet supported.");
        $this -> buffer -> writeText("นายสังฆภัณฑ์ เฮงพิทักษ์ฝั่ง ผู้เฒ่าซึ่งมีอาชีพเป็นฅนขายฃวด ถูกตำรวจปฏิบัติการจับฟ้องศาล ฐานลักนาฬิกาคุณหญิงฉัตรชฎา ฌานสมาธิ\n"); // Quotation from Wikipedia
        $this -> checkOutput();
    }

    public function testTurkish()
    {
        $this -> buffer -> writeText("Pijamalı hasta, yağız şoföre çabucak güvendi.\n");
        $this -> checkOutput("\x1b@Pijamal\x1bt\x02\xd5 hasta, ya\x1bt\x0d\xa7\x8dz \x9fof\x94re \x87abucak g\x81vendi.\x0a");
    }
    
    public function testArabic()
    {
        $this -> markTestIncomplete("Right-to-left text not yet supported.");
        $this -> buffer -> writeText("صِف خَلقَ خَودِ كَمِثلِ الشَمسِ إِذ بَزَغَت — يَحظى الضَجيعُ بِها نَجلاءَ مِعطارِ" . "\n"); // Quotation from Wikipedia
        $this -> checkOutput();
    }
    
    public function testHebrew()
    {
        // RTL text is more complex than the above.
        $this -> markTestIncomplete("Right-to-left text not yet supported.");
        $this -> buffer -> writeText("דג סקרן שט בים מאוכזב ולפתע מצא לו חברה איך הקליטה" . "\n");
        $this -> checkOutput();
    }
    
    public function testVietnamese() {
        $this -> buffer -> writeText("Tiếng Việt, còn gọi tiếng Việt Nam hay Việt ngữ, là ngôn ngữ của người Việt (người Kinh) và là ngôn ngữ chính thức tại Việt Nam.\n");
        $this -> checkOutput("\x1b@Ti\x1bt\x1e\xd5ng Vi\xd6t, c\xdfn g\xe4i ti\xd5ng Vi\xd6t Nam hay Vi\xd6t ng\xf7, l\xb5 ng\xabn ng\xf7 c\xf1a ng\xad\xeai Vi\xd6t (ng\xad\xeai Kinh) v\xb5 l\xb5 ng\xabn ng\xf7 ch\xddnh th\xf8c t\xb9i Vi\xd6t Nam.\x0a");
    }

    public function testWindowsLineEndings() {
        $this -> buffer -> writeText("Hello World!\r\n");
        $this -> checkOutput("\x1b@Hello World!\x0a");
    }

    public function testWindowsLineEndingsRaw() {
        $this -> buffer -> writeTextRaw("Hello World!\r\n");
        $this -> checkOutput("\x1b@Hello World!\x0a");
    }
}
