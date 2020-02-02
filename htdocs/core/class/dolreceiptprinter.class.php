<?php
/* Copyright (C) 2015-2019  Frédéric France     <frederic.france@netlogic.fr>
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
 * <dol_set_print_width_57>                         Ticket print width of 57mm
 * <dol_cut_paper_full>                             Cut ticket completely
 * <dol_cut_paper_partial>                          Cut ticket partially
 * <dol_open_drawer>                                Open cash drawer
 * <dol_activate_buzzer>                            Activate buzzer
 * <dol_print_barcode>                              Print barcode
 * <dol_print_qrcode>                               Print QR Code
 * <dol_print_logo>                                 Print logo stored on printer. Example : <print_logo>32|32
 * <dol_print_logo_old>                             Print logo stored on printer. Must be followed by logo code. For old printers.
 * <dol_print_object_lines>                         Print object lines
 * <dol_print_object_tax>                           Print object total tax
 * <dol_print_object_local_tax>                     Print object local tax
 * <dol_print_object_total>                         Print object total
 * <dol_print_payment>                              Print payment method
 *
 * Code which can be placed everywhere
 * <dol_value_date>                                 Replaced by date AAAA-MM-DD
 * <dol_value_date_time>                            Replaced by date and time AAAA-MM-DD HH:MM:SS
 * <dol_value_year>                                 Replaced by Year
 * <dol_value_month_letters>                        Replaced by month in letters (example : november)
 * <dol_value_month>                                Replaced by month number
 * <dol_value_day>                                  Replaced by day number
 * <dol_value_day_letters>                          Replaced by day number
 * <dol_value_table>                                Replaced by table number (for restaurant, bar...)
 * <dol_value_cutlery>                              Replaced by number of cutlery (for restaurant)
 * <dol_object_id>                                  Replaced by object id
 * <dol_object_ref>                                 Replaced by object ref
 * <dol_value_customer_firstname>                   Replaced by customer firstname
 * <dol_value_customer_lastname>                    Replaced by customer name
 * <dol_value_customer_mail>                        Replaced by customer mail
 * <dol_value_customer_phone>                       Replaced by customer phone
 * <dol_value_customer_mobile>                      Replaced by customer mobile
 * <dol_value_customer_skype>                       Replaced by customer skype
 * <dol_value_customer_tax_number>                  Replaced by customer VAT number
 * <dol_value_customer_account_balance>             Replaced by customer account balance
 * <dol_value_mysoc_name>                           Replaced by mysoc name
 * <dol_value_mysoc_address>                        Replaced by mysoc address
 * <dol_value_mysoc_zip>                            Replaced by mysoc zip
 * <dol_value_mysoc_town>                           Replaced by mysoc town
 * <dol_value_mysoc_country>                        Replaced by mysoc country
 * <dol_value_mysoc_idprof1>                        Replaced by mysoc idprof1
 * <dol_value_mysoc_idprof2>                        Replaced by mysoc idprof2
 * <dol_value_mysoc_idprof3>                        Replaced by mysoc idprof3
 * <dol_value_mysoc_idprof4>                        Replaced by mysoc idprof4
 * <dol_value_mysoc_idprof5>                        Replaced by mysoc idprof5
 * <dol_value_mysoc_idprof6>                        Replaced by mysoc idprof6
 * <dol_value_vendor_lastname>                      Replaced by vendor name
 * <dol_value_vendor_firstname>                     Replaced by vendor firstname
 * <dol_value_vendor_mail>                          Replaced by vendor mail
 * <dol_value_customer_points>                      Replaced by customer points
 * <dol_value_object_points>                        Replaced by number of points for this object
 *
 * Conditional code at line start (if then Print)
 * <dol_print_if_customer>                          Print the line IF a customer is affected to the object
 * <dol_print_if_vendor>                            Print the line IF a vendor is affected to the object
 * <dol_print_if_happy_hour>                        Print the line IF Happy Hour
 * <dol_print_if_num_object_unique>                 Print the line IF object is validated
 * <dol_print_if_customer_points>                   Print the line IF customer points > 0
 * <dol_print_if_object_points>                     Print the line IF points of the object > 0
 * <dol_print_if_customer_tax_number>               Print the line IF customer has vat number
 * <dol_print_if_customer_account_balance_positive> Print the line IF customer balance > 0
 *
 */

