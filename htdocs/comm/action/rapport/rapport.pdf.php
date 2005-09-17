<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * or see http://www.gnu.org/
 *
 * $Id$
 * $Source$
 */

/**
	    \file       htdocs/comm/action/rapport/rapport.pdf.php
        \ingroup    commercial
		\brief      Fichier de generation de PDF pour les rapports d'actions
		\version    $Revision$
*/

require_once(FPDF_PATH.'fpdf.php');
require_once(DOL_DOCUMENT_ROOT ."/includes/fpdf/fpdf_indexes.php");
require_once(DOL_DOCUMENT_ROOT ."/includes/fpdf/fpdf_html.php");


/**
        \class      CommActionRapport
	    \brief      Classe permettant la generation des rapports d'actions
*/

class CommActionRapport
{
    var $title;
    var $subject;
    
    function CommActionRapport($db=0, $month, $year)
    {
        global $langs;
        $langs->load("commercial");
        
        $this->db = $db;
        $this->description = "";
        $this->date_edition = dolibarr_print_date(time(),"%d %B %Y");
        $this->month = $month;
        $this->year = $year;

        $this->title=$langs->trans("ActionsReport").' '.$this->year."-".$this->month;
        $this->subject=$langs->trans("ActionsReport").' '.$this->year."-".$this->month;
    }

    function generate($socid = 0, $catid = 0)
    {
        global $user,$conf,$langs;

        $dir = $conf->commercial->dir_output."/comm/actions";
        $file = $dir . "/rapport-action-".$this->month."-".$this->year.".pdf";
        create_exdir($dir);

        if (file_exists($dir))
        {
            $pdf=new PDF_html('P','mm','A4');
            $pdf->Open();

            $pdf->SetTitle($this->title);
            $pdf->SetSubject($this->subject);
            $pdf->SetCreator("Dolibarr ".DOL_VERSION);
            $pdf->SetAuthor($user->fullname);

            $pdf->SetFillColor(220,220,220);

            $pdf->SetFont('Arial','', 9);

            //	  $nbpage = $this->_cover($pdf);
            $nbpage = $this->_pages($pdf);

            $pdf->Close();

            $pdf->Output($file);

            return 1;
        }
    }

    /*
     *
     */
    function _cover(&$pdf)
    {
        global $user;

        $pdf->AddPage();
        $pdf->SetAutoPageBreak(false);
        $pdf->SetFont('Arial','',40);
        $pdf->SetXY (10, 80);
        $pdf->MultiCell(190, 20, $langs->trans("ActionsReport").' '.$title, 0, 'C', 0);

        $pdf->SetFont('Arial','',30);
        $pdf->SetXY (10, 140);
        $pdf->MultiCell(190, 20, $langs->trans("Date").': '.$this->date_edition, 0, 'C', 0);

        $pdf->SetXY (10, 170);
        $pdf->SetFont('Arial','B',18);
        $pdf->MultiCell(190, 15, $user->fullname, 0, 'C');
        $pdf->SetFont('Arial','B',16);
        $pdf->MultiCell(190, 15, "Tél : +33 (0) 6 13 79 63 41", 0, 'C');

        $pdf->SetFont('Arial','',10);
        $pdf->SetXY (10, 277);
        $pdf->MultiCell(190, 10, "http://www.lafrere.com/", 1, 'C', 1);

        $pdf->Rect(10, 10, 190, 277);
        return 1;
    }

    /*
     *
     */
    function _pages(&$pdf)
    {
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(true);

        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(5, $pdf->GetY());
        $pdf->MultiCell(80, 2, $this->title, 0, 'L', 0);

        $pdf->SetFont('Arial','',9);
        $y=$pdf->GetY()+1;
        
        $sql = "SELECT s.nom as societe, s.idp as socidp, s.client, a.id,".$this->db->pdate("a.datea")." as da, a.datea, c.libelle, u.code, a.fk_contact, a.note, a.percent as percent";
        $sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a, ".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."user as u";
        $sql .= " WHERE a.fk_soc = s.idp AND c.id=a.fk_action AND a.fk_user_author = u.rowid";

        $sql .= " AND date_format(a.datea, '%m') = ".$this->month;
        $sql .= " AND date_format(a.datea, '%Y') = ".$this->year;

        $sql .= " ORDER BY a.datea DESC";

        if ($this->db->query($sql))
        {
            $num = $this->db->num_rows();
            $i = 0;
            $y0=$y1=$y2=$y3=0;

            while ($i < $num)
            {
                $obj = $this->db->fetch_object();

                $y = max($y, $pdf->GetY(), $y0, $y1, $y2, $y3) + 1;

                $pdf->SetXY(5, $y);
                $pdf->MultiCell(22, 4, dolibarr_print_date($obj->da)."\n".dolibarr_print_date($obj->da,"%H:%m:%S"), 0, 'L', 0);
                $y0 = $pdf->GetY();

                $pdf->SetXY(26, $y);
                $pdf->MultiCell(40, 4, $obj->societe, 0, 'L', 0);
                $y1 = $pdf->GetY();

                $pdf->SetXY(66,$y);
                $pdf->MultiCell(40, 4, $obj->libelle, 0, 'L', 0);
                $y2 = $pdf->GetY();

                $pdf->SetXY(106,$y);
                $pdf->MultiCell(94, 4, eregi_replace('<br>',"\n",dolibarr_trunc($obj->note,150)), 0, 'L', 0);
                $y3 = $pdf->GetY();

                $i++;
            }
        }

        $pdf->Rect(5, 5, 200, 287);
        return 1;
    }


}

?>
