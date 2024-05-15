-- Copyright (C) 2001-2004  Rodolphe Quiedeville  <rodolphe@quiedeville.org>
-- Copyright (C) 2003       Jean-Louis Bergamo    <jlb@j1b.org>
-- Copyright (C) 2004-2012  Laurent Destailleur   <eldy@users.sourceforge.net>
-- Copyright (C) 2004       Benoit Mortier        <benoit.mortier@opensides.be>
-- Copyright (C) 2004       Guillaume Delecourt   <guillaume.delecourt@opensides.be>
-- Copyright (C) 2005-2012  Regis Houssin         <regis.houssin@inodbox.com>
-- Copyright (C) 2007 	    Patrick Raguin        <patrick.raguin@gmail.com>
-- Copyright (C) 2011 	    Juanjo Menent         <jmenent@2byte.es>
-- Copyright (C) 2020 	    Udo Tamm              <dev@dolibit.de>
-- Copyright (C) 2022 	    Éric Seigne           <eric.seigne@cap-rel.fr>
-- Copyright (C) 2021 	    Lenin Rivas           <lenin@leninrivas.com>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <https://www.gnu.org/licenses/>.
--
-----------------------------------------------------------------------------

--
-- Do not place a comment at the end of the line, this file is parsed when
-- of the install and all the acronyms '--' are removed.
--
-- Ne pas placer de commentaire en fin de ligne, ce fichier est parsé lors
-- de l'install et tous les sigles '--' sont supprimés.
--

--
-- Currencies (primary code - code ISO4217 - libelle us)
--

INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'ALL', '[76,101,107]', 1,	  'Albania Lek');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'DZD', NULL, 1,              'Algeria Dinar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'AOA', NULL, 1,					    'Angola Kwanza');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'AFN', '[1547]', 1,				  'Afghanistan Afghani');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'ARS', '[36]', 1,            'Argentino Peso');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'AWG', '[402]', 1,				    'Aruba Guilder');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'AUD', '[36]', 1,				    'Australia Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'AZN', '[1084,1072,1085]', 1, 'Azerbaijan New Manat');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'BHD', NULL, 1,              'Bahrain');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'BSD', '[36]', 1,				    'Bahamas Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'BBD', '[36]', 1,				    'Barbados Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'BDT', '[2547]', 1,			    'Bangladeshi Taka');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'BYR', '[112,46]', 1,			  'Belarus Ruble');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'BZD', '[66,90,36]', 1,			'Belize Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'BMD', '[36]', 1,				    'Bermuda Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'BOB', '[66,115]', 1,				'Bolivia Boliviano');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'BAM', '[75,77]', 1,			  	'Bosnia and Herzegovina Convertible Marka');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'BWP', '[80]', 1,				    'Botswana Pula');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'BGN', '[1083,1074]', 1,			'Bulgaria Lev');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'BRL', '[82,36]', 1,				  'Brazil Real');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'BND', '[36]', 1,				    'Brunei Darussalam Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'BIF', NULL, 1,				    'Burundi Franc');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'KHR', '[6107]', 1,			  	'Cambodia Riel');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'CAD', '[36]', 1,				    'Canada Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'CVE', '[4217]', 1,          'Cap Verde Escudo');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'KYD', '[36]', 1,	    			'Cayman Islands Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'CLP', '[36]', 1,			    	'Chile Peso');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'CNY', '[165]', 1,			    	'China Yuan Renminbi'); 
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'COP', '[36]', 1,	    			'Colombia Peso');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'CRC', '[8353]', 1,		  		'Costa Rica Colon');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'HRK', '[107,110]', 1,		  	'Croatia Kuna');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'CUP', '[8369]', 1,		  		'Cuba Peso');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'CZK', '[75,269]', 1,		  	'Czech Republic Koruna');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'DKK', '[107,114]', 1,	  		'Denmark Krone');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'DOP', '[82,68,36]', 1,			'Dominican Republic Peso');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'XCD', '[36]', 1,		    		'East Caribbean Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'ECS', '[83,47,46]', 1,		  'Ecuador Sucre');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'EGP', '[163]', 1,	    			'Egypt Pound');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'SVC', '[36]', 1,			    	'El Salvador Colon');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'EEK', '[107,114]', 1,	  		'Estonia Kroon');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'ETB', NULL, 1,		  		    'Ethiopian Birr'); 
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'EUR', '[8364]', 1,		  		'Euro Member Countries'); 
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'FKP', '[163]', 1,	    			'Falkland Islands (Malvinas) Pound');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'FJD', '[36]', 1,		    		'Fiji Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'GHC', '[162]', 1,		    		'Ghana Cedis');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'GNF', '[70,71]', 1,			  	'Guinea Franc');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'GIP', '[163]', 1,			      'Gibraltar Pound');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'GTQ', '[81]', 1,			    	'Guatemala Quetzal');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'GGP', '[163]', 1,		    		'Guernsey Pound');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'GYD', '[36]', 1,			    	'Guyana Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'HNL', '[76]', 1,	    			'Honduras Lempira');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'HKD', '[36]', 1,			    	'Hong Kong Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'HUF', '[70,116]', 1,		  	'Hungary Forint');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'ISK', '[107,114]', 1,	  		'Iceland Krona');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'INR', '[8377]', 1,			  	'India Rupee'); 
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'IDR', '[82,112]', 1,		  	'Indonesia Rupiah');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'IRR', '[65020]', 1,			  	'Iran Rial');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'IMP', '[163]', 1,			    	'Isle of Man Pound');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'ILS', '[8362]', 1,		  		'Israel Shekel');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'JMD', '[74,36]', 1,			  	'Jamaica Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'JPY', '[165]', 1,			    	'Japan Yen');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'JEP', '[163]', 1,		    		'Jersey Pound');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'KES', NULL, 1,			      	'Kenya Shilling');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'KZT', '[1083,1074]', 1,			'Kazakhstan Tenge');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'KPW', '[8361]', 1,		  		'Korea (North) Won');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'KRW', '[8361]', 1,			  	'Korea (South) Won');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'KGS', '[1083,1074]', 1,			'Kyrgyzstan Som');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'LAK', '[8365]', 1,		  		'Laos Kip');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'LVL', '[76,115]', 1,		  	'Latvia Lat');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'LBP', '[163]', 1,			    	'Lebanon Pound');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'LRD', '[36]', 1,			  	  'Liberia Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'LTL', '[76,116]', 1,		  	'Lithuania Litas');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'MGA', NULL, 1,			    		'Ariary');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'MKD', '[1076,1077,1085]', 1,	'Macedonia Denar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'MYR', '[82,77]', 1,		  		'Malaysia Ringgit');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'MRO', NULL, 1,			    		'Mauritania Ouguiya');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'MUR', '[8360]', 1,	  			'Mauritius Rupee');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'MXN', '[36]', 1,	    			'Mexico Peso');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'MDL', NULL, 1,	  	        'Moldova Leu');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'MMK', '[75]', 1,	  			'Myanmar Kyat');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'MNT', '[8366]', 1,	  			'Mongolia Tughrik');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'MAD', NULL, 1,			    		'Morocco Dirham');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'MZN', '[77,84]', 1,	  			'Mozambique Metical');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'NAD', '[36]', 1,			    	'Namibia Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'NPR', '[8360]', 1,  				'Nepal Rupee');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'ANG', '[402]', 1,		    		'Netherlands Antilles Guilder');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'NZD', '[36]', 1,		    		'New Zealand Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'NIO', '[67,36]', 1,			  	'Nicaragua Cordoba');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'NGN', '[8358]', 1,		  		'Nigeria Naira');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'NOK', '[107,114]', 1,		  	'Norway Krone');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'OMR', '[65020]', 1,	  			'Oman Rial');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'PKR', '[8360]', 1,		  		'Pakistan Rupee');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'PAB', '[66,47,46]', 1,			'Panama Balboa');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'PYG', '[71,115]', 1,		  	'Paraguay Guarani');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'PEN', '[83,47]', 1,			    'Perú Sol');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'PHP', '[8369]', 1,			  	'Philippines Peso');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'PLN', '[122,322]', 1,	  		'Poland Zloty');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'QAR', '[65020]', 1,		  		'Qatar Riyal');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'RON', '[108,101,105]', 1,		'Romania New Leu');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'RUB', '[1088,1091,1073]', 1, 'Russia Ruble');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'SHP', '[163]', 1,		    		'Saint Helena Pound');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'SAR', '[65020]', 1,			  	'Saudi Arabia Riyal');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'RSD', '[1044,1080,1085,46]', 1,	'Serbia Dinar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'SCR', '[8360]', 1,		  		'Seychelles Rupee');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'SGD', '[36]', 1,		    		'Singapore Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'SBD', '[36]', 1,		    		'Solomon Islands Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'SOS', '[83]', 1,		    		'Somalia Shilling');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'ZAR', '[82]', 1,	  	  		'South Africa Rand');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'LKR', '[8360]', 1,		  		'Sri Lanka Rupee');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'SEK', '[107,114]', 1,		  	'Sweden Krona');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'CHF', '[67,72,70]', 1,			'Switzerland Franc');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'SRD', '[36]', 1,		    		'Suriname Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'SYP', '[163]', 1,			    	'Syria Pound');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'TWD', '[78,84,36]', 1,			'Taiwan New Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'THB', '[3647]', 1,			  	'Thailand Baht');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'TTD', '[84,84,36]', 1,			'Trinidad and Tobago Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'TND', NULL, 1,				    	'Tunisia Dinar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'TRY', '[8378]', 1,		  		'Turkey Lira');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'TVD', '[36]', 1,		    		'Tuvalu Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'UAH', '[8372]', 1,	  			'Ukraine Hryvna');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'AED', NULL, 1,			     		'United Arab Emirates Dirham');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'GBP', '[163]', 1,	  	  		'United Kingdom Pound');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'USD', '[36]', 1,		    		'United States Dollar');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'UYU', '[36,85]', 1,		  		'Uruguay Peso');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'UZS', '[1083,1074]', 1,			'Uzbekistan Som');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'VEF', '[66,115]', 1,		  	'Venezuela Bolivar Fuerte');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'VND', '[8363]', 1,		  		'Viet Nam Dong');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'XAF', NULL, 1,	    				'Communaute Financiere Africaine (BEAC) CFA Franc');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'XOF', NULL, 1,			    		'Communaute Financiere Africaine (BCEAO) Franc');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'XPF', '[70]', 1,            'Franc CFP');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'YER', '[65020]', 1,		  		'Yemen Rial');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'ZWD', '[90,36]', 1,			  	'Zimbabwe Dollar');

-- obsolete
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'ATS', NULL, 0,	    'Shiliing autrichiens');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'BEF', NULL, 0,  	  'Francs belges');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'DEM', NULL, 0,      'Deutsche Mark');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'ESP', NULL, 0,      'Pesete');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'FIM', NULL, 0,      'Mark finlandais'); 
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'FRF', NULL, 0,      'Francs francais');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'GRD', NULL, 0,      'Drachme (grece)');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'IEP', NULL, 0,      'Livres irlandaises');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'ITL', NULL, 0,      'Lires');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'LUF', NULL, 0,      'Francs luxembourgeois');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'NLG', NULL, 0,      'Florins');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'PTE', NULL, 0,      'Escudos');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'SKK', NULL, 0,      'Couronnes slovaques');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'SUR', NULL, 0,      'Rouble');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'XEU', NULL, 0,      'Ecus');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'TRL', '[84,76]', 0,	'Turkey Lira');


-- invalid (for compatibility)
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'ARP', NULL, 0,	'Pesos argentins');
INSERT INTO llx_c_currencies ( code_iso, unicode, active, label ) VALUES ( 'MXP', NULL, 0,	'Pesos Mexicans');
