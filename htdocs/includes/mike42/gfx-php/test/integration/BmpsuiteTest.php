<?php

use Mike42\GfxPhp\Image;
use PHPUnit\Framework\TestCase;

/**
 * Simple check that files in the BMP Suite can be read, and have the expected dimensions.
 */
class BmpsuiteTest extends TestCase
{
    function loadImage(string $filename)
    {
        return Image::fromFile(__DIR__ . "/../resources/bmpsuite/$filename");
    }

    function test_badbitcount()
    {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("b/badbitcount.bmp");
    }

    function test_badbitssize()
    {
        $img = $this -> loadImage("b/badbitssize.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_baddens1()
    {
        $img = $this -> loadImage("b/baddens1.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_baddens2()
    {
        $img = $this -> loadImage("b/baddens2.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_badfilesize()
    {
        $img = $this -> loadImage("b/badfilesize.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_badheadersize()
    {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("b/badheadersize.bmp");
    }

    function test_badpalettesize()
    {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("b/badpalettesize.bmp");
    }

    function test_badplanes()
    {
        $img = $this -> loadImage("b/badplanes.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_badrle()
    {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("b/badrle.bmp");
    }

    function test_badrle4()
    {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("b/badrle4.bmp");
    }

    function test_badrle4bis()
    {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("b/badrle4bis.bmp");
    }

    function test_badrle4ter()
    {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("b/badrle4ter.bmp");
    }

    function test_badrlebis()
    {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("b/badrlebis.bmp");
    }

    function test_badrleter()
    {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("b/badrleter.bmp");
    }

    function test_badwidth()
    {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("b/badwidth.bmp");
    }

    function test_pal8badindex()
    {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("b/pal8badindex.bmp");
    }

    function test_reallybig()
    {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("b/reallybig.bmp");
    }

    function test_rgb16_880()
    {
        $img = $this -> loadImage("b/rgb16-880.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_rletopdown()
    {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("b/rletopdown.bmp");
    }

    function test_shortfile()
    {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("b/shortfile.bmp");
    }

    function test_pal1()
    {
        $img = $this -> loadImage("g/pal1.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_pal1bg()
    {
        $img = $this -> loadImage("g/pal1bg.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_pal1wb()
    {
        $img = $this -> loadImage("g/pal1wb.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_pal4()
    {
        $img = $this -> loadImage("g/pal4.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_pal4gs()
    {
        $img = $this -> loadImage("g/pal4gs.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_pal4rle()
    {
        $img = $this -> loadImage("g/pal4rle.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_pal8_0()
    {
        $img = $this -> loadImage("g/pal8-0.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_pal8()
    {
        $img = $this -> loadImage("g/pal8.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_pal8gs()
    {
        $img = $this -> loadImage("g/pal8gs.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_pal8nonsquare()
    {
        $img = $this -> loadImage("g/pal8nonsquare.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(32, $img -> getHeight());
    }

    function test_pal8os2()
    {
        $img = $this -> loadImage("g/pal8os2.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_pal8rle()
    {
        $img = $this -> loadImage("g/pal8rle.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_pal8topdown()
    {
        $img = $this -> loadImage("g/pal8topdown.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_pal8v4()
    {
        $img = $this -> loadImage("g/pal8v4.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_pal8v5()
    {
        $img = $this -> loadImage("g/pal8v5.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_pal8w124()
    {
        $img = $this -> loadImage("g/pal8w124.bmp");
        $this -> assertEquals(124, $img -> getWidth());
        $this -> assertEquals(61, $img -> getHeight());
    }

    function test_pal8w125()
    {
        $img = $this -> loadImage("g/pal8w125.bmp");
        $this -> assertEquals(125, $img -> getWidth());
        $this -> assertEquals(62, $img -> getHeight());
    }

    function test_pal8w126()
    {
        $img = $this -> loadImage("g/pal8w126.bmp");
        $this -> assertEquals(126, $img -> getWidth());
        $this -> assertEquals(63, $img -> getHeight());
    }

    function test_rgb16_565()
    {
        $img = $this -> loadImage("g/rgb16-565.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_rgb16_565pal()
    {
        $img = $this -> loadImage("g/rgb16-565pal.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_rgb16()
    {
        $img = $this -> loadImage("g/rgb16.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_rgb16bfdef()
    {
        $img = $this -> loadImage("g/rgb16bfdef.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_rgb24()
    {
        $img = $this -> loadImage("g/rgb24.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_rgb24pal()
    {
        $img = $this -> loadImage("g/rgb24pal.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_rgb32()
    {
        $img = $this -> loadImage("g/rgb32.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_rgb32bf()
    {
        $img = $this -> loadImage("g/rgb32bf.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_rgb32bfdef()
    {
        $img = $this -> loadImage("g/rgb32bfdef.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_pal1huff()
    {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("q/pal1huff.bmp");
    }

    function test_pal1p1()
    {
        $img = $this -> loadImage("q/pal1p1.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_pal2()
    {
        $img = $this -> loadImage("q/pal2.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_pal2color()
    {
        $img = $this -> loadImage("q/pal2color.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_pal4rlecut()
    {
        $img = $this -> loadImage("q/pal4rlecut.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_pal4rletrns()
    {
        $img = $this -> loadImage("q/pal4rletrns.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_pal8offs()
    {
        $img = $this -> loadImage("q/pal8offs.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_pal8os2_hs()
    {
        $img = $this -> loadImage("q/pal8os2-hs.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_pal8os2_sz()
    {
        $img = $this -> loadImage("q/pal8os2-sz.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_pal8os2sp()
    {
        $img = $this -> loadImage("q/pal8os2sp.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_pal8os2v2_16()
    {
        $img = $this -> loadImage("q/pal8os2v2-16.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_pal8os2v2_40sz()
    {
        $img = $this -> loadImage("q/pal8os2v2-40sz.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_pal8os2v2_sz()
    {
        $img = $this -> loadImage("q/pal8os2v2-sz.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_pal8os2v2()
    {
        $img = $this -> loadImage("q/pal8os2v2.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_pal8oversizepal()
    {
        $img = $this -> loadImage("q/pal8oversizepal.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_pal8rlecut()
    {
        $img = $this -> loadImage("q/pal8rlecut.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_pal8rletrns()
    {
        $img = $this -> loadImage("q/pal8rletrns.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_rgb16_231()
    {
        $img = $this -> loadImage("q/rgb16-231.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_rgb16_3103()
    {
        $img = $this -> loadImage("q/rgb16-3103.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_rgb16faketrns()
    {
        $img = $this -> loadImage("q/rgb16faketrns.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_rgb24jpeg()
    {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("q/rgb24jpeg.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_rgb24largepal()
    {
        $img = $this -> loadImage("q/rgb24largepal.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_rgb24lprof()
    {
        $img = $this -> loadImage("q/rgb24lprof.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_rgb24png()
    {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("q/rgb24png.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_rgb24prof()
    {
        $img = $this -> loadImage("q/rgb24prof.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_rgb24prof2()
    {
        $img = $this -> loadImage("q/rgb24prof2.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_rgb32_111110()
    {
        $img = $this -> loadImage("q/rgb32-111110.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_rgb32_7187()
    {
        $img = $this -> loadImage("q/rgb32-7187.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_rgb32_xbgr()
    {
        $img = $this -> loadImage("q/rgb32-xbgr.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_rgb32fakealpha()
    {
        $img = $this -> loadImage("q/rgb32fakealpha.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_rgb32h52()
    {
        $img = $this -> loadImage("q/rgb32h52.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_rgba16_1924()
    {
        $img = $this -> loadImage("q/rgba16-1924.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_rgba16_4444()
    {
        $img = $this -> loadImage("q/rgba16-4444.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_rgba16_5551()
    {
        $img = $this -> loadImage("q/rgba16-5551.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_rgba32_1010102()
    {
        $img = $this -> loadImage("q/rgba32-1010102.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_rgba32_61754()
    {
        $img = $this -> loadImage("q/rgba32-61754.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_rgba32_81284()
    {
        $img = $this -> loadImage("q/rgba32-81284.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_rgba32()
    {
        $img = $this -> loadImage("q/rgba32.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_rgba32abf()
    {
        $img = $this -> loadImage("q/rgba32abf.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_rgba32h56()
    {
        $img = $this -> loadImage("q/rgba32h56.bmp");
        $this -> assertEquals(127, $img -> getWidth());
        $this -> assertEquals(64, $img -> getHeight());
    }

    function test_ba_bm()
    {
        // Different container format, not recognised as bitmap at all
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("x/ba-bm.bmp");
    }
}
