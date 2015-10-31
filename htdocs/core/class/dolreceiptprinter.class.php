<?php
/*
 * Copyright (C) 2015       Frederic France     <frederic.france@free.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *  \file           htdocs/core/class/dolreceiptprinter.class.php
 *  \brief          Print receipt ticket on various printer
 */

/*
 * codes for ticket template
 *
 * <align_left>                                    Left align text
 * <align_center>                                  Center text
 * <align_right>                                   Right align text
 * <use_font_a>                                    Use font A of printer
 * <use_font_b>                                    Use font B of printer
 * <use_font_c>                                    Use font C of printer
 * <bold> </bold>                                  Text Bold
 * <double_height> </double_height>                Text double height
 * <double_width> </double_width>                  Text double width
 * <underline> </underline>                        Underline text
 * <underline_2dots> </underline>                  Underline with double line
 * <emphasized> </emphasized>                      Emphasized text
 * <switch_colors> </switch_colors>                Print in white on black
 * <print_barcode>                                 Print barcode
 * <print_barcode_customer_id>                     Print barcode customer id
 * <set_print_width_57>                            Ticket print width of 57mm
 * <cut_paper_full>                                Cut ticket completely
 * <cut_paper_partial>                             Cut ticket partially
 * <open_drawer>                                   Open cash drawer
 * <activate_buzzer>                               Activate buzzer
 * 
 * Code which can be placed everywhere
 * <print_qrcode>                                  Print QR Code
 * <print_date>                                    Print date (format : AAAA-MM-DD)
 * <print_date_time>                               Print date and time (format AAAA-MM-DD HH:MM:SS)
 * <print_year>                                    Print Year
 * <print_month_letters>                           Print month in letters (example : november)
 * <print_month>                                   Print month number
 * <print_day>                                     Print day number
 * <print_table>                                   Print table number
 * <print_cutlery>                                 Print cutlery number
 * <print_payment>                                 Print payment method
 * <print_logo>                                    Print logo stored on printer. Example : <print_logo>32|32
 * <print_logo_deprecated>                         Print logo stored on printer. Must be followed by logo code. For old printers.
 * <print_num_order>                               Print order number
 * <print_num_order_unique>                        Print order number after validation
 * <print_customer_first_name>                     Print customer firstname
 * <print_customer_last_name>                      Print customer name
 * <print_customer_mail>                           Print customer mail
 * <print_customer_telephone>                      Print customer phone
 * <print_customer_mobile>                         Print customer mobile
 * <print_customer_skype>                          Print customer skype
 * <print_customer_tax_number>                     Print customer VAT number
 * <print_customer_account_balance>                Print customer account balance
 * <print_vendor_last_name>                        Print vendor name
 * <print_vendor_first_name>                       Print vendor firstname
 * <print_customer_points>                         Print customer points
 * <print_order_points>                            Print number of points for this order
 *
 * Conditional code at line start (if…then Print)
 * <print_if_customer>                             Print the line IF a customer is affected to the order
 * <print_if_vendor>                               Print the line IF a vendor is affected to the order
 * <print_if_happy_hour>                           Print the line IF Happy Hour
 * <print_if_num_order_unique>                     Print the line IF order is validated
 * <print_if_customer_points>                      Print the line IF customer points > 0
 * <print_if_order_points>                         Print the line IF points of the order > 0
 * <print_if_customer_tax_number>                  Print the line IF customer has vat number
 * <print_if_customer_account_balance_positive>    Print the line IF customer balance > 0
 *
 */

/**
 * Class to manage Receipt Printers
 */
class dolReceiptPrinter
{
    var $db;
    var $error;
    var $errors;



    /**
     * Constructor
     *
     * @param   DoliDB      $db         database
     */
    function __construct($db)
    {
        $this->db=$db;
    }


}