require_once DOL_DOCUMENT_ROOT .'/includes/mike42/escpos-php/autoload.php';
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\CapabilityProfile;
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;


/**
 * Class to manage Receipt Printers
 */
class dolReceiptPrinter extends Printer
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

    /*
     * @var string[] array of tags
     */
    public $tags;
    public $printer;
    public $template;

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
    public function __construct($db)
    {
        $this->db=$db;
        $this->tags = array(
            'dol_line_feed',
            'dol_line_feed_reverse',
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
            'dol_set_print_width_57',
            'dol_cut_paper_full',
            'dol_cut_paper_partial',
            'dol_open_drawer',
            //'dol_activate_buzzer',
            'dol_print_text',
            'dol_print_qrcode',
            'dol_print_barcode',
            'dol_value_date',
            'dol_value_date_time',
            'dol_value_year',
            'dol_value_month_letters',
            'dol_value_month',
            'dol_value_day',
            'dol_value_day_letters',
            'dol_value_table',
            'dol_value_cutlery',
            'dol_print_payment',
            'dol_print_logo',
            'dol_print_logo_old',
            'dol_value_object_id',
            'dol_value_object_ref',
            'dol_print_object_lines',
            'dol_print_object_tax',
            'dol_print_object_local_tax',
            'dol_print_object_total',
            'dol_print_object_number',
            'dol_value_customer_firstname',
            'dol_value_customer_lastname',
            'dol_value_customer_mail',
            'dol_value_customer_phone',
            'dol_value_customer_mobile',
            'dol_value_customer_skype',
            'dol_value_customer_tax_number',
            'dol_value_customer_account_balance',
            'dol_value_mysoc_name',
            'dol_value_mysoc_address',
            'dol_value_mysoc_zip',
            'dol_value_mysoc_town',
            'dol_value_mysoc_country',
            'dol_value_mysoc_idprof1',
            'dol_value_mysoc_idprof2',
            'dol_value_mysoc_idprof3',
            'dol_value_mysoc_idprof4',
            'dol_value_mysoc_idprof5',
            'dol_value_mysoc_idprof6',
            'dol_value_mysoc_tva_intra',
            'dol_value_mysoc_capital',
            'dol_value_vendor_lastname',
            'dol_value_vendor_firstname',
            'dol_value_vendor_mail',
            'dol_value_customer_points',
            'dol_value_object_points',
            'dol_print_if_customer',
            'dol_print_if_vendor',
            'dol_print_if_happy_hour',
            'dol_print_if_num_object_unique',
            'dol_print_if_customer_points',
            'dol_print_if_object_points',
            'dol_print_if_customer_tax_number',
            'dol_print_if_customer_account_balance_positive',
        );
    }

    /**
     * list printers
     *
     * @return  int                     0 if OK; >0 if KO
     */
    public function listPrinters()
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
    public function listPrintersTemplates()
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
    public function selectTypePrinter($selected = '', $htmlname = 'printertypeid')
    {
        global $langs;

        $options = array(
            1 => $langs->trans('CONNECTOR_DUMMY'),
            2 => $langs->trans('CONNECTOR_FILE_PRINT'),
            3 => $langs->trans('CONNECTOR_NETWORK_PRINT'),
            4 => $langs->trans('CONNECTOR_WINDOWS_PRINT'),
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
    public function selectProfilePrinter($selected = '', $htmlname = 'printerprofileid')
    {
        global $langs;

        $options = array(
            0 => $langs->trans('PROFILE_DEFAULT'),
            1 => $langs->trans('PROFILE_SIMPLE'),
            2 => $langs->trans('PROFILE_EPOSTEP'),
            3 => $langs->trans('PROFILE_P822D'),
            4 => $langs->trans('PROFILE_STAR'),
        );

        $this->profileresprint = Form::selectarray($htmlname, $options, $selected);
        return 0;
    }


    /**
     *  Function to Add a printer in db
     *
     *  @param    string    $name           Printer name
     *  @param    int       $type           Printer type
     *  @param    int       $profile        Printer profile
     *  @param    string    $parameter      Printer parameter
     *  @return  int                        0 if OK; >0 if KO
     */
    public function addPrinter($name, $type, $profile, $parameter)
    {
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
    public function updatePrinter($name, $type, $profile, $parameter, $printerid)
    {
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

    /**
     *  Function to Delete a printer from db
     *
     *  @param    int       $printerid      Printer id
     *  @return  int                        0 if OK; >0 if KO
     */
    public function deletePrinter($printerid)
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
     *  Function to add a printer template in db
     *
     *  @param    string    $name           Template name
     *  @param    int       $template       Template
     *  @return   int                       0 if OK; >0 if KO
     */
    public function addTemplate($name, $template)
    {
        global $conf;
        $error = 0;
        $sql = 'INSERT INTO '.MAIN_DB_PREFIX.'printer_receipt_template';
        $sql.= ' (name, template, entity) VALUES ("'.$this->db->escape($name).'"';
        $sql.= ', "'.$this->db->escape($template).'", '.$conf->entity.')';
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
    public function updateTemplate($name, $template, $templateid)
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
    public function sendTestToPrinter($printerid)
    {
        global $conf;
        $error = 0;
        $img = EscposImage::load(DOL_DOCUMENT_ROOT.'/theme/dolibarr_logo_bw.png');
        //$this->profile = CapabilityProfile::load("TM-T88IV");
        $ret = $this->initPrinter($printerid);
        if ($ret>0) {
            setEventMessages($this->error, $this->errors, 'errors');
        } else {
            try {
                $this->printer->bitImage($img);
                $this->printer->text("Hello World!\n");
                $testStr = "1234567890";
                $this->printer->barcode($testStr);
                //$this->printer->qrcode($testStr, Printer::QR_ECLEVEL_M, 5, Printer::QR_MODEL_1);
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

    /**
     *  Function to Print Receipt Ticket
     *
     *  @param   object    $object          order or invoice object
     *  @param   int       $templateid      Template id
     *  @param   int       $printerid       Printer id
     *  @return  int                        0 if OK; >0 if KO
     */
    public function sendToPrinter($object, $templateid, $printerid)
    {
        global $conf, $mysoc, $langs;
        $error = 0;
        $ret = $this->loadTemplate($templateid);

        // tags a remplacer par leur valeur avant de parser (dol_value_xxx)
        $this->template = str_replace('<dol_value_object_id>', $object->id, $this->template);
        $this->template = str_replace('<dol_value_object_ref>', $object->ref, $this->template);
        $this->template = str_replace('<dol_value_object_points>', $object->points, $this->template);
        $this->template = str_replace('<dol_value_customer_firstname>', $object->customer_firstname, $this->template);
        $this->template = str_replace('<dol_value_customer_lastname>', $object->customer_lastname, $this->template);
        $this->template = str_replace('<dol_value_customer_mail>', $object->customer_mail, $this->template);
        $this->template = str_replace('<dol_value_customer_phone>', $object->customer_phone, $this->template);
        $this->template = str_replace('<dol_value_customer_mobile>', $object->customer_mobile, $this->template);
        $this->template = str_replace('<dol_value_customer_skype>', $object->customer_skype, $this->template);
        $this->template = str_replace('<dol_value_customer_tax_number>', $object->customer_tax_number, $this->template);
        $this->template = str_replace('<dol_value_customer_account_balance>', $object->customer_account_balance, $this->template);
        $this->template = str_replace('<dol_value_customer_points>', $object->customer_points, $this->template);
        $this->template = str_replace('<dol_value_mysoc_name>', $mysoc->name, $this->template);
        $this->template = str_replace('<dol_value_mysoc_address>', $mysoc->address, $this->template);
        $this->template = str_replace('<dol_value_mysoc_zip>', $mysoc->zip, $this->template);
        $this->template = str_replace('<dol_value_mysoc_town>', $mysoc->town, $this->template);
        $this->template = str_replace('<dol_value_mysoc_country>', $mysoc->country, $this->template);
        $this->template = str_replace('<dol_value_mysoc_idprof1>', $mysoc->idprof1, $this->template);
        $this->template = str_replace('<dol_value_mysoc_idprof2>', $mysoc->idprof2, $this->template);
        $this->template = str_replace('<dol_value_mysoc_idprof3>', $mysoc->idprof3, $this->template);
        $this->template = str_replace('<dol_value_mysoc_idprof4>', $mysoc->idprof4, $this->template);
        $this->template = str_replace('<dol_value_mysoc_idprof5>', $mysoc->idprof5, $this->template);
        $this->template = str_replace('<dol_value_mysoc_idprof6>', $mysoc->idprof6, $this->template);
        $this->template = str_replace('<dol_value_mysoc_tva_intra>', $mysoc->tva_intra, $this->template);
        $this->template = str_replace('<dol_value_mysoc_capital>', $mysoc->capital, $this->template);
        $this->template = str_replace('<dol_value_vendor_firstname>', $object->vendor_firstname, $this->template);
        $this->template = str_replace('<dol_value_vendor_lastname>', $object->vendor_lastname, $this->template);
        $this->template = str_replace('<dol_value_vendor_mail>', $object->vendor_mail, $this->template);
        $this->template = str_replace('<dol_value_date>', $object->date, $this->template);
        $this->template = str_replace('<dol_value_date_time>', $object->date_time, $this->template);
        $this->template = str_replace('<dol_value_year>', $object->date_time, $this->template);
        $this->template = str_replace('<dol_value_month_letters>', $object->date_time, $this->template);
        $this->template = str_replace('<dol_value_month>', $object->date_time, $this->template);
        $this->template = str_replace('<dol_value_day>', $object->date_time, $this->template);
        $this->template = str_replace('<dol_value_day_letters>', $object->date_time, $this->template);
        $this->template = str_replace('<dol_value_table>', $object->table, $this->template);
        $this->template = str_replace('<dol_value_cutlery>', $object->cutlery, $this->template);

        // parse template
        $p = xml_parser_create();
        xml_parse_into_struct($p, $this->template, $vals, $index);
        xml_parser_free($p);
        //print '<pre>'.print_r($index, true).'</pre>';
        //print '<pre>'.print_r($vals, true).'</pre>';
        // print ticket
        $level = 0;
        $nbcharactbyline = 48;
        $ret = $this->initPrinter($printerid);
        if ($ret>0) {
            setEventMessages($this->error, $this->errors, 'errors');
        } else {
            $nboflines = count($vals);
            for ($tplline=0; $tplline < $nboflines; $tplline++) {
                //var_dump($vals[$tplline]['value']);
                switch ($vals[$tplline]['tag']) {
                    case 'DOL_PRINT_TEXT':
                        $this->printer->text($vals[$tplline]['value']);
                        break;
                    case 'DOL_PRINT_OBJECT_LINES':
                        foreach ($object->lines as $line) {
                            //var_dump($line);
                            $spacestoadd = $nbcharactbyline - strlen($line->ref)- strlen($line->qty) - 10 - 1;
                            $spaces = str_repeat(' ', $spacestoadd);
                            $this->printer->text($line->ref.$spaces.$line->qty.' '.str_pad(price($line->total_ttc), 10, ' ', STR_PAD_LEFT)."\n");
                            $this->printer->text(strip_tags(htmlspecialchars_decode($line->desc))."\n");
                        }
                        break;
                    case 'DOL_PRINT_OBJECT_TAX':
                        //var_dump($object);
                        $vatarray = array();
                        foreach ($object->lines as $line) {
                            $vatarray[$line->tva_tx] += $line->total_tva;
                        }
                        foreach($vatarray as $vatkey => $vatvalue) {
                             $spacestoadd = $nbcharactbyline - strlen($vatkey)- 12;
                             $spaces = str_repeat(' ', $spacestoadd);
                             $this->printer->text($spaces. $vatkey.'% '.str_pad(price($vatvalue), 10, ' ', STR_PAD_LEFT)."\n");
                        }
                        break;
                    case 'DOL_PRINT_OBJECT_TOTAL':
                        $title = $langs->trans('TotalHT');
                        $spacestoadd = $nbcharactbyline - strlen($title) - 10;
                        $spaces = str_repeat(' ', $spacestoadd);
                        $this->printer->text($title.$spaces.str_pad(price($object->total_ht), 10, ' ', STR_PAD_LEFT)."\n");
                        $title = $langs->trans('TotalVAT');
                        $spacestoadd = $nbcharactbyline - strlen($title) - 10;
                        $spaces = str_repeat(' ', $spacestoadd);
                        $this->printer->text($title.$spaces.str_pad(price($object->total_tva), 10, ' ', STR_PAD_LEFT)."\n");
                        $title = $langs->trans('TotalTTC');
                        $spacestoadd = $nbcharactbyline - strlen($title) - 10;
                        $spaces = str_repeat(' ', $spacestoadd);
                        $this->printer->text($title.$spaces.str_pad(price($object->total_ttc), 10, ' ', STR_PAD_LEFT)."\n");
                        break;
                    case 'DOL_LINE_FEED':
                        $this->printer->feed();
                        break;
                    case 'DOL_LINE_FEED_REVERSE':
                        $this->printer->feedReverse();
                        break;
                    case 'DOL_ALIGN_CENTER':
                        $this->printer->setJustification(Printer::JUSTIFY_CENTER);
                        break;
                    case 'DOL_ALIGN_RIGHT':
                        $this->printer->setJustification(Printer::JUSTIFY_RIGHT);
                        break;
                    case 'DOL_ALIGN_LEFT':
                        $this->printer->setJustification(Printer::JUSTIFY_LEFT);
                        break;
                    case 'DOL_OPEN_DRAWER':
                        $this->printer->pulse();
                        break;
                    case 'DOL_ACTIVATE_BUZZER':
                        //$this->printer->buzzer();
                        break;
                    case 'DOL_PRINT_BARCODE':
                        // $vals[$tplline]['value'] -> barcode($content, $type)
                        // var_dump($vals[$tplline]['value']);
                        try {
                            $this->printer->barcode($vals[$tplline]['value']);
                        } catch (Exception $e) {
                            $this->errors[] = 'Invalid Barcode value: '.$vals[$tplline]['value'];
                            $error++;
                        }
                        break;
                    case 'DOL_PRINT_LOGO':
                        $img = EscposImage::load(DOL_DATA_ROOT .'/mycompany/logos/'.$mysoc->logo);
                        $this->printer->graphics($img);
                        break;
                    case 'DOL_PRINT_LOGO_OLD':
                        $img = EscposImage::load(DOL_DATA_ROOT .'/mycompany/logos/'.$mysoc->logo);
                        $this->printer->bitImage($img);
                        break;
                    case 'DOL_PRINT_QRCODE':
                        // $vals[$tplline]['value'] -> qrCode($content, $ec, $size, $model)
                        $this->printer->qrcode($vals[$tplline]['value']);
                        break;
                    case 'DOL_CUT_PAPER_FULL':
                        $this->printer->cut(Printer::CUT_FULL);
                        break;
                    case 'DOL_CUT_PAPER_PARTIAL':
                        $this->printer->cut(Printer::CUT_PARTIAL);
                        break;
                    case 'DOL_USE_FONT_A':
                        $this->printer->setFont(Printer::FONT_A);
                        break;
                    case 'DOL_USE_FONT_B':
                        $this->printer->setFont(Printer::FONT_B);
                        break;
                    case 'DOL_USE_FONT_C':
                        $this->printer->setFont(Printer::FONT_C);
                        break;
                    default:
                        $this->printer->text($vals[$tplline]['tag']);
                        $this->printer->text($vals[$tplline]['value']);
                        $this->errors[] = 'UnknowTag: &lt;'.strtolower($vals[$tplline]['tag']).'&gt;';
                        $error++;
                        break;
                }
            }
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
     *  @param   int       $templateid          Template id
     *  @return  int                            0 if OK; >0 if KO
     */
    public function loadTemplate($templateid)
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


    /**
     *  Function Init Printer
     *
     *  @param   int       $printerid       Printer id
     *  @return  int                        0 if OK; >0 if KO
     */
    public function initPrinter($printerid)
    {
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
                $this->printer = new Printer($this->connector, $this->profile);
            } catch (Exception $e) {
                $this->errors[] = $e->getMessage();
                $error++;
            }
        }
        return $error;
    }
}
