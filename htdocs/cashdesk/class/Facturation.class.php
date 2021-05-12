<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier <jeremie.o@laposte.net>
 * Copyright (C) 2008-2010 Laurent Destailleur   <eldy@uers.sourceforge.net>
 * Copyright (C) 2010      Juanjo Menent    <jmenent@2byte.es>
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

include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';


/**
<<<<<<< HEAD
 * Class ot manage invoices for pos module (cashdesk)
=======
 * Class to manage invoices for pos module (cashdesk)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 */
class Facturation
{
    /**
     * Attributs "volatiles" : reinitialises apres chaque traitement d'un article
     * <p>Attributs "volatiles" : reinitialises apres chaque traitement d'un article</p>
     * int $id			=> 'rowid' du produit dans llx_product
     * string $ref		=> 'ref' du produit dans llx_product
     * int $qte			=> Quantite pour le produit en cours de traitement
     * int $stock		=> Stock theorique pour le produit en cours de traitement
     * int $remise_percent	=> Remise en pourcent sur le produit en cours
     * int $montant_remise	=> Remise en pourcent sur le produit en cours
     * int $prix		=> Prix HT du produit en cours
     * int $tva			=> 'rowid' du taux de tva dans llx_c_tva
     */
<<<<<<< HEAD
    public $id;
=======

    /**
	 * @var int ID
	 */
	public $id;

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    protected $ref;
    protected $qte;
    protected $stock;
    protected $remise_percent;
    protected $montant_remise;
    protected $prix;
    protected $tva;

    /**
     * Attributs persistants : utilises pour toute la duree de la vente (jusqu'a validation ou annulation)
     * string $num_facture	=> Numero de la facture (de la forme FAYYMM-XXXX)
     * string $mode_reglement	=> Mode de reglement (ESP, CB ou CHQ)
     * int $montant_encaisse	=> Montant encaisse en cas de reglement en especes
     * int $montant_rendu	=> Monnaie rendue en cas de reglement en especes
     * int $paiement_le		=> Date de paiement en cas de paiement differe
     *
     * int $prix_total_ht	=> Prix total hors taxes
     * int $montant_tva		=> Montant total de la TVA, tous taux confondus
     * int $prix_total_ttc	=> Prix total TTC
     */
    protected $num_facture;
    protected $mode_reglement;
    protected $montant_encaisse;
    protected $montant_rendu;
    protected $paiement_le;

    protected $prix_total_ht;
    protected $montant_tva;
    protected $prix_total_ttc;


    /**
     *	Constructor
     */
    public function __construct()
    {
        $this->raz();
        $this->razPers();
    }


<<<<<<< HEAD
    // Methodes de traitement des donnees
=======
    // Data processing methods
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9


