<?php
/* Copyright (C) 2003-2005  Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015  Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012  Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2011  Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2012       Cedric Salvador			<csalvador@gpcsolutions.fr>
 * Copyright (C) 2013       Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2015       Marcos García			<marcosgdf@gmail.com>
 * Copyright (C) 2016-2018  Charlie Benke			<charlie@patas-monkey.com>
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
 */

/**
 *  \file       htdocs/fichinter/class/fichinterrec.class.php
 *  \ingroup    facture
 *  \brief      Fichier de la classe des factures recurentes
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/notify.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';


/**
 *	Classe de gestion des factures recurrentes/Modeles
 */
class FichinterRec extends Fichinter
{
    public $element = 'fichinterrec';
    public $table_element = 'fichinter_rec';
    public $table_element_line = 'fichinter_rec';
    public $fk_element = 'fk_fichinter';

    /**
	 * {@inheritdoc}
	 */
	protected $table_ref_field = 'titre';

    /**
     * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
     */
    public $picto = 'intervention';

    public $title;
    public $number;
    public $date;
    public $amount;
    public $remise;
    public $tva;
    public $total;
    public $db_table;
    public $propalid;

    public $date_last_gen;
    public $date_when;
    public $nb_gen_done;
    public $nb_gen_max;

    public $rang;
    public $special_code;

    public $usenewprice = 0;

    /**
     *	Constructor
     *
     * 	@param		DoliDB		$db		Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;

        //status dans l'ordre de l'intervention
        $this->statuts[0] = 'Draft';
        $this->statuts[1] = 'Closed';

        $this->statuts_short[0] = 'Draft';
        $this->statuts_short[1] = 'Closed';

        $this->statuts_logo[0] = 'statut0';
        $this->statuts_logo[1] = 'statut1';
    }

    /**
     *	Returns the label status
     *
     *	@param      int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
     *	@return     string              Label
     */
    public function getLibStatut($mode = 0)
    {
        return $this->LibStatut($this->statut, $mode);
    }


