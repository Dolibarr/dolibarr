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
 *  \brief          Print receipt ticket on various ESC/POS printer
 */

/*
 * Tags for ticket template
 *
 * <dol_align_left>                                 Left align text
 * <dol_align_center>                               Center text
 * <dol_align_right>                                Right align text
 * <dol_use_font_a>                                 Use font A of printer
 * <dol_use_font_b>                                 Use font B of printer
 * <dol_use_font_c>                                 Use font C of printer
 * <dol_bold> </dol_bold>                           Text Bold
 * <dol_double_height> </dol_double_height>         Text double height
 * <dol_double_width> </dol_double_width>           Text double width
 * <dol_underline> </dol_underline>                 Underline text
 * <dol_underline_2dots> </dol_underline_2dots>     Underline with double line
 * <dol_emphasized> </dol_emphasized>               Emphasized text
 * <dol_switch_colors> </dol_switch_colors>         Print in white on black
 * <dol_print_barcode>                              Print barcode
 * <dol_print_barcode_customer_id>                  Print barcode customer id
 * <dol_set_print_width_57>                         Ticket print width of 57mm
 * <dol_cut_paper_full>                             Cut ticket completely
 * <dol_cut_paper_partial>                          Cut ticket partially
 * <dol_open_drawer>                                Open cash drawer
 * <dol_activate_buzzer>                            Activate buzzer
 * 
 * Code which can be placed everywhere
 * <dol_print_qrcode>                               Print QR Code
 * <dol_print_date>                                 Print date AAAA-MM-DD
 * <dol_print_date_time>                            Print date and time AAAA-MM-DD HH:MM:SS
 * <dol_print_year>                                 Print Year
 * <dol_print_month_letters>                        Print month in letters (example : november)
 * <dol_print_month>                                Print month number
 * <dol_print_day>                                  Print day number
 * <dol_print_day_letters>                          Print day number
 * <dol_print_table>                                Print table number (for restaurant, bar...)
 * <dol_print_cutlery>                              Print number of cutlery (for restaurant)
 * <dol_print_payment>                              Print payment method
 * <dol_print_logo>                                 Print logo stored on printer. Example : <print_logo>32|32
 * <dol_print_logo_old>                             Print logo stored on printer. Must be followed by logo code. For old printers.
 * <dol_print_order_lines>                          Print order lines
 * <dol_print_order_tax>                            Print order total tax
 * <dol_print_order_local_tax>                      Print order local tax
 * <dol_print_order_total>                          Print order total
 * <dol_print_order_number>                         Print order number
 * <dol_print_order_number_unique>                  Print order number after validation
 * <dol_print_customer_first_name>                  Print customer firstname
 * <dol_print_customer_last_name>                   Print customer name
 * <dol_print_customer_mail>                        Print customer mail
 * <dol_print_customer_telephone>                   Print customer phone
 * <dol_print_customer_mobile>                      Print customer mobile
 * <dol_print_customer_skype>                       Print customer skype
 * <dol_print_customer_tax_number>                  Print customer VAT number
 * <dol_print_customer_account_balance>             Print customer account balance
 * <dol_print_vendor_last_name>                     Print vendor name
 * <dol_print_vendor_first_name>                    Print vendor firstname
 * <dol_print_vendor_mail>                          Print vendor mail
 * <dol_print_customer_points>                      Print customer points
 * <dol_print_order_points>                         Print number of points for this order
 *
 * Conditional code at line start (if…then Print)
 * <dol_print_if_customer>                          Print the line IF a customer is affected to the order
 * <dol_print_if_vendor>                            Print the line IF a vendor is affected to the order
 * <dol_print_if_happy_hour>                        Print the line IF Happy Hour
 * <dol_print_if_num_order_unique>                  Print the line IF order is validated
 * <dol_print_if_customer_points>                   Print the line IF customer points > 0
 * <dol_print_if_order_points>                      Print the line IF points of the order > 0
 * <dol_print_if_customer_tax_number>               Print the line IF customer has vat number
 * <dol_print_if_customer_account_balance_positive> Print the line IF customer balance > 0
 *
 */

require_once DOL_DOCUMENT_ROOT .'/includes/escpos/Escpos.php';

 
/**
 * Class to manage Receipt Printers
 */