    /**
     *  Add a product into cart
     *
     *  @return	void
     */
    public function ajoutArticle()
    {
        global $conf,$db,$mysoc;

        $thirdpartyid = $_SESSION['CASHDESK_ID_THIRDPARTY'];

        $societe = new Societe($db);
        $societe->fetch($thirdpartyid);

        $product = new Product($db);
        $product->fetch($this->id);


        $vatrowid = $this->tva();

        $tmp = getTaxesFromId($vatrowid);
        $txtva = $tmp['rate'].(empty($tmp['code'])?'':' ('.$tmp['code'].')');
        $vat_npr = $tmp['npr'];

        $localtaxarray = getLocalTaxesFromRate($vatrowid, 0, $societe, $mysoc, 1);

        // Clean vat code
        $vat_src_code='';
        if (preg_match('/\((.*)\)/', $txtva, $reg))
        {
            $vat_src_code = $reg[1];
            $txtva = preg_replace('/\s*\(.*\)/', '', $txtva);    // Remove code into vatrate.
        }

        // Define part of HT, VAT, TTC
        $resultarray=calcul_price_total($this->qte, $this->prix(), $this->remisePercent(), $txtva, -1, -1, 0, 'HT', $vat_npr, $product->type, $mysoc, $localtaxarray);

<<<<<<< HEAD
        // Calcul du total ht sans remise
=======
        // Calculation of total HT without discount
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        $total_ht = $resultarray[0];
        $total_vat = $resultarray[1];
        $total_ttc = $resultarray[2];
        $total_localtax1 = $resultarray[9];
        $total_localtax2 = $resultarray[10];

<<<<<<< HEAD
        // Calcul du montant de la remise
=======
        // Calculation of the discount amount
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        if ($this->remisePercent())
        {
            $remise_percent = $this->remisePercent();
        } else {
            $remise_percent = 0;
        }
        $montant_remise_ht = ($resultarray[6] - $resultarray[0]);
        $this->montantRemise($montant_remise_ht);

        $newcartarray=$_SESSION['poscart'];
<<<<<<< HEAD
        $i=count($newcartarray);
=======

        $i = 0;
        if (!is_null($newcartarray) && !empty($newcartarray)) {
            $i=count($newcartarray);
        }
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

        $newcartarray[$i]['id']=$i;
        $newcartarray[$i]['ref']=$product->ref;
        $newcartarray[$i]['label']=$product->label;
        $newcartarray[$i]['price']=$product->price;
        $newcartarray[$i]['price_ttc']=$product->price_ttc;

        if (! empty($conf->global->PRODUIT_MULTIPRICES))
        {
            if (isset($product->multiprices[$societe->price_level]))
            {
                $newcartarray[$i]['price'] = $product->multiprices[$societe->price_level];
                $newcartarray[$i]['price_ttc'] = $product->multiprices_ttc[$societe->price_level];
            }
        }

        $newcartarray[$i]['fk_article']=$this->id;
        $newcartarray[$i]['qte']=$this->qte();
        $newcartarray[$i]['fk_tva']=$this->tva();   // Vat rowid
        $newcartarray[$i]['remise_percent']=$remise_percent;
        $newcartarray[$i]['remise']=price2num($montant_remise_ht);
<<<<<<< HEAD
        $newcartarray[$i]['total_ht']=price2num($total_ht,'MT');
        $newcartarray[$i]['total_ttc']=price2num($total_ttc,'MT');
=======
        $newcartarray[$i]['total_ht']=price2num($total_ht, 'MT');
        $newcartarray[$i]['total_ttc']=price2num($total_ttc, 'MT');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        $newcartarray[$i]['total_vat']=price2num($total_vat, 'MT');
        $newcartarray[$i]['total_localtax1']=price2num($total_localtax1, 'MT');
        $newcartarray[$i]['total_localtax2']=price2num($total_localtax2, 'MT');
        $_SESSION['poscart']=$newcartarray;

        $this->raz();
<<<<<<< HEAD

=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    }

    /**
     *  Remove a product from panel
     *
     *  @param  int		$aArticle	Id of line into cart to remove
     *  @return	void
     */
    public function supprArticle($aArticle)
    {
        $poscart=$_SESSION['poscart'];

        $j=0;
        $newposcart=array();
        foreach($poscart as $key => $val)
        {
            if ($poscart[$key]['id'] != $aArticle)
            {
               $newposcart[$j]=$poscart[$key];
               $newposcart[$j]['id']=$j;
               $j++;
            }
        }
        unset($poscart);
        //var_dump($poscart);exit;
        $_SESSION['poscart']=$newposcart;
    }

    /**
<<<<<<< HEAD
     * Calcul du total HT, total TTC et montants TVA
=======
     * Calculation of total HT, total TTC and VAT amounts
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
     *
     * @return	int		Total
     */
    public function calculTotaux()
    {
        global $db;

        $total_ht=0;
        $total_ttc=0;
        $total_vat = 0;
        $total_localtax1 = 0;
        $total_localtax2 = 0;

<<<<<<< HEAD
        $tab=array();
        $tab = $_SESSION['poscart'];
=======
        $tab = (! empty($_SESSION['poscart'])?$_SESSION['poscart']:array());
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

        $tab_size=count($tab);
        for($i=0;$i < $tab_size;$i++)
        {
            // Total HT
            $remise = $tab[$i]['remise'];
            $total_ht += ($tab[$i]['total_ht']);
            $total_vat += ($tab[$i]['total_vat']);
            $total_ttc += ($tab[$i]['total_ttc']);
            $total_localtax1 += ($tab[$i]['total_localtax1']);
            $total_localtax2 += ($tab[$i]['total_localtax2']);
        }

        $this->prix_total_ttc = $total_ttc;
        $this->prix_total_ht = $total_ht;
        $this->prix_total_vat = $total_vat;
        $this->prix_total_localtax1 = $total_localtax1;
        $this->prix_total_localtax2 = $total_localtax2;

        $this->montant_tva = $total_ttc - $total_ht;
        //print 'total: '.$this->prix_total_ttc; exit;
    }

