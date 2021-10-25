<?php
/**
 * This file is part of escpos-php: PHP receipt printer library for use with
 * ESC/POS-compatible thermal and impact printers.
 *
 * Copyright (c) 2014-16 Michael Billington < michael.billington@gmail.com >,
 * incorporating modifications by others. See CONTRIBUTORS.md for a full list.
 *
 * This software is distributed under the terms of the MIT license. See LICENSE.md
 * for details.
 */

namespace Mike42\Escpos\PrintBuffers;

use Mike42\Escpos\Printer;

/**
 * Print buffers manage newlines and character encoding for the target printer.
 * They are used as a swappable component: text or image-based output.
 *
 * - Text output (EscposPrintBuffer) is the fast default, and is recommended for
 *   most people, as the text output can be more directly manipulated by ESC/POS
 *   commands.
 * - Image output (ImagePrintBuffer) is designed to accept more encodings than the
 *   physical printer supports, by rendering the text to small images on-the-fly.
 *   This takes a lot more CPU than sending text, but is necessary for some users.
 * - If your use case fits outside these, then a further speed/flexibility trade-off
 *   can be made by printing directly from generated HTML or PDF.
 */
interface PrintBuffer
{
    /**
     * Cause the buffer to send any partial input and wait on a newline.
     * If the printer is already on a new line, this does nothing.
     */
    public function flush();

    /**
     * Used by Escpos to check if a printer is set.
     */
    public function getPrinter();

    /**
     * Used by Escpos to hook up one-to-one link between buffers and printers.
     *
     * @param Printer|null $printer New printer
     */
    public function setPrinter(Printer $printer = null);

    /**
     * Accept UTF-8 text for printing.
     *
     * @param string $text Text to print
     */
    public function writeText($text);

    /**
     * Accept 8-bit text in the current encoding and add it to the buffer.
     *
     * @param string $text Text to print, already the target encoding.
     */
    public function writeTextRaw($text);
}