class dolReceiptPrinter extends Escpos
{
    const CONNECTOR_DUMMY = 1;
    const CONNECTOR_FILE_PRINT = 2;
    const CONNECTOR_NETWORK_PRINT = 3;
    const CONNECTOR_WINDOWS_PRINT = 4;
    //const CONNECTOR_JAVA = 5;
    var $db;
    var $tags;
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
        $this->tags = array(
            'dol_align_left',
            'dol_align_center',
            'dol_align_right',
            'dol_use_font_a',
            'dol_use_font_b',
            'dol_use_font_c',
            'dol_bold',
            '/dol_bold',
            'dol_double_height',
            '/dol_double_height',
            'dol_double_width',
            '/dol_double_width',
            'dol_underline',
            '/dol_underline',
            'dol_underline_2dots',
            '/dol_underline',
            'dol_emphasized',
            '/dol_emphasized',
            'dol_switch_colors',
            '/dol_switch_colors',
            'dol_print_barcode',
            'dol_print_barcode_customer_id',
            'dol_set_print_width_57',
            'dol_cut_paper_full',
            'dol_cut_paper_partial',
            'dol_open_drawer',
            'dol_activate_buzzer',
            'dol_print_qrcode',
            'dol_print_date',
            'dol_print_date_time',
            'dol_print_year',
            'dol_print_month_letters',
            'dol_print_month',
            'dol_print_day',
            'dol_print_day_letters',
            'dol_print_table',
            'dol_print_cutlery',
            'dol_print_payment',
            'dol_print_logo',
            'dol_print_logo_old',
            'dol_print_order_lines',
            'dol_print_order_tax',
            'dol_print_order_local_tax',
            'dol_print_order_total',
            'dol_print_order_number',
            'dol_print_order_number_unique',
            'dol_print_customer_first_name',
            'dol_print_customer_last_name',
            'dol_print_customer_mail',
            'dol_print_customer_telephone',
            'dol_print_customer_mobile',
            'dol_print_customer_skype',
            'dol_print_customer_tax_number',
            'dol_print_customer_account_balance',
            'dol_print_vendor_last_name',
            'dol_print_vendor_first_name',
            'dol_print_vendor_mail',
            'dol_print_customer_points',
            'dol_print_order_points',
            'dol_print_if_customer',
            'dol_print_if_vendor',
            'dol_print_if_happy_hour',
            'dol_print_if_num_order_unique',
            'dol_print_if_customer_points',
            'dol_print_if_order_points',
            'dol_print_if_customer_tax_number',
            'dol_print_if_customer_account_balance_positive',
        );

    }

    /**
     * list printers
     *
     * @return  int                     0 if OK; >0 if KO
     */
    function listPrinters()
    {
        global $conf;
        $error = 0;
        $line = 0;
        $sql = 'SELECT rowid, name, fk_type, parameter';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'printer_receipt';
        $sql.= ' WHERE entity = '.$conf->entity;
        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            while ($line < $num) {
                $obj[] = $this->db->fetch_array($resql);
                $line++;
            }
        } else {
            $error++;
            $this->errors[] = $this->db->lasterror;
        }
        $this->listprinters = $obj;
        return $error;
    }


    /**
     * List printers templates
     *
     * @return  int                     0 if OK; >0 if KO
     */
    function listPrintersTemplates()
    {
        global $conf;
        $error = 0;
        $line = 0;
        $sql = 'SELECT rowid, name, template';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'printer_receipt_template';
        $sql.= ' WHERE entity = '.$conf->entity;
        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            while ($line < $num) {
                $obj[] = $this->db->fetch_array($resql);
                $line++;
            }
        } else {
            $error++;
            $this->errors[] = $this->db->lasterror;
        }
        $this->listprinterstemplates = $obj;
        return $error;
    }


    /**
     *  Form to Select type printer
     *
     *  @param    string    $selected       Id printer type pre-selected
     *  @param    string    $htmlname       select html name
     *  @return  int                        0 if OK; >0 if KO
     */
    function selectTypePrinter($selected='', $htmlname='printertypeid')
    {
        global $langs;
        $error = 0;
        $html = '<select class="flat" name="'.$htmlname.'">';
        $html.= '<option value="1" '.($selected==1?'selected="selected"':'').'>'.$langs->trans('CONNECTOR_DUMMY').'</option>';
        $html.= '<option value="2" '.($selected==2?'selected="selected"':'').'>'.$langs->trans('CONNECTOR_FILE_PRINT').'</option>';
        $html.= '<option value="3" '.($selected==3?'selected="selected"':'').'>'.$langs->trans('CONNECTOR_NETWORK_PRINT').'</option>';
        $html.= '<option value="4" '.($selected==4?'selected="selected"':'').'>'.$langs->trans('CONNECTOR_WINDOWS_PRINT').'</option>';
        //$html.= '<option value="5" '.($selected==5?'selected="selected"':'').'>'.$langs->trans('CONNECTOR_JAVA').'</option>';
        $html.= '</select>';

        $this->resprint = $html;
        return $error;
    }

    /**
     *  Function to Add a printer in db
     *
     *  @param    string    $name           Printer name
     *  @param    int       $type           Printer type
     *  @param    string    $parameter      Printer parameter
     *  @return  int                        0 if OK; >0 if KO
     */
    function AddPrinter($name, $type, $parameter)
    {
        global $conf;
        $error = 0;
        $sql = 'INSERT INTO '.MAIN_DB_PREFIX.'printer_receipt';
        $sql.= ' (name, fk_type, parameter, entity)';
        $sql.= ' VALUES ("'.$this->db->escape($name).'", '.$type.', "'.$this->db->escape($parameter).'", '.$conf->entity.')';
        $resql = $this->db->query($sql);
        if (! $resql) {
            $error++;
            $this->errors[] = $this->db->lasterror;
        }
        return $error;
    }

    /**
     *  Function to Update a printer in db
     *
     *  @param    string    $name           Printer name
     *  @param    int       $type           Printer type
     *  @param    string    $parameter      Printer parameter
     *  @param    int       $printerid      Printer id
     *  @return  int                        0 if OK; >0 if KO
     */
    function UpdatePrinter($name, $type, $parameter, $printerid)
    {
        global $conf;
        $error = 0;
        $sql = 'UPDATE '.MAIN_DB_PREFIX.'printer_receipt';
        $sql.= ' SET name="'.$this->db->escape($name).'"';
        $sql.= ', fk_type='.$type;
        $sql.= ', parameter="'.$this->db->escape($parameter).'"';
        $sql.= ' WHERE rowid='.$printerid;
        $resql = $this->db->query($sql);
        if (! $resql) {
            $error++;
            $this->errors[] = $this->db->lasterror;
        }
        return $error;
    }

    /**
     *  Function to Delete a printer from db
     *
     *  @param    int       $printerid      Printer id
     *  @return  int                        0 if OK; >0 if KO
     */
    function DeletePrinter($printerid)
    {
        global $conf;
        $error = 0;
        $sql = 'DELETE FROM '.MAIN_DB_PREFIX.'printer_receipt';
        $sql.= ' WHERE rowid='.$printerid;
        $resql = $this->db->query($sql);
        if (! $resql) {
            $error++;
            $this->errors[] = $this->db->lasterror;
        }
        return $error;
    }

    /**
     *  Function to Update a printer template in db
     *
     *  @param    string    $name           Template name
     *  @param    int       $template       Template
     *  @param    int       $templateid     Template id
     *  @return   int                       0 if OK; >0 if KO
     */
    function UpdateTemplate($name, $template, $templateid)
    {
        global $conf;
        $error = 0;
        $sql = 'UPDATE '.MAIN_DB_PREFIX.'printer_receipt_template';
        $sql.= ' SET name="'.$this->db->escape($name).'"';
        $sql.= ', template="'.$this->db->escape($template).'"';
        $sql.= ' WHERE rowid='.$templateid;
        $resql = $this->db->query($sql);
        if (! $resql) {
            $error++;
            $this->errors[] = $this->db->lasterror;
        }
        return $error;
    }


    /**
     *  Function to Send Test page to Printer
     *
     *  @param    int       $printerid      Printer id
     *  @return  int                        0 if OK; >0 if KO
     */
    function SendTestToPrinter($printerid)
    {
        global $conf;
        $error = 0;
        $sql = 'SELECT rowid, name, fk_type, parameter';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'printer_receipt';
        $sql.= ' WHERE rowid = '.$printerid;
        $sql.= ' AND entity = '.$conf->entity;
        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_array($resql);
        } else {
            $error++;
            $this->errors[] = $this->db->lasterror;
        }
        //print '<pre>'.print_r($obj, true).'</pre>';
        if (! $error) {
            $parameter = $obj['parameter'];
            try {
                switch ($obj['fk_type']) {
                    case 1:
                        require_once DOL_DOCUMENT_ROOT .'/includes/escpos/src/DummyPrintConnector.php';
                        $connector = new DummyPrintConnector();
                        break;
                    case 2:
                        $connector = new FilePrintConnector($parameter);
                        break;
                    case 3:
                        $parameters = explode(':', $parameter);
                        $connector = new NetworkPrintConnector($parameters[0], $parameters[1]);
                        break;
                    case 4:
                        $connector = new WindowsPrintConnector($parameter);
                        break;
                    default:
                        $connector = 'CONNECTOR_UNKNOWN';
                        break;
                }
                $testprinter = new Escpos($connector);
                $img = new EscposImage(DOL_DOCUMENT_ROOT .'/theme/common/dolibarr_logo_bw.png');
                $testprinter -> graphics($img);
                $testprinter -> text("Hello World!\n");
                $testStr = "Testing 123";
                $testprinter -> qrCode($testStr);
                $testprinter -> text("Most simple example\n");
                $testprinter -> feed();
                $testprinter -> cut();
                //print '<pre>'.print_r($connector, true).'</pre>';
                $testprinter -> close();

                //print '<pre>'.print_r($connector, true).'</pre>';
                //print '<pre>'.print_r($testprinter, true).'</pre>';
            } catch (Exception $e) {
                $this->errors[] = $e->getMessage();
                $error++;
            }
        }
        return $error;
    }

    /**
     *  Function to Print Receipt Ticket
     *
     *  @param   object    $object          order or invoice object
     *  @param   int       $template        Template id
     *  @param   int       $printerid       Printer id
     *  @return  int                        0 if OK; >0 if KO
     */
    function SendToPrinter($object, $template, $printerid)
    {
        global $conf;
        $error = 0;

        // parse template


        // print ticket

        return $error;
    }

}