    /**
<<<<<<< HEAD
     * Reinitialisation des attributs
=======
     * Reset attributes
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
     *
     * @return	void
     */
    public function raz()
    {
        $this->id('RESET');
        $this->ref('RESET');
        $this->qte('RESET');
        $this->stock('RESET');
        $this->remisePercent('RESET');
        $this->montantRemise('RESET');
        $this->prix('RESET');
        $this->tva('RESET');
    }

    /**
<<<<<<< HEAD
     * Reinitialisation des attributs persistants
=======
     * Resetting persistent attributes
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
     *
     *  @return	void
     */
    private function razPers()
    {
        $this->numInvoice('RESET');
        $this->getSetPaymentMode('RESET');
        $this->montantEncaisse('RESET');
        $this->montantRendu('RESET');
        $this->paiementLe('RESET');

        $this->prixTotalHt('RESET');
        $this->montantTva('RESET');
        $this->prixTotalTtc('RESET');
<<<<<<< HEAD

    }


    // Methodes de modification des attributs proteges
=======
    }


    // Methods for modifying protected attributes
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

    /**
     * Getter for id
     *
     * @param	int		$aId	Id
<<<<<<< HEAD
     * @return  id
     */
    public function id($aId=null)
=======
     * @return  int             Id
     */
    public function id($aId = null)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {

        if ( !$aId )
        {
            return $this->id;
<<<<<<< HEAD

        }
        else if ( $aId == 'RESET' )
        {

            $this->id = null;

=======
        }
        elseif ( $aId == 'RESET' )
        {

            $this->id = null;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        }
        else
        {

            $this->id = $aId;
<<<<<<< HEAD

=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        }
    }

    /**
     * Getter for ref
     *
     * @param	string	$aRef	Ref
     * @return	string			Ref
     */
<<<<<<< HEAD
    public function ref($aRef=null)
     {
=======
    public function ref($aRef = null)
    {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

        if (is_null($aRef))
        {
            return $this->ref;
        }
<<<<<<< HEAD
        else if ( $aRef == 'RESET' )
=======
        elseif ( $aRef == 'RESET' )
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        {
            $this->ref = null;
        }
        else
        {
            $this->ref = $aRef;
        }
<<<<<<< HEAD

=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    }

    /**
     * Getter for qte
     *
     * @param	int		$aQte		Qty
     * @return	int					Qty
     */
<<<<<<< HEAD
    public function qte($aQte=null)
=======
    public function qte($aQte = null)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
        if (is_null($aQte))
        {
            return $this->qte;
        }
<<<<<<< HEAD
        else if ( $aQte == 'RESET' )
=======
        elseif ( $aQte == 'RESET' )
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        {

            $this->qte = null;
        }
        else
        {
            $this->qte = $aQte;
        }
<<<<<<< HEAD

=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    }

    /**
     * Getter for stock
     *
     * @param   string	$aStock		Stock
     * @return	string				Stock
     */
<<<<<<< HEAD
    public function stock($aStock=null)
=======
    public function stock($aStock = null)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {

        if (is_null($aStock))
        {
            return $this->stock;
        }
<<<<<<< HEAD
        else if ( $aStock == 'RESET' )
=======
        elseif ( $aStock == 'RESET' )
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        {
            $this->stock = null;
        }
        else
        {
            $this->stock = $aStock;
        }
<<<<<<< HEAD

=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    }

    /**
     * Getter for remise_percent
     *
     * @param	string	$aRemisePercent		Discount
     * @return	string						Discount
     */
<<<<<<< HEAD
    public function remisePercent($aRemisePercent=null)
