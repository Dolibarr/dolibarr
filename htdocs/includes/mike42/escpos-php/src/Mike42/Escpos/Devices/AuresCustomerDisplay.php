<?php
/**
 * This file is part of escpos-php: PHP receipt printer library for use with
 * ESC/POS-compatible thermal and impact printers.
 *
 * Copyright (c) 2014-18 Michael Billington < michael.billington@gmail.com >,
 * incorporating modifications by others. See CONTRIBUTORS.md for a full list.
 *
 * This software is distributed under the terms of the MIT license. See LICENSE.md
 * for details.
 */

namespace Mike42\Escpos\Devices;

use Mike42\Escpos\Printer;

/**
 * A class for sending ESC/POS-like code to an Aures customer display.
 * The display has some features that printers do not, such as an ability to "clear" the screen.
 */
class AuresCustomerDisplay extends Printer
{

    /**
     * Indicates that the text should wrap and type over
     * existing text on the screen, rather than scroll.
     */
    const TEXT_OVERWRITE = 1;

    /**
     * Indicates that overflowing text should cause the
     * display to scroll vertically, like a computer terminal.
     */
    const TEXT_VERTICAL_SCROLL = 2;

    /**
     * Indicates that overflowing text should cause the
     * display to scroll horizontally, like a news ticker.
     */
    const TEXT_HORIZONTAL_SCROLL = 3;

    /**
     *
     * {@inheritdoc}
     *
     * @see \Mike42\Escpos\Printer::initialize()
     */
    public function initialize()
    {
        // Select ESC/POS mode first
        $this->selectEscposMode();
        parent::initialize();
        // ESC @ does not reset character table on this printer
        $this->selectCharacterTable(0);
        // Default to horizontal scroll mode. Behaves most like a printer.
        $this->selectTextScrollMode(AuresCustomerDisplay::TEXT_VERTICAL_SCROLL);
    }

    /**
     * Selects ESC/POS mode.
     *
     * This device supports other modes, which are not used.
     */
    protected function selectEscposMode()
    {
        $this->connector->write("\x02\x05\x43\x31\x03");
    }

    /**
     *
     * @param int $mode
     *            The text scroll mode to use. One of
     *            AuresCustomerDisplay::TEXT_OVERWRITE,
     *            AuresCustomerDisplay::TEXT_VERTICAL_SCROLL or
     *            AuresCustomerDisplay::TEXT_HORIZONTAL_SCROLL
     */
    public function selectTextScrollMode(int $mode = AuresCustomerDisplay::TEXT_VERTICAL_SCROLL)
    {
        self::validateInteger($mode, 1, 3, __FUNCTION__);
        $this->connector->write("\x1F" . chr($mode));
    }

    /**
     * Clear the display.
     */
    public function clear()
    {
        $this->connector->write("\x0c");
    }

    /**
     * Instruct the display to show the firmware version.
     */
    public function showFirmwareVersion()
    {
        $this->connector->write("\x02\x05\x56\x01\x03");
    }

    /**
     * Instruct the display to begin a self-test/demo sequence.
     */
    public function selfTest()
    {
        $this->connector->write("\x02\x05\x44\x08\x03");
    }

    /**
     * Instruct the display to show a pre-loaded logo.
     *
     * Note that this driver is not capable of uploading a
     * logo, but that the vendor supplies software
     * which has this function.
     */
    public function showLogo()
    {
        $this->connector->write("\x02\xFC\x55\xAA\x55\xAA");
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \Mike42\Escpos\Printer::text()
     */
    public function text(string $str)
    {
        // Need to intercept line-feeds, since "\n" is insufficient on this device.
        foreach (explode("\n", $str) as $id => $line) {
            if ($id > 0) {
                $this->feed();
            }
            parent::text($line);
        }
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \Mike42\Escpos\Printer::feed()
     */
    public function feed(int $lines = 1)
    {
        self::validateInteger($lines, 1, 255, __FUNCTION__);
        for ($i = 0; $i < $lines; $i ++) {
            $this->connector->write("\r\n");
        }
    }
}
