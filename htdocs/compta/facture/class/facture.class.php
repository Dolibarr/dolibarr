<?php
/* Copyright (C) 2002-2007  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2004       Sebastien Di Cintio     <sdicintio@ressource-toi.org>
 * Copyright (C) 2004       Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2005       Marc Barilley / Ocebo   <marc@ocebo.com>
 * Copyright (C) 2005-2014  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2006       Andre Cianfarani        <acianfa@free.fr>
 * Copyright (C) 2007       Franky Van Liedekerke   <franky.van.liedekerke@telenet.be>
 * Copyright (C) 2010-2020  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2012-2014  Christophe Battarel     <christophe.battarel@altairis.fr>
 * Copyright (C) 2012-2015  Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2012       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013       Cedric Gross            <c.gross@kreiz-it.fr>
 * Copyright (C) 2013       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2016-2022  Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2018-2022  Alexandre Spangaro		<aspangaro@open-dsi.fr>
 * Copyright (C) 2018       Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2022       Sylvain Legrand         <contact@infras.fr>
 * Copyright (C) 2022      	Gauthier VERDOL       	<gauthier.verdol@atm-consulting.fr>
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
 */

/**
 *	\file       htdocs/compta/facture/class/facture.class.php
 *	\ingroup    facture
 *	\brief      File of class to manage invoices
 *	@deprecated use instead invoice.class.php
 */

include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/invoice.class.php';

/**
 *	Class to manage invoices
 *	@deprecated use instead invoice.class.php
 */
class Facture extends Invoice {}


/**
 *	Class to manage invoice lines.
 *  Saved into database table llx_facturedet
 *  @deprecated use instead invoice.class.php
 */
class FactureLigne extends InvoiceLine {}