=======
    public function remisePercent($aRemisePercent = null)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {

        if (is_null($aRemisePercent))
        {
            return $this->remise_percent;
        }
<<<<<<< HEAD
        else if ($aRemisePercent == 'RESET')
=======
        elseif ($aRemisePercent == 'RESET')
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        {
            $this->remise_percent = null;
        }
        else
        {
            $this->remise_percent = $aRemisePercent;
        }
<<<<<<< HEAD

=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    }

    /**
     * Getter for montant_remise
     *
     * @param	int		$aMontantRemise		Amount
     * @return	string						Amount
     */
<<<<<<< HEAD
    public function montantRemise($aMontantRemise=null)
=======
    public function montantRemise($aMontantRemise = null)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {

        if (is_null($aMontantRemise)) {

            return $this->montant_remise;
<<<<<<< HEAD

        } else if ( $aMontantRemise == 'RESET' ) {

            $this->montant_remise = null;

        } else {

            $this->montant_remise = $aMontantRemise;

        }

=======
        } elseif ( $aMontantRemise == 'RESET' ) {

            $this->montant_remise = null;
        } else {

            $this->montant_remise = $aMontantRemise;
        }
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    }

    /**
     * Getter for prix
     *
     * @param	int		$aPrix		Price
     * @return	string				Stock
     */
<<<<<<< HEAD
    public function prix($aPrix=null)
=======
    public function prix($aPrix = null)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {

        if (is_null($aPrix)) {

            return $this->prix;
<<<<<<< HEAD

        } else if ( $aPrix == 'RESET' ) {

            $this->prix = null;

        } else {

            $this->prix = $aPrix;

        }

=======
        } elseif ( $aPrix == 'RESET' ) {

            $this->prix = null;
        } else {

            $this->prix = $aPrix;
        }
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    }

    /**
     * Getter for tva
     *
     * @param	int		$aTva		Vat
     * @return	int					Vat
     */
<<<<<<< HEAD
    public function tva($aTva=null)
=======
    public function tva($aTva = null)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
        if (is_null($aTva)) {

            return $this->tva;
<<<<<<< HEAD

        } else if ( $aTva == 'RESET' ) {

            $this->tva = null;

        } else {

            $this->tva = $aTva;

        }

=======
        } elseif ( $aTva == 'RESET' ) {

            $this->tva = null;
        } else {

            $this->tva = $aTva;
        }
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    }

    /**
     * Get num invoice
     *
     * @param string	$aNumFacture		Invoice ref
     * @return	string						Invoice ref
     */
<<<<<<< HEAD
    public function numInvoice($aNumFacture=null)
=======
    public function numInvoice($aNumFacture = null)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
        if (is_null($aNumFacture)) {

            return $this->num_facture;
<<<<<<< HEAD

        } else if ( $aNumFacture == 'RESET' ) {

            $this->num_facture = null;

        } else {

            $this->num_facture = $aNumFacture;

=======
        } elseif ( $aNumFacture == 'RESET' ) {

            $this->num_facture = null;
        } else {

            $this->num_facture = $aNumFacture;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        }
    }

    /**
     * Get payment mode
     *
     * @param	int		$aModeReglement		Payment mode
     * @return	int							Payment mode
     */
<<<<<<< HEAD
    public function getSetPaymentMode($aModeReglement=null)
=======
    public function getSetPaymentMode($aModeReglement = null)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {

        if (is_null($aModeReglement)) {

            return $this->mode_reglement;
<<<<<<< HEAD

        } else if ( $aModeReglement == 'RESET' ) {

            $this->mode_reglement = null;

        } else {

            $this->mode_reglement = $aModeReglement;

        }

=======
        } elseif ( $aModeReglement == 'RESET' ) {

            $this->mode_reglement = null;
        } else {

            $this->mode_reglement = $aModeReglement;
        }
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    }

    /**
     * Get amount
     *
     * @param	int		$aMontantEncaisse		Amount
     * @return	int								Amount
     */
<<<<<<< HEAD
    public function montantEncaisse($aMontantEncaisse=null)
