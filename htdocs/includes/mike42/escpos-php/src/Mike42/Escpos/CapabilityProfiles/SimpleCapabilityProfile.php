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

namespace Mike42\Escpos\CapabilityProfiles;

use Mike42\Escpos\CodePage;

/**
 * This capability profile is designed for non-Epson printers sold online. Without knowing
 * their character encoding table, only CP437 output is assumed, and graphics() calls will
 * be disabled, as it usually prints junk on these models.
 */
class SimpleCapabilityProfile extends DefaultCapabilityProfile
{
    /**
     * Map of numbers to supported code pages.
     */
    public function getSupportedCodePages()
    {
        /* Use only CP437 output */
        return array(0 => CodePage::CP437);
    }

    /**
     * True for graphics support, false if not supported.
     */
    public function getSupportsGraphics()
    {
        /* Ask the driver to use bitImage wherever possible instead of graphics */
        return false;
    }
}
