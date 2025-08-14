<?php
use Mike42\GfxPhp\Image;
use Mike42\GfxPhp\RgbRasterImage;
use PHPUnit\Framework\TestCase;
use Mike42\GfxPhp\BlackAndWhiteRasterImage;
use Mike42\GfxPhp\GrayscaleRasterImage;
use Mike42\GfxPhp\IndexedRasterImage;

/**
 * Simple check that files in the PyGIF test suite can be read, and have the expected dimensions.
 */
class GifsuiteTest extends TestCase
{
    function loadImage(string $filename) {
        return Image::fromFile(__DIR__ . "/../resources/pygif/$filename");
    }

    function test_255_codes() {
        $img = $this -> loadImage("255-codes.gif");
        $this -> assertEquals(100, $img -> getWidth());
        $this -> assertEquals(100, $img -> getHeight());
    }

    function test_4095_codes_clear() {
        $img = $this -> loadImage("4095-codes-clear.gif");
        $this -> assertEquals(100, $img -> getWidth());
        $this -> assertEquals(100, $img -> getHeight());
    }

    function test_4095_codes() {
        $img = $this -> loadImage("4095-codes.gif");
        $this -> assertEquals(100, $img -> getWidth());
        $this -> assertEquals(100, $img -> getHeight());
    }

    function test_all_blues() {
        $img = $this -> loadImage("all-blues.gif");
        $this -> assertEquals(16, $img -> getWidth());
        $this -> assertEquals(16, $img -> getHeight());
    }

    function test_all_greens() {
        $img = $this -> loadImage("all-greens.gif");
        $this -> assertEquals(16, $img -> getWidth());
        $this -> assertEquals(16, $img -> getHeight());
    }

    function test_all_reds() {
        $img = $this -> loadImage("all-reds.gif");
        $this -> assertEquals(16, $img -> getWidth());
        $this -> assertEquals(16, $img -> getHeight());
    }

    function test_animation() {
        $img = $this -> loadImage("animation.gif");
        $this -> assertEquals(2, $img -> getWidth());
        $this -> assertEquals(2, $img -> getHeight());
    }

    function test_animation_multi_image_explicit_zero_delay() {
        $img = $this -> loadImage("animation-multi-image-explicit-zero-delay.gif");
        $this -> assertEquals(2, $img -> getWidth());
        $this -> assertEquals(2, $img -> getHeight());
    }

    function test_animation_multi_image() {
        $img = $this -> loadImage("animation-multi-image.gif");
        $this -> assertEquals(2, $img -> getWidth());
        $this -> assertEquals(2, $img -> getHeight());
    }

    function test_animation_no_delays() {
        $img = $this -> loadImage("animation-no-delays.gif");
        $this -> assertEquals(2, $img -> getWidth());
        $this -> assertEquals(2, $img -> getHeight());
    }

    function test_animation_speed() {
        $img = $this -> loadImage("animation-speed.gif");
        $this -> assertEquals(2, $img -> getWidth());
        $this -> assertEquals(2, $img -> getHeight());
    }

    function test_animation_zero_delays() {
        $img = $this -> loadImage("animation-zero-delays.gif");
        $this -> assertEquals(2, $img -> getWidth());
        $this -> assertEquals(2, $img -> getHeight());
    }

