<?php
/*
 * Copyright (C) 2015-2018  Frédéric France     <frederic.france@free.fr>
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
 * <dol_print_customer_firstname>                   Print customer firstname
 * <dol_print_customer_lastname>                    Print customer name
 * <dol_print_customer_mail>                        Print customer mail
 * <dol_print_customer_phone>                       Print customer phone
 * <dol_print_customer_mobile>                      Print customer mobile
 * <dol_print_customer_skype>                       Print customer skype
 * <dol_print_customer_tax_number>                  Print customer VAT number
 * <dol_print_customer_account_balance>             Print customer account balance
 * <dol_print_vendor_lastname>                      Print vendor name
 * <dol_print_vendor_firstname>                     Print vendor firstname
 * <dol_print_vendor_mail>                          Print vendor mail
 * <dol_print_customer_points>                      Print customer points
 * <dol_print_order_points>                         Print number of points for this order
 *
 * Conditional code at line start (if�then Print)
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

require_once DOL_DOCUMENT_ROOT .'/includes/mike42/escpos-php/Escpos.php';


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

    /**
     * @var DoliDB Database handler.
     */
    public $db;

    var $tags;
    var $printer;
    var $template;

    /**
     * @var string Error code (or message)
     */
    public $error='';

    /**
     * @var string[] Error codes (or messages)
     */
    public $errors = array();



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
            'dol_print_customer_firstname',
            'dol_print_customer_lastname',
            'dol_print_customer_mail',
            'dol_print_customer_phone',
            'dol_print_customer_mobile',
            'dol_print_customer_skype',
            'dol_print_customer_tax_number',
            'dol_print_customer_account_balance',
            'dol_print_vendor_lastname',
            'dol_print_vendor_firstname',
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
        $obj = array();
        $sql = 'SELECT rowid, name, fk_type, fk_profile, parameter';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'printer_receipt';
        $sql.= ' WHERE entity = '.$conf->entity;
        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            while ($line < $num) {
                $row = $this->db->fetch_array($resql);
                switch ($row['fk_type']) {
                    case 1:
                        $row['fk_type_name'] = 'CONNECTOR_DUMMY';
                        break;
                    case 2:
                        $row['fk_type_name'] = 'CONNECTOR_FILE_PRINT';
                        break;
                    case 3:
                        $row['fk_type_name'] = 'CONNECTOR_NETWORK_PRINT';
                        break;
                    case 4:
                        $row['fk_type_name'] = 'CONNECTOR_WINDOWS_PRINT';
                        break;
                    case 5:
                        $row['fk_type_name'] = 'CONNECTOR_JAVA';
                        break;
                    default:
                        $row['fk_type_name'] = 'CONNECTOR_UNKNOWN';
                        break;
                }
                switch ($row['fk_profile']) {
                    case 0:
                        $row['fk_profile_name'] = 'PROFILE_DEFAULT';
                        break;
                    case 1:
                        $row['fk_profile_name'] = 'PROFILE_SIMPLE';
                        break;
                    case 2:
                        $row['fk_profile_name'] = 'PROFILE_EPOSTEP';
                        break;
                    case 3:
                        $row['fk_profile_name'] = 'PROFILE_P822D';
                        break;
                    default:
                        $row['fk_profile_name'] = 'PROFILE_STAR';
                        break;
                }
                $obj[] = $row;
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
        $obj = array();
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

        $options = array(
            1 => $langs->trans('CONNECTOR_DUMMY'),
            2 => $langs->trans('CONNECTOR_FILE_PRINT'),
            3 => $langs->trans('CONNECTOR_NETWORK_PRINT'),
            4 => $langs->trans('CONNECTOR_WINDOWS_PRINT')
        );

        $this->resprint = Form::selectarray($htmlname, $options, $selected);

        return 0;
    }


    /**
     *  Form to Select Profile printer
     *
     *  @param    string    $selected       Id printer profile pre-selected
     *  @param    string    $htmlname       select html name
     *  @return  int                        0 if OK; >0 if KO
     */
    function selectProfilePrinter($selected='', $htmlname='printerprofileid')
    {
        global $langs;

        $options = array(
            0 => $langs->trans('PROFILE_DEFAULT'),
            1 => $langs->trans('PROFILE_SIMPLE'),
            2 => $langs->trans('PROFILE_EPOSTEP'),
            3 => $langs->trans('PROFILE_P822D'),
            4 => $langs->trans('PROFILE_STAR')
        );

        $this->profileresprint = Form::selectarray($htmlname, $options, $selected);
        return 0;
    }


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     *  Function to Add a printer in db
     *
     *  @param    string    $name           Printer name
     *  @param    int       $type           Printer type
     *  @param    int       $profile        Printer profile
     *  @param    string    $parameter      Printer parameter
     *  @return  int                        0 if OK; >0 if KO
     */
    function AddPrinter($name, $type, $profile, $parameter)
    {
        // phpcs:enable
        global $conf;
        $error = 0;
        $sql = 'INSERT INTO '.MAIN_DB_PREFIX.'printer_receipt';
        $sql.= ' (name, fk_type, fk_profile, parameter, entity)';
        $sql.= ' VALUES ("'.$this->db->escape($name).'", '.$type.', '.$profile.', "'.$this->db->escape($parameter).'", '.$conf->entity.')';
        $resql = $this->db->query($sql);
        if (! $resql) {
            $error++;
            $this->errors[] = $this->db->lasterror;
        }
        return $error;
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     *  Function to Update a printer in db
     *
     *  @param    string    $name           Printer name
     *  @param    int       $type           Printer type
     *  @param    int       $profile        Printer profile
     *  @param    string    $parameter      Printer parameter
     *  @param    int       $printerid      Printer id
     *  @return  int                        0 if OK; >0 if KO
     */
    function UpdatePrinter($name, $type, $profile, $parameter, $printerid)
    {
        // phpcs:enable
        global $conf;
        $error = 0;
        $sql = 'UPDATE '.MAIN_DB_PREFIX.'printer_receipt';
        $sql.= ' SET name="'.$this->db->escape($name).'"';
        $sql.= ', fk_type='.$type;
        $sql.= ', fk_profile='.$profile;
        $sql.= ', parameter="'.$this->db->escape($parameter).'"';
        $sql.= ' WHERE rowid='.$printerid;
        $resql = $this->db->query($sql);
        if (! $resql) {
            $error++;
            $this->errors[] = $this->db->lasterror;
        }
        return $error;
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     *  Function to Delete a printer from db
     *
     *  @param    int       $printerid      Printer id
     *  @return  int                        0 if OK; >0 if KO
     */
    function DeletePrinter($printerid)
    {
        // phpcs:enable
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

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
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
        // phpcs:enable
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


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     *  Function to Send Test page to Printer
     *
     *  @param    int       $printerid      Printer id
     *  @return  int                        0 if OK; >0 if KO
     */
    function SendTestToPrinter($printerid)
    {
        // phpcs:enable
        global $conf;
        $error = 0;
        $img = new EscposImage(DOL_DOCUMENT_ROOT .'/theme/common/dolibarr_logo_bw.png');
        $ret = $this->InitPrinter($printerid);
        if ($ret>0) {
            setEventMessages($this->error, $this->errors, 'errors');
        } else {
            try {
                $this->printer->graphics($img);
                $this->printer->text("Hello World!\n");
                $testStr = "Testing 123";
                $this->printer->qrCode($testStr);
                $this->printer->text("Most simple example\n");
                $this->printer->feed();
                $this->printer->cut();
                //print '<pre>'.print_r($this->connector, true).'</pre>';
                $this->printer->close();
            } catch (Exception $e) {
                $this->errors[] = $e->getMessage();
                $error++;
            }
        }
        return $error;
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     *  Function to Print Receipt Ticket
     *
     *  @param   object    $object          order or invoice object
     *  @param   int       $templateid      Template id
     *  @param   int       $printerid       Printer id
     *  @return  int                        0 if OK; >0 if KO
     */
    function SendToPrinter($object, $templateid, $printerid)
    {
        // phpcs:enable
        global $conf;
        $error = 0;
        $ret = $this->loadTemplate($templateid);

        // tags a remplacer par leur valeur avant de parser
        $this->template = str_replace('<dol_print_num_order>', $object->id, $this->template);
        $this->template = str_replace('<dol_print_customer_firstname>', $object->customer_firstname, $this->template);
        $this->template = str_replace('<dol_print_customer_lastname>', $object->customer_lastname, $this->template);
        $this->template = str_replace('<dol_print_customer_mail>', $object->customer_mail, $this->template);
        $this->template = str_replace('<dol_print_customer_phone>', $object->customer_phone, $this->template);
        $this->template = str_replace('<dol_print_customer_mobile>', $object->customer_mobile, $this->template);
        $this->template = str_replace('<dol_print_customer_skype>', $object->customer_skype, $this->template);
        $this->template = str_replace('<dol_print_customer_tax_number>', $object->customer_tax_number, $this->template);
        $this->template = str_replace('<dol_print_customer_account_balance>', $object->customer_account_balance, $this->template);
        $this->template = str_replace('<dol_print_customer_points>', $object->customer_points, $this->template);
        $this->template = str_replace('<dol_print_order_points>', $object->order_points, $this->template);
        $this->template = str_replace('<dol_print_vendor_firstname>', $object->vendor_firstname, $this->template);
        $this->template = str_replace('<dol_print_vendor_lastname>', $object->vendor_lastname, $this->template);
        $this->template = str_replace('<dol_print_vendor_mail>', $object->vendor_mail, $this->template);
        $this->template = str_replace('<dol_print_date>', $object->date, $this->template);
        $this->template = str_replace('<dol_print_date_time>', $object->date_time, $this->template);
        $this->template = str_replace('<dol_print_year>', $object->date_time, $this->template);
        $this->template = str_replace('<dol_print_month_letters>', $object->date_time, $this->template);
        $this->template = str_replace('<dol_print_month>', $object->date_time, $this->template);
        $this->template = str_replace('<dol_print_day>', $object->date_time, $this->template);
        $this->template = str_replace('<dol_print_day_letters>', $object->date_time, $this->template);
        $this->template = str_replace('<dol_print_table>', $object->table, $this->template);
        $this->template = str_replace('<dol_print_cutlery>', $object->cutlery, $this->template);

        // parse template
        $p = xml_parser_create();
        xml_parse_into_struct($p, $this->template, $vals, $index);
        xml_parser_free($p);
        //print '<pre>'.print_r($index, true).'</pre>';
        //print '<pre>'.print_r($vals, true).'</pre>';
        // print ticket
        $level = 0;
        $html = '<table border="1" style="width:210px"><pre>';
        $ret = $this->InitPrinter($printerid);
        if ($ret>0) {
            setEventMessages($this->error, $this->errors, 'errors');
        }
        else
        {
            $nboflines = count($vals);
            for ($line=0; $line < $nboflines; $line++)
            {
                switch ($vals[$line]['tag']) {
                    case 'DOL_ALIGN_CENTER':
                        $this->printer->setJustification(Escpos::JUSTIFY_CENTER);
                        $html.='<center>';
                        $this->printer->text($vals[$line]['value']);
                        break;
                    case 'DOL_ALIGN_RIGHT':
                        $this->printer->setJustification(Escpos::JUSTIFY_RIGHT);
                        $html.='<right>';
                        break;
                    case 'DOL_ALIGN_LEFT':
                        $this->printer->setJustification(Escpos::JUSTIFY_LEFT);
                        $html.='<left>';
                        break;
                    case 'DOL_OPEN_DRAWER':
                        $this->printer->pulse();
                        $html.= ' &#991;'.nl2br($vals[$line]['value']);
                        break;
                    case 'DOL_ACTIVATE_BUZZER':
                        //$this->printer->buzzer();
                        $html.= ' &#x266b;'.nl2br($vals[$line]['value']);
                        break;
                    case 'DOL_PRINT_BARCODE':
                        // $vals[$line]['value'] -> barcode($content, $type)
                        $this->printer->barcode($object->barcode);
                        break;
                    case 'DOL_PRINT_BARCODE_CUSTOMER_ID':
                        // $vals[$line]['value'] -> barcode($content, $type)
                        $this->printer->barcode($object->customer_id);
                        break;
                    case 'DOL_PRINT_QRCODE':
                        // $vals[$line]['value'] -> qrCode($content, $ec, $size, $model)
                        $this->printer->qrcode($vals[$line]['value']);
                        $html.='QRCODE: '.$vals[$line]['value'];
                        break;
                    case 'DOL_CUT_PAPER_FULL':
                        $this->printer->cut(Escpos::CUT_FULL);
                        $html.= ' &#9986;'.nl2br($vals[$line]['value']);
                        break;
                    case 'DOL_CUT_PAPER_PARTIAL':
                        $this->printer->cut(Escpos::CUT_PARTIAL);
                        $html.= ' &#9986;'.nl2br($vals[$line]['value']);
                        break;
                    case 'DOL_USE_FONT_A':
                        $this->printer->setFont(Escpos::FONT_A);
                        $this->printer->text($vals[$line]['value']);
                        break;
                    case 'DOL_USE_FONT_B':
                        $this->printer->setFont(Escpos::FONT_B);
                        $this->printer->text($vals[$line]['value']);
                        break;
                    case 'DOL_USE_FONT_C':
                        $this->printer->setFont(Escpos::FONT_C);
                        $this->printer->text($vals[$line]['value']);
                        break;
                    default:
                        $this->printer->text($vals[$line]['value']);
                        $html.= nl2br($vals[$line]['value']);
                        $this->errors[] = 'UnknowTag: &lt;'.strtolower($vals[$line]['tag']).'&gt;';
                        $error++;
                        break;
                }
            }
            $html.= '</pre></table>';
            print $html;
            // Close and print
            // uncomment next line to see content sent to printer
            //print '<pre>'.print_r($this->connector, true).'</pre>';
            $this->printer->close();
        }
        return $error;
    }

    /**
     *  Function to load Template
     *
     *  @param   int       $templateid        Template id
     *  @return  int                        0 if OK; >0 if KO
     */
    function loadTemplate($templateid)
    {
        global $conf;
        $error = 0;
        $sql = 'SELECT template';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'printer_receipt_template';
        $sql.= ' WHERE rowid='.$templateid;
        $sql.= ' AND entity = '.$conf->entity;
        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_array($resql);
        } else {
            $error++;
            $this->errors[] = $this->db->lasterror;
        }
        if (empty($obj)) {
            $error++;
            $this->errors[] = 'TemplateDontExist';
        } else {
            $this->template = $obj['0'];
        }

        return $error;
    }


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     *  Function Init Printer
     *
     *  @param   int       $printerid       Printer id
     *  @return  int                        0 if OK; >0 if KO
     */
    function InitPrinter($printerid)
    {
        // phpcs:enable
        global $conf;
        $error=0;
        $sql = 'SELECT rowid, name, fk_type, fk_profile, parameter';
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
        if (empty($obj)) {
            $error++;
            $this->errors[] = 'PrinterDontExist';
        }
        if (! $error) {
            $parameter = $obj['parameter'];
            try {
                switch ($obj['fk_type']) {
                    case 1:
                        require_once DOL_DOCUMENT_ROOT .'/includes/mike42/escpos-php/src/DummyPrintConnector.php';
                        $this->connector = new DummyPrintConnector();
                        break;
                    case 2:
                        $this->connector = new FilePrintConnector($parameter);
                        break;
                    case 3:
                        $parameters = explode(':', $parameter);
                        $this->connector = new NetworkPrintConnector($parameters[0], $parameters[1]);
                        break;
                    case 4:
                        $this->connector = new WindowsPrintConnector($parameter);
                        break;
                    default:
                        $this->connector = 'CONNECTOR_UNKNOWN';
                        break;
                }
                $this->printer = new Escpos($this->connector);
            } catch (Exception $e) {
                $this->errors[] = $e->getMessage();
                $error++;
            }
        }
        return $error;
    }
}