=======
    public function montantEncaisse($aMontantEncaisse = null)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {

        if (is_null($aMontantEncaisse)) {

            return $this->montant_encaisse;
<<<<<<< HEAD

        } else if ( $aMontantEncaisse == 'RESET' ) {

            $this->montant_encaisse = null;

        } else {

            $this->montant_encaisse = $aMontantEncaisse;

        }

=======
        } elseif ( $aMontantEncaisse == 'RESET' ) {

            $this->montant_encaisse = null;
        } else {

            $this->montant_encaisse = $aMontantEncaisse;
        }
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    }

    /**
     * Get amount
     *
     * @param	int			$aMontantRendu		Amount
     * @return	int								Amount
     */
<<<<<<< HEAD
    public function montantRendu($aMontantRendu=null)
=======
    public function montantRendu($aMontantRendu = null)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {

        if (is_null($aMontantRendu)) {

            return $this->montant_rendu;
<<<<<<< HEAD
        } else if ( $aMontantRendu == 'RESET' ) {

            $this->montant_rendu = null;

        } else {

            $this->montant_rendu = $aMontantRendu;

        }

=======
        } elseif ( $aMontantRendu == 'RESET' ) {

            $this->montant_rendu = null;
        } else {

            $this->montant_rendu = $aMontantRendu;
        }
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    }

    /**
     * Get payment date
     *
<<<<<<< HEAD
     * @param	date		$aPaiementLe		Date
     * @return	date							Date
     */
    public function paiementLe($aPaiementLe=null)
=======
     * @param	integer		$aPaiementLe		Date
     * @return	integer							Date
     */
    public function paiementLe($aPaiementLe = null)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
        if (is_null($aPaiementLe)) {

            return $this->paiement_le;
<<<<<<< HEAD

        } else if ( $aPaiementLe == 'RESET' ) {

            $this->paiement_le = null;

        } else {

            $this->paiement_le = $aPaiementLe;

=======
        } elseif ( $aPaiementLe == 'RESET' ) {

            $this->paiement_le = null;
        } else {

            $this->paiement_le = $aPaiementLe;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        }
    }

    /**
<<<<<<< HEAD
     * Get totla HT
=======
     * Get total HT
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
     *
     * @param	int		$aTotalHt		Total amount
     * @return	int						Total amount
     */
<<<<<<< HEAD
    public function prixTotalHt($aTotalHt=null)
=======
    public function prixTotalHt($aTotalHt = null)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
        if (is_null($aTotalHt)) {

            return $this->prix_total_ht;
<<<<<<< HEAD

        } else if ( $aTotalHt == 'RESET' ) {

            $this->prix_total_ht = null;

        } else {

            $this->prix_total_ht = $aTotalHt;

=======
        } elseif ( $aTotalHt == 'RESET' ) {

            $this->prix_total_ht = null;
        } else {

            $this->prix_total_ht = $aTotalHt;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        }
    }

    /**
     * Get amount vat
     *
     * @param	int		$aMontantTva	Amount vat
     * @return	int						Amount vat
     */
<<<<<<< HEAD
    public function montantTva($aMontantTva=null)
=======
    public function montantTva($aMontantTva = null)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
        if (is_null($aMontantTva)) {

            return $this->montant_tva;
<<<<<<< HEAD

        } else if ( $aMontantTva == 'RESET' ) {

            $this->montant_tva = null;

        } else {

            $this->montant_tva = $aMontantTva;

        }

=======
        } elseif ( $aMontantTva == 'RESET' ) {

            $this->montant_tva = null;
        } else {

            $this->montant_tva = $aMontantTva;
        }
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    }

    /**
     * Get total TTC
     *
     * @param	int		$aTotalTtc		Amount ttc
     * @return	int						Amount ttc
     */
<<<<<<< HEAD
    public function prixTotalTtc($aTotalTtc=null)
=======
    public function prixTotalTtc($aTotalTtc = null)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
        if (is_null($aTotalTtc))
        {
            return $this->prix_total_ttc;
        }
<<<<<<< HEAD
        else if ( $aTotalTtc == 'RESET' )
=======
        elseif ( $aTotalTtc == 'RESET' )
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        {
            $this->prix_total_ttc = null;
        }
        else
        {
            $this->prix_total_ttc = $aTotalTtc;
        }
    }
<<<<<<< HEAD

}

=======
}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