    function test_comment() {
        $img = $this -> loadImage("comment.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_depth1() {
        $img = $this -> loadImage("depth1.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_depth2() {
        $img = $this -> loadImage("depth2.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_depth3() {
        $img = $this -> loadImage("depth3.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_depth4() {
        $img = $this -> loadImage("depth4.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_depth5() {
        $img = $this -> loadImage("depth5.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_depth6() {
        $img = $this -> loadImage("depth6.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_depth7() {
        $img = $this -> loadImage("depth7.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_depth8() {
        $img = $this -> loadImage("depth8.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_disabled_transparent() {
        $img = $this -> loadImage("disabled-transparent.gif");
        $this -> assertEquals(2, $img -> getWidth());
        $this -> assertEquals(2, $img -> getHeight());
    }

    function test_dispose_keep() {
        $img = $this -> loadImage("dispose-keep.gif");
        $this -> assertEquals(2, $img -> getWidth());
        $this -> assertEquals(2, $img -> getHeight());
    }

    function test_dispose_none() {
        $img = $this -> loadImage("dispose-none.gif");
        $this -> assertEquals(2, $img -> getWidth());
        $this -> assertEquals(2, $img -> getHeight());
    }

    function test_dispose_restore_background() {
        $img = $this -> loadImage("dispose-restore-background.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_dispose_restore_previous() {
        $img = $this -> loadImage("dispose-restore-previous.gif");
        $this -> assertEquals(2, $img -> getWidth());
        $this -> assertEquals(2, $img -> getHeight());
    }

    function test_double_clears() {
        $img = $this -> loadImage("double-clears.gif");
        $this -> assertEquals(8, $img -> getWidth());
        $this -> assertEquals(8, $img -> getHeight());
    }

    function test_extra_data() {
        $img = $this -> loadImage("extra-data.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_extra_pixels() {
        $img = $this -> loadImage("extra-pixels.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_four_colors() {
        $img = $this -> loadImage("four-colors.gif");
        $this -> assertEquals(2, $img -> getWidth());
        $this -> assertEquals(2, $img -> getHeight());
    }

    function test_gif87a_animation() {
        $img = $this -> loadImage("gif87a-animation.gif");
        $this -> assertEquals(2, $img -> getWidth());
        $this -> assertEquals(2, $img -> getHeight());
    }

    function test_gif87a() {
        $img = $this -> loadImage("gif87a.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_high_color() {
        $img = $this -> loadImage("high-color.gif");
        $this -> assertEquals(16, $img -> getWidth());
        $this -> assertEquals(16, $img -> getHeight());
    }

    function test_icc_color_profile_empty() {
        $img = $this -> loadImage("icc-color-profile-empty.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_icc_color_profile() {
        $img = $this -> loadImage("icc-color-profile.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_image_inside_bg() {
        $img = $this -> loadImage("image-inside-bg.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_image_outside_bg() {
        $img = $this -> loadImage("image-outside-bg.gif");
        $this -> assertEquals(2, $img -> getWidth());
        $this -> assertEquals(2, $img -> getHeight());
    }

    function test_image_overlap_bg() {
        $img = $this -> loadImage("image-overlap-bg.gif");
        $this -> assertEquals(2, $img -> getWidth());
        $this -> assertEquals(2, $img -> getHeight());
    }

    function test_images_combine() {
        $img = $this -> loadImage("images-combine.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_images_overlap() {
        $img = $this -> loadImage("images-overlap.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_image_zero_height() {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("image-zero-height.gif");
    }

    function test_image_zero_size() {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("image-zero-size.gif");
    }

    function test_image_zero_width() {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("image-zero-width.gif");
    }

    function test_interlace() {
        $img = $this -> loadImage("interlace.gif");
        $this -> assertEquals(16, $img -> getWidth());
        $this -> assertEquals(16, $img -> getHeight());
    }

    function test_invalid_ascii_comment() {
        $img = $this -> loadImage("invalid-ascii-comment.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_invalid_background() {
        $img = $this -> loadImage("invalid-background.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_invalid_code() {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("invalid-code.gif");
    }

    function test_invalid_colors() {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("invalid-colors.gif");
    }

    function test_invalid_transparent() {
        $img = $this -> loadImage("invalid-transparent.gif");
        $this -> assertEquals(2, $img -> getWidth());
        $this -> assertEquals(2, $img -> getHeight());
    }

    function test_invalid_utf8_comment() {
        $img = $this -> loadImage("invalid-utf8-comment.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_large_codes() {
        $img = $this -> loadImage("large-codes.gif");
        $this -> assertEquals(100, $img -> getWidth());
        $this -> assertEquals(100, $img -> getHeight());
    }

    function test_large_comment() {
        $img = $this -> loadImage("large-comment.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_local_color_table() {
        $img = $this -> loadImage("local-color-table.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_loop_animexts() {
        $img = $this -> loadImage("loop-animexts.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_loop_buffer() {
        $img = $this -> loadImage("loop-buffer.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_loop_buffer_max() {
        $img = $this -> loadImage("loop-buffer_max.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_loop_infinite() {
        $img = $this -> loadImage("loop-infinite.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_loop_max() {
        $img = $this -> loadImage("loop-max.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_loop_once() {
        $img = $this -> loadImage("loop-once.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_many_clears() {
        $img = $this -> loadImage("many-clears.gif");
        $this -> assertEquals(8, $img -> getWidth());
        $this -> assertEquals(8, $img -> getHeight());
    }

    function test_max_codes() {
        $img = $this -> loadImage("max-codes.gif");
        $this -> assertEquals(100, $img -> getWidth());
        $this -> assertEquals(100, $img -> getHeight());
    }

    function test_max_height() {
        $img = $this -> loadImage("max-height.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(65535, $img -> getHeight());
    }

    function test_max_size() {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("max-size.gif");
    }

    function test_max_width() {
        $img = $this -> loadImage("max-width.gif");
        $this -> assertEquals(65535, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_missing_pixels() {
        $img = $this -> loadImage("missing-pixels.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_no_clear_and_eoi() {
        $img = $this -> loadImage("no-clear-and-eoi.gif");
        $this -> assertEquals(2, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_no_clear() {
        $img = $this -> loadImage("no-clear.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_no_data() {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("no-data.gif");
    }

    function test_no_eoi() {
        $img = $this -> loadImage("no-eoi.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_no_global_color_table() {
        $img = $this -> loadImage("no-global-color-table.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_nul_application_extension() {
        $img = $this -> loadImage("nul-application-extension.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_nul_comment() {
        $img = $this -> loadImage("nul-comment.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_plain_text() {
        $img = $this -> loadImage("plain-text.gif");
        $this -> assertEquals(40, $img -> getWidth());
        $this -> assertEquals(8, $img -> getHeight());
    }

    function test_transparent() {
        $img = $this -> loadImage("transparent.gif");
        $this -> assertEquals(2, $img -> getWidth());
        $this -> assertEquals(2, $img -> getHeight());
    }

    function test_unknown_application_extension() {
        $img = $this -> loadImage("unknown-application-extension.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_unknown_extension() {
        $img = $this -> loadImage("unknown-extension.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_xmp_data_empty() {
        $img = $this -> loadImage("xmp-data-empty.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_xmp_data() {
        $img = $this -> loadImage("xmp-data.gif");
        $this -> assertEquals(1, $img -> getWidth());
        $this -> assertEquals(1, $img -> getHeight());
    }

    function test_zero_height() {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("zero-height.gif");
    }

    function test_zero_size() {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("zero-size.gif");
    }

    function test_zero_width() {
        $this -> expectException(Exception::class);
        $img = $this -> loadImage("zero-width.gif");
    }
}

