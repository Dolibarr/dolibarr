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
 * Class ot manage invoices for pos module (cashdesk)
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
    public $id;
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


    // Methodes de traitement des donnees


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

        // Calcul du total ht sans remise
        $total_ht = $resultarray[0];
        $total_vat = $resultarray[1];
        $total_ttc = $resultarray[2];
        $total_localtax1 = $resultarray[9];
        $total_localtax2 = $resultarray[10];

        // Calcul du montant de la remise
        if ($this->remisePercent())
        {
            $remise_percent = $this->remisePercent();
        } else {
            $remise_percent = 0;
        }
        $montant_remise_ht = ($resultarray[6] - $resultarray[0]);
        $this->montantRemise($montant_remise_ht);

        $newcartarray=$_SESSION['poscart'];
        $i=count($newcartarray);

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
        $newcartarray[$i]['total_ht']=price2num($total_ht,'MT');
        $newcartarray[$i]['total_ttc']=price2num($total_ttc,'MT');
        $newcartarray[$i]['total_vat']=price2num($total_vat, 'MT');
        $newcartarray[$i]['total_localtax1']=price2num($total_localtax1, 'MT');
        $newcartarray[$i]['total_localtax2']=price2num($total_localtax2, 'MT');
        $_SESSION['poscart']=$newcartarray;

        $this->raz();

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
     * Calcul du total HT, total TTC et montants TVA
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

        $tab=array();
        $tab = $_SESSION['poscart'];

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
     * Reinitialisation des attributs
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
     * Reinitialisation des attributs persistants
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

    }


    // Methodes de modification des attributs proteges

    /**
     * Getter for id
     *
     * @param	int		$aId	Id
     * @return  id
     */
    public function id($aId=null)
    {

        if ( !$aId )
        {
            return $this->id;

        }
        else if ( $aId == 'RESET' )
        {

            $this->id = NULL;

        }
        else
        {

            $this->id = $aId;

        }
    }

    /**
     * Getter for ref
     *
     * @param	string	$aRef	Ref
     * @return	string			Ref
     */
    public function ref($aRef=null)
     {

        if (is_null($aRef))
        {
            return $this->ref;
        }
        else if ( $aRef == 'RESET' )
        {
            $this->ref = NULL;
        }
        else
        {
            $this->ref = $aRef;
        }

    }

    /**
     * Getter for qte
     *
     * @param	int		$aQte		Qty
     * @return	int					Qty
     */
    public function qte($aQte=null)
    {
        if (is_null($aQte))
        {
            return $this->qte;
        }
        else if ( $aQte == 'RESET' )
        {

            $this->qte = NULL;
        }
        else
        {
            $this->qte = $aQte;
        }

    }

    /**
     * Getter for stock
     *
     * @param   string	$aStock		Stock
     * @return	string				Stock
     */
    public function stock($aStock=null)
    {

        if (is_null($aStock))
        {
            return $this->stock;
        }
        else if ( $aStock == 'RESET' )
        {
            $this->stock = NULL;
        }
        else
        {
            $this->stock = $aStock;
        }

    }

    /**
     * Getter for remise_percent
     *
     * @param	string	$aRemisePercent		Discount
     * @return	string						Discount
     */
    public function remisePercent($aRemisePercent=null)
    {

        if (is_null($aRemisePercent))
        {
            return $this->remise_percent;
        }
        else if ($aRemisePercent == 'RESET')
        {
            $this->remise_percent = NULL;
        }
        else
        {
            $this->remise_percent = $aRemisePercent;
        }

    }

    /**
     * Getter for montant_remise
     *
     * @param	int		$aMontantRemise		Amount
     * @return	string						Amount
     */
    public function montantRemise($aMontantRemise=null)
    {

        if (is_null($aMontantRemise)) {

            return $this->montant_remise;

        } else if ( $aMontantRemise == 'RESET' ) {

            $this->montant_remise = NULL;

        } else {

            $this->montant_remise = $aMontantRemise;

        }

    }

    /**
     * Getter for prix
     *
     * @param	int		$aPrix		Price
     * @return	string				Stock
     */
    public function prix($aPrix=null)
    {

        if (is_null($aPrix)) {

            return $this->prix;

        } else if ( $aPrix == 'RESET' ) {

            $this->prix = NULL;

        } else {

            $this->prix = $aPrix;

        }

    }

    /**
     * Getter for tva
     *
     * @param	int		$aTva		Vat
     * @return	int					Vat
     */
    public function tva($aTva=null)
    {
        if (is_null($aTva)) {

            return $this->tva;

        } else if ( $aTva == 'RESET' ) {

            $this->tva = NULL;

        } else {

            $this->tva = $aTva;

        }

    }

    /**
     * Get num invoice
     *
     * @param string	$aNumFacture		Invoice ref
     * @return	string						Invoice ref
     */
    public function numInvoice($aNumFacture=null)
    {
        if (is_null($aNumFacture)) {

            return $this->num_facture;

        } else if ( $aNumFacture == 'RESET' ) {

            $this->num_facture = NULL;

        } else {

            $this->num_facture = $aNumFacture;

        }
    }

    /**
     * Get payment mode
     *
     * @param	int		$aModeReglement		Payment mode
     * @return	int							Payment mode
     */
    public function getSetPaymentMode($aModeReglement=null)
    {

        if (is_null($aModeReglement)) {

            return $this->mode_reglement;

        } else if ( $aModeReglement == 'RESET' ) {

            $this->mode_reglement = NULL;

        } else {

            $this->mode_reglement = $aModeReglement;

        }

    }

    /**
     * Get amount
     *
     * @param	int		$aMontantEncaisse		Amount
     * @return	int								Amount
     */
    public function montantEncaisse($aMontantEncaisse=null)
    {

        if (is_null($aMontantEncaisse)) {

            return $this->montant_encaisse;

        } else if ( $aMontantEncaisse == 'RESET' ) {

            $this->montant_encaisse = NULL;

        } else {

            $this->montant_encaisse = $aMontantEncaisse;

        }

    }

    /**
     * Get amount
     *
     * @param	int			$aMontantRendu		Amount
     * @return	int								Amount
     */
    public function montantRendu($aMontantRendu=null)
    {

        if (is_null($aMontantRendu)) {

            return $this->montant_rendu;
        } else if ( $aMontantRendu == 'RESET' ) {

            $this->montant_rendu = NULL;

        } else {

            $this->montant_rendu = $aMontantRendu;

        }

    }

    /**
     * Get payment date
     *
     * @param	date		$aPaiementLe		Date
     * @return	date							Date
     */
    public function paiementLe($aPaiementLe=null)
    {
        if (is_null($aPaiementLe)) {

            return $this->paiement_le;

        } else if ( $aPaiementLe == 'RESET' ) {

            $this->paiement_le = NULL;

        } else {

            $this->paiement_le = $aPaiementLe;

        }
    }

    /**
     * Get totla HT
     *
     * @param	int		$aTotalHt		Total amount
     * @return	int						Total amount
     */
    public function prixTotalHt($aTotalHt=null)
    {
        if (is_null($aTotalHt)) {

            return $this->prix_total_ht;

        } else if ( $aTotalHt == 'RESET' ) {

            $this->prix_total_ht = NULL;

        } else {

            $this->prix_total_ht = $aTotalHt;

        }
    }

    /**
     * Get amount vat
     *
     * @param	int		$aMontantTva	Amount vat
     * @return	int						Amount vat
     */
    public function montantTva($aMontantTva=null)
    {
        if (is_null($aMontantTva)) {

            return $this->montant_tva;

        } else if ( $aMontantTva == 'RESET' ) {

            $this->montant_tva = NULL;

        } else {

            $this->montant_tva = $aMontantTva;

        }

    }

    /**
     * Get total TTC
     *
     * @param	int		$aTotalTtc		Amount ttc
     * @return	int						Amount ttc
     */
    public function prixTotalTtc($aTotalTtc=null)
    {
        if (is_null($aTotalTtc))
        {
            return $this->prix_total_ttc;
        }
        else if ( $aTotalTtc == 'RESET' )
        {
            $this->prix_total_ttc = NULL;
        }
        else
        {
            $this->prix_total_ttc = $aTotalTtc;
        }
    }

}