    /**
     *  Create a predefined fichinter
     *
     *  @param      User    $user       User object
     *  @param      int     $notrigger  no trigger
     *  @return     int                 <0 if KO, id of fichinter if OK
     */
    public function create($user, $notrigger = 0)
    {
        global $conf;

        $error = 0;
        $now = dol_now();

        // Clean parameters
        $this->title = trim($this->title);
        $this->description = trim($this->description);


        $this->db->begin();

        // Load fichinter model
        $fichintsrc = new Fichinter($this->db);

        $result = $fichintsrc->fetch($this->id_origin);
        $result = $fichintsrc->fetch_lines(1); // to get all lines


        if ($result > 0) {
            // On positionne en mode brouillon la facture
            $this->brouillon = 1;

            $sql = "INSERT INTO ".MAIN_DB_PREFIX."fichinter_rec (";
            $sql .= "titre";
            $sql .= ", fk_soc";
            $sql .= ", entity";
            $sql .= ", datec";
            $sql .= ", duree";
            $sql .= ", description";
            $sql .= ", note_private";
            $sql .= ", note_public";
            $sql .= ", fk_user_author";
            $sql .= ", fk_projet";
            $sql .= ", fk_contrat";
            $sql .= ", modelpdf";

            $sql .= ", frequency";
            $sql .= ", unit_frequency";
            $sql .= ", date_when";
            $sql .= ", date_last_gen";
            $sql .= ", nb_gen_done";
            $sql .= ", nb_gen_max";
            // $sql.= ", auto_validate";

            $sql .= ") VALUES (";
            $sql .= "'".$this->db->escape($this->title)."'";
            $sql .= ", ".($this->socid > 0 ? $this->socid : 'null');
            $sql .= ", ".$conf->entity;
            $sql .= ", '".$this->db->idate($now)."'";
            $sql .= ", ".(!empty($fichintsrc->duration) ? $fichintsrc->duration : '0');
            $sql .= ", ".(!empty($this->description) ? ("'".$this->db->escape($this->description)."'") : "null");
            $sql .= ", ".(!empty($fichintsrc->note_private) ? ("'".$this->db->escape($fichintsrc->note_private)."'") : "null");
            $sql .= ", ".(!empty($fichintsrc->note_public) ? ("'".$this->db->escape($fichintsrc->note_public)."'") : "null");
            $sql .= ", '".$user->id."'";
            // si c'est la même société on conserve les liens vers le projet et le contrat
            if ($this->socid == $fichintsrc->socid) {
                $sql .= ", ".(!empty($fichintsrc->fk_project) ? $fichintsrc->fk_project : "null");
                $sql .= ", ".(!empty($fichintsrc->fk_contrat) ? $fichintsrc->fk_contrat : "null");
            } else {
                $sql .= ", null, null";
            }

            $sql .= ", ".(!empty($fichintsrc->modelpdf) ? "'".$this->db->escape($fichintsrc->modelpdf)."'" : "''");

            // récurrence
            $sql .= ", ".(!empty($this->frequency) ? $this->frequency : "null");
            $sql .= ", '".$this->db->escape($this->unit_frequency)."'";
            $sql .= ", ".(!empty($this->date_when) ? "'".$this->db->idate($this->date_when)."'" : 'null');
            $sql .= ", ".(!empty($this->date_last_gen) ? "'".$this->db->idate($this->date_last_gen)."'" : 'null');
            $sql .= ", 0"; // we start à 0
            $sql .= ", ".$this->nb_gen_max;
            // $sql.= ", ".$this->auto_validate;
            $sql .= ")";

            if ($this->db->query($sql)) {
                $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);

                /*
                 * Lines
                 */
                $num = count($fichintsrc->lines);
                for ($i = 0; $i < $num; $i++) {
                    //$result=$fichintlignesrc->fetch($fichintsrc->lines[$i]->id);

                    //var_dump($fichintsrc->lines[$i]);
                    $result_insert = $this->addline(
                        $fichintsrc->lines[$i]->desc,
                        $fichintsrc->lines[$i]->duration,
                        $fichintsrc->lines[$i]->datei,
                        $fichintsrc->lines[$i]->rang,
                        $fichintsrc->lines[$i]->subprice,
                        $fichintsrc->lines[$i]->qty,
                        $fichintsrc->lines[$i]->tva_tx,
                        $fichintsrc->lines[$i]->fk_product,
                        $fichintsrc->lines[$i]->remise_percent,
                        'HT',
                        0,
                        '',
                        0,
                        $fichintsrc->lines[$i]->product_type,
                        $fichintsrc->lines[$i]->special_code,
                        $fichintsrc->lines[$i]->label,
                        $fichintsrc->lines[$i]->fk_unit
                    );

                    if ($result_insert < 0)
                        $error++;
                }

                if ($error)
                    $this->db->rollback();
                else {
                    $this->db->commit();
                    return $this->id;
                }
            } else {
                $this->error = $this->db->error().' sql='.$sql;
                $this->db->rollback();
                return -2;
            }
        } else {
            $this->db->rollback();
            return -1;
        }
    }


    /**
     *	Recupere l'objet facture et ses lignes de factures
     *
     *	@param	  int		$rowid	   	Id of object to load
     * 	@param		string	$ref			Reference of fichinter
     * 	@param		string	$ref_ext		External reference of fichinter
     *	@return	 int		 			>0 if OK, <0 if KO, 0 if not found
     */
    public function fetch($rowid = 0, $ref = '', $ref_ext = '')
    {
        $sql = 'SELECT f.titre, f.fk_soc';
        $sql .= ', f.datec, f.duree, f.fk_projet, f.fk_contrat, f.description';
        $sql .= ', f.note_private, f.note_public, f.fk_user_author';
        $sql .= ', f.frequency, f.unit_frequency, f.date_when, f.date_last_gen, f.nb_gen_done, f.nb_gen_max, f.auto_validate';
        $sql .= ', f.note_private, f.note_public, f.fk_user_author';

        $sql .= ' FROM '.MAIN_DB_PREFIX.'fichinter_rec as f';
        if ($rowid > 0) $sql .= ' WHERE f.rowid='.$rowid;
        elseif ($ref) $sql .= " WHERE f.titre='".$this->db->escape($ref)."'";

        dol_syslog(get_class($this)."::fetch rowid=".$rowid, LOG_DEBUG);

        $result = $this->db->query($sql);
        if ($result) {
            if ($this->db->num_rows($result)) {
                $obj = $this->db->fetch_object($result);

                $this->id = $rowid;
                $this->titre				= $obj->titre;
                $this->ref = $obj->titre;
                $this->description = $obj->description;
                $this->datec				= $obj->datec;
                $this->duration = $obj->duree;
                $this->socid				= $obj->fk_soc;
                $this->statut = 0;
                $this->fk_project			= $obj->fk_projet;
                $this->fk_contrat			= $obj->fk_contrat;
                $this->note_private = $obj->note_private;
                $this->note_public			= $obj->note_public;
                $this->user_author			= $obj->fk_user_author;
                $this->modelpdf				= $obj->model_pdf;
                $this->rang = $obj->rang;
                $this->special_code = $obj->special_code;
                $this->frequency			= $obj->frequency;
                $this->unit_frequency = $obj->unit_frequency;
                $this->date_when			= $this->db->jdate($obj->date_when);
                $this->date_last_gen		= $this->db->jdate($obj->date_last_gen);
                $this->nb_gen_done = $obj->nb_gen_done;
                $this->nb_gen_max = $obj->nb_gen_max;
                $this->auto_validate		= $obj->auto_validate;

                $this->brouillon = 1;

                /*
                 * Lines
                 */
                $result = $this->fetch_lines();
                if ($result < 0) {
                    $this->error = $this->db->error();
                    return -3;
                }
                return 1;
            } else {
                $this->error = 'Interventional with id '.$rowid.' not found sql='.$sql;
                dol_syslog(get_class($this).'::Fetch Error '.$this->error, LOG_ERR);
                return -2;
            }
        } else {
            $this->error = $this->db->error();
            return -1;
        }
    }


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *  Recupere les lignes de factures predefinies dans this->lines
     *  @param   int	$sall   sall
     *
     *  @return	 int            1 if OK, < 0 if KO
     */
    public function fetch_lines($sall = 0)
    {
        // phpcs:enable
        $sql = 'SELECT l.rowid, l.fk_product, l.product_type, l.label as custom_label, l.description, ';
        $sql .= ' l.price, l.qty, l.tva_tx, l.remise, l.remise_percent, l.subprice, l.duree, ';
        $sql .= ' l.total_ht, l.total_tva, l.total_ttc,';
        $sql .= ' l.rang, l.special_code,';
        $sql .= ' l.fk_unit, p.ref as product_ref, p.fk_product_type as fk_product_type,';
        $sql .= ' p.label as product_label, p.description as product_desc';
        $sql .= ' FROM '.MAIN_DB_PREFIX.'fichinterdet_rec as l';
        $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON l.fk_product = p.rowid';
        $sql .= ' WHERE l.fk_fichinter = '.$this->id;

        dol_syslog('FichInter-rec::fetch_lines', LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result) {
            $num = $this->db->num_rows($result);
            $i = 0;
            while ($i < $num) {
                $objp = $this->db->fetch_object($result);
                $line = new FichinterLigne($this->db);

                $line->id = $objp->rowid;
                $line->label = $objp->custom_label; // Label line
                $line->desc = $objp->description; // Description line
                $line->product_type = $objp->product_type; // Type of line
                $line->product_ref = $objp->product_ref; // Ref product
                $line->product_label = $objp->product_label; // Label product
                $line->product_desc = $objp->product_desc; // Description product
                $line->fk_product_type = $objp->fk_product_type; // Type of product
                $line->qty = $objp->qty;
                $line->duree = $objp->duree;
                $line->duration = $objp->duree;
                $line->datei = $objp->date;
                $line->subprice = $objp->subprice;
                $line->tva_tx = $objp->tva_tx;
                $line->remise_percent = $objp->remise_percent;
                $line->fk_remise_except = $objp->fk_remise_except;
                $line->fk_product = $objp->fk_product;
                $line->date_start = $objp->date_start;
                $line->date_end = $objp->date_end;
                $line->date_start = $objp->date_start;
                $line->date_end = $objp->date_end;
                $line->info_bits = $objp->info_bits;
                $line->total_ht = $objp->total_ht;
                $line->total_tva = $objp->total_tva;
                $line->total_ttc = $objp->total_ttc;
                $line->code_ventilation = $objp->fk_code_ventilation;
                $line->rang = $objp->rang;
                $line->special_code = $objp->special_code;
                $line->fk_unit = $objp->fk_unit;

                // Ne plus utiliser
                $line->price = $objp->price;
                $line->remise = $objp->remise;

                $this->lines[$i] = $line;

                $i++;
            }

            $this->db->free($result);
            return 1;
        } else {
            $this->error = $this->db->error();
            return -3;
        }
    }


    /**
     * 	Delete template fichinter rec
     *
     *	@param	 	int		$rowid	  	    Id of fichinter rec to delete. If empty, we delete current instance of fichinter rec
     *	@param		int		$notrigger	    1=Does not execute triggers, 0= execute triggers
     *	@param		int		$idwarehouse    Id warehouse to use for stock change.
     *	@return		int						<0 if KO, >0 if OK
     */
    public function delete($rowid = 0, $notrigger = 0, $idwarehouse = -1)
    {
        if (empty($rowid)) $rowid = $this->id;

        dol_syslog(get_class($this)."::delete rowid=".$rowid, LOG_DEBUG);

        $error = 0;
        $this->db->begin();

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."fichinterdet_rec WHERE fk_fichinter = ".$rowid;
        dol_syslog($sql);
        if ($this->db->query($sql)) {
            $sql = "DELETE FROM ".MAIN_DB_PREFIX."fichinter_rec WHERE rowid = ".$rowid;
            dol_syslog($sql);
            if (!$this->db->query($sql)) {
                $this->error = $this->db->lasterror();
                $error = -1;
            }
        } else {
            $this->error = $this->db->lasterror();
            $error = -2;
        }

        if (!$error) {
            $this->db->commit();
            return 1;
        } else {
            $this->db->rollback();
            return $error;
        }
    }


    /**
    *  Add a line to fichinter rec
    *
    *  @param		string		$desc               Description de la ligne
    *  @param		integer		$duration           Durée
    *  @param		string	    $datei				Date
    *  @param	    int			$rang			    Position of line
    *  @param		double		$pu_ht			    Prix unitaire HT (> 0 even for credit note)
    *  @param		double		$qty			 	Quantite
    *  @param		double		$txtva		   	    Taux de tva force, sinon -1
    *  @param		int			$fk_product	  	    Id du produit/service predefini
    *  @param		double		$remise_percent  	Pourcentage de remise de la ligne
    *  @param		string		$price_base_type	HT or TTC
    *  @param		int			$info_bits			Bits de type de lignes
    *  @param		int			$fk_remise_except   Id remise
    *  @param		double		$pu_ttc			    Prix unitaire TTC (> 0 even for credit note)
    *  @param		int			$type				Type of line (0=product, 1=service)
    *  @param		int			$special_code		Special code
    *  @param		string		$label				Label of the line
    *  @param		string		$fk_unit			Unit
    *  @return		int			 				    <0 if KO, Id of line if OK
    */
    public function addline($desc, $duration, $datei, $rang = -1, $pu_ht = 0, $qty = 0, $txtva = 0, $fk_product = 0, $remise_percent = 0, $price_base_type = 'HT', $info_bits = 0, $fk_remise_except = '', $pu_ttc = 0, $type = 0, $special_code = 0, $label = '', $fk_unit = null)
    {
        global $mysoc;

        include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

        // Check parameters
        if ($type < 0) return -1;

        if ($this->brouillon) {
            // Clean parameters
            $remise_percent = price2num($remise_percent);
            $qty = price2num($qty);
            if (!$qty) $qty = 1;
            if (!$info_bits) $info_bits = 0;
            $pu_ht = price2num($pu_ht);
            $pu_ttc = price2num($pu_ttc);
            if (!preg_match('/\((.*)\)/', $txtva)) {
            	$txtva = price2num($txtva); // $txtva can have format '5.0(XXX)' or '5'
            }

            if ($price_base_type == 'HT') {
                $pu = $pu_ht;
            } else {
                $pu = $pu_ttc;
            }

            // Calcul du total TTC et de la TVA pour la ligne a partir de
            // qty, pu, remise_percent et txtva
            // TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
            // la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.
            $tabprice = calcul_price_total($qty, $pu, $remise_percent, $txtva, 0, 0, 0, $price_base_type, $info_bits, $type, $mysoc);

            $total_ht  = $tabprice[0];
            $total_tva = $tabprice[1];
            $total_ttc = $tabprice[2];

            $product_type = $type;
            if ($fk_product) {
                $product = new Product($this->db);
                $result = $product->fetch($fk_product);
                $product_type = $product->type;
            }

            $sql = "INSERT INTO ".MAIN_DB_PREFIX."fichinterdet_rec (";
            $sql .= "fk_fichinter";
            $sql .= ", label";
            $sql .= ", description";
            $sql .= ", date";
            $sql .= ", duree";
            //$sql.= ", price";
            //$sql.= ", qty";
            //$sql.= ", tva_tx";
            $sql .= ", fk_product";
            $sql .= ", product_type";
            $sql .= ", remise_percent";
            //$sql.= ", subprice";
            $sql .= ", remise";
            $sql .= ", total_ht";
            $sql .= ", total_tva";
            $sql .= ", total_ttc";
            $sql .= ", rang";
            //$sql.= ", special_code";
            $sql .= ", fk_unit";
            $sql .= ") VALUES (";
            $sql .= (int) $this->id;
            $sql .= ", ".(!empty($label) ? "'".$this->db->escape($label)."'" : "null");
            $sql .= ", ".(!empty($desc) ? "'".$this->db->escape($desc)."'" : "null");
            $sql .= ", ".(!empty($datei) ? "'".$this->db->idate($datei)."'" : "null");
            $sql .= ", ".$duration;
            //$sql.= ", ".price2num($pu_ht);
            //$sql.= ", ".(!empty($qty)? $qty :(!empty($duration)? $duration :"null"));
            //$sql.= ", ".price2num($txtva);
            $sql .= ", ".(!empty($fk_product) ? $fk_product : "null");
            $sql .= ", ".$product_type;
            $sql .= ", ".(!empty($remise_percent) ? $remise_percent : "null");
            //$sql.= ", '".price2num($pu_ht)."'";
            $sql .= ", null";
            $sql .= ", '".price2num($total_ht)."'";
            $sql .= ", '".price2num($total_tva)."'";
            $sql .= ", '".price2num($total_ttc)."'";
            $sql .= ", ".(int) $rang;
            //$sql.= ", ".$special_code;
            $sql .= ", ".(!empty($fk_unit) ? $fk_unit : "null");
            $sql .= ")";

            dol_syslog(get_class($this)."::addline", LOG_DEBUG);
            if ($this->db->query($sql)) {
                return 1;
            } else {
                $this->error = $this->db->lasterror();
                return -1;
            }
        }
    }


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *	Rend la fichinter automatique
     *
     *	@param		User	$user		User object
     *	@param		int		$freq		Freq
     *	@param		string	$courant	Courant
     *	@return		int					0 if OK, <0 if KO
     */
    public function set_auto($user, $freq, $courant)
    {
        // phpcs:enable
        if ($user->rights->fichinter->creer) {
            $sql = "UPDATE ".MAIN_DB_PREFIX."fichinter_rec ";
            $sql .= " SET frequency='".$this->db->escape($freq)."'";
            $sql .= ", date_last_gen='".$this->db->escape($courant)."'";
            $sql .= " WHERE rowid = ".$this->id;

            $resql = $this->db->query($sql);

            if ($resql) {
                $this->frequency = $freq;
                $this->date_last_gen = $courant;
                return 0;
            } else {
                dol_print_error($this->db);
                return -1;
            }
        } else {
            return -2;
        }
    }

    /**
     *  Return clicable name (with picto eventually)
     *
     *  @param	int		$withpicto      Add picto into link
     *  @param  string	$option		    Where point the link
     *  @param  int		$max			Maxlength of ref
     *  @param  int		$short		    1=Return just URL
     *  @param  string   $moretitle     Add more text to title tooltip
     *  @return string 					String with URL
     */
    public function getNomUrl($withpicto = 0, $option = '', $max = 0, $short = 0, $moretitle = '')
    {
        global $langs;

        $result = '';
        $label = $langs->trans("ShowInterventionModel").': '.$this->ref;

        $url = DOL_URL_ROOT.'/fichinter/card-rec.php?id='.$this->id;

        if ($short) return $url;

        $picto = 'intervention';

        $link = '<a href="'.$url.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
        $linkend = '</a>';

        if ($withpicto) {
            $result .= $link.img_object($label, $picto, 'class="classfortooltip"').$linkend;
        }
        if ($withpicto && $withpicto != 2) {
            $result .= ' ';
        }
        if ($withpicto != 2) {
            $result .= $link.$this->ref.$linkend;
        }
        return $result;
    }


    /**
     *  Initialise an instance with random values.
     *  Used to build previews or test instances.
     *	id must be 0 if object instance is a specimen.
     *
     *	@param	string		$option		''=Create a specimen fichinter with lines, 'nolines'=No lines
     *  @return	void
     */
    public function initAsSpecimen($option = '')
    {
        global $user, $langs, $conf;

        $now = dol_now();
        $arraynow = dol_getdate($now);
        $nownotime = dol_mktime(0, 0, 0, $arraynow['mon'], $arraynow['mday'], $arraynow['year']);

        parent::initAsSpecimen($option);

        $this->usenewprice = 1;
    }

    /**
     * Function used to replace a thirdparty id with another one.
     *
     * @param DoliDB $db Database handler
     * @param int $origin_id Old thirdparty id
     * @param int $dest_id New thirdparty id
     * @return bool
     */
    public static function replaceThirdparty(DoliDB $db, $origin_id, $dest_id)
    {
        $tables = array('fichinter_rec');

        return CommonObject::commonReplaceThirdparty($db, $origin_id, $dest_id, $tables);
    }


    /**
     *	Update frequency and unit
     *
     *	@param	 	int		$frequency		value of frequency
     *	@param	 	string	$unit 			unit of frequency  (d, m, y)
     *	@return		int						<0 if KO, >0 if OK
     */
    public function setFrequencyAndUnit($frequency, $unit)
    {
        if (!$this->table_element) {
            dol_syslog(get_class($this)."::setFrequencyAndUnit called with table_element not defined", LOG_ERR);
            return -1;
        }

        if (!empty($frequency) && empty($unit)) {
            dol_syslog(get_class($this)."::setFrequencyAndUnit called with frequency defined but unit not ", LOG_ERR);
            return -2;
        }

        $sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
        $sql .= ' SET frequency = '.($frequency ? $this->db->escape($frequency) : 'null');
        if (!empty($unit)) {
            $sql .= ', unit_frequency = "'.$this->db->escape($unit).'"';
        }
        $sql .= ' WHERE rowid = '.$this->id;

        dol_syslog(get_class($this)."::setFrequencyAndUnit", LOG_DEBUG);
        if ($this->db->query($sql)) {
            $this->frequency = $frequency;
            if (!empty($unit)) $this->unit_frequency = $unit;
            return 1;
        } else {
            dol_print_error($this->db);
            return -1;
        }
    }

    /**
     *	Update the next date of execution
     *
     *	@param	 	datetime	$date					date of execution
     *	@param	 	int			$increment_nb_gen_done	0 do nothing more, >0 increment nb_gen_done
     *	@return		int									<0 if KO, >0 if OK
     */
    public function setNextDate($date, $increment_nb_gen_done = 0)
    {
        if (!$this->table_element) {
            dol_syslog(get_class($this)."::setNextDate was called on objet with property table_element not defined", LOG_ERR);
            return -1;
        }
        $sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
        $sql .= " SET date_when = ".($date ? "'".$this->db->idate($date)."'" : "null");
        if ($increment_nb_gen_done > 0) $sql .= ', nb_gen_done = nb_gen_done + 1';
        $sql .= ' WHERE rowid = '.$this->id;

        dol_syslog(get_class($this)."::setNextDate", LOG_DEBUG);
        if ($this->db->query($sql)) {
            $this->date_when = $date;
            if ($increment_nb_gen_done > 0) $this->nb_gen_done++;
            return 1;
        } else {
            dol_print_error($this->db);
            return -1;
        }
    }

    /**
     *	Update the maximum period
     *
     *	@param	 	int		$nb		number of maximum period
     *	@return		int				<0 if KO, >0 if OK
     */
    public function setMaxPeriod($nb)
    {
        if (!$this->table_element) {
            dol_syslog(get_class($this)."::setMaxPeriod was called on objet with property table_element not defined", LOG_ERR);
            return -1;
        }

        if (empty($nb)) $nb = 0;

        $sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
        $sql .= ' SET nb_gen_max = '.$nb;
        $sql .= ' WHERE rowid = '.$this->id;

        dol_syslog(get_class($this)."::setMaxPeriod", LOG_DEBUG);
        if ($this->db->query($sql)) {
            $this->nb_gen_max = $nb;
            return 1;
        } else {
            dol_print_error($this->db);
            return -1;
        }
    }

    /**
     *	Update the auto validate fichinter
     *
     *	@param	 	int		$validate		0 to create in draft, 1 to create and validate fichinter
     *	@return		int						<0 if KO, >0 if OK
     */
    public function setAutoValidate($validate)
    {
        if (!$this->table_element) {
            dol_syslog(get_class($this)."::setAutoValidate called with property table_element not defined", LOG_ERR);
            return -1;
        }

        $sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
        $sql .= ' SET auto_validate = '.$validate;
        $sql .= ' WHERE rowid = '.$this->id;

        dol_syslog(get_class($this)."::setAutoValidate", LOG_DEBUG);
        if ($this->db->query($sql)) {
            $this->auto_validate = $validate;
            return 1;
        } else {
            dol_print_error($this->db);
            return -1;
        }
    }

    /**
     *	Update the Number of Generation Done
     *
     *	@return		int						<0 if KO, >0 if OK
     */
    public function updateNbGenDone()
    {
        if (!$this->table_element) {
            dol_syslog(get_class($this)."::updateNbGenDone called with property table_element not defined", LOG_ERR);
            return -1;
        }

        $sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
        $sql .= ' SET nb_gen_done = nb_gen_done + 1';
        $sql .= ' , date_last_gen = now()';
        // si on et arrivé à la fin des génération
        if ($this->nb_gen_max == $this->nb_gen_done + 1)
            $sql .= ' , statut = 1';

        $sql .= ' WHERE rowid = '.$this->id;

        dol_syslog(get_class($this)."::setAutoValidate", LOG_DEBUG);
        if ($this->db->query($sql)) {
            $this->nb_gen_done = $this->nb_gen_done + 1;
            $this->nb_gen_done = dol_now();
            return 1;
        } else {
            dol_print_error($this->db);
            return -1;
        }
    }
}
