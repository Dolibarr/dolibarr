<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2018 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024      Alexandre Janniaux   <alexandre.janniaux@gmail.com>
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
 *		\file       htdocs/core/lib/emaillayout.lib.php
 *		\brief      File for getting email html models
 */

/**
 * Get empty html
 *
 * @param	string	$name	Name of template
 * @return 	string  $out  	Html content
 */
function getHtmlOfLayout($name)
{
	if ($name == 'basic') {
		$out = '
            <div >
            <div >
            <img alt="Gray rectangle" width="800px" height="100px" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAMgAAABkCAIAAABM5OhcAAABGklEQVR4nO3SwQ3AIBDAsNLJb3SWIEJC9gR5ZM3MB6f9twN4k7FIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIvEBtxYAkgpLmAeAAAAAElFTkSuQmCC" />
            </div>
            <h3>Lorem, ipsum dolor sit amet consectetur adipisicing elit.</h3>
            <h3>Lorem, ipsum dolor sit amet consectetur</h3>
            <!-- Les paragraphes de texte -->
            <div class="email-template-text">
            <p>Lorem, ipsum dolor sit amet consectetur adipisicing elit. Aperiam non blanditiis delectus accusamus reprehenderit voluptates repellat.<br> Excepturi illo delectus nisi labore, iste voluptatibus dignissimos explicabo, corrupti voluptatum et facere cum?</p>
            </div>
            <div class="email-template-text">
            <p>Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
            </div>
            <div class="email-template-text">
            <p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.</p>
            </div>

            <img alt="Gray rectangle" width="15%" height="50px" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAMgAAABkCAIAAABM5OhcAAABGklEQVR4nO3SwQ3AIBDAsNLJb3SWIEJC9gR5ZM3MB6f9twN4k7FIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIvEBtxYAkgpLmAeAAAAAElFTkSuQmCC" />

        </div>';
	} elseif ($name == 'news') {
		$out = '
        <h1 style="margin-left:120px;">Lorem, ipsum dolor sit amet consectetur adipisicing elit sit amet consectetur</h1>
        <h2 style="margin-left:120px;">Lorem, ipsum dolor sit amet consectetur adipisicing elitsit amet consectetur adipisicing </h2>

        <div style="display: flex; align-items: center; justify-content: start; width: 100%; max-width: 600px;margin-top:0">
            <div style="flex-grow: 1; margin-right: 10px; margin-left:120px;width:200px">
            <h2>Lorem ipsum dolor sit amet, consectetur Lorem ipsum dolor sit amet, consectetur</h2>

                Lorem ipsum dolor sit amet, consectetur
                adipiscing elit. Sed do eiusmod temxwshslkdsdsslpor incididunt ut labore et dolore magna <br>aliqua. Lorem, ipsum dolor sit amet consectetur adipisicing elit. Totam sit<br> autem nihil omnis! Odit ipsum repellat, voluptas accusantium dolores adipisci ut voluptates eius cumque dicta obcaecati
                <img alt="Gray rectangle" style="margin-top:10px"   width="15%" height="20px" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAMgAAABkCAIAAABM5OhcAAABGklEQVR4nO3SwQ3AIBDAsNLJb3SWIEJC9gR5ZM3MB6f9twN4k7FIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIvEBtxYAkgpLmAeAAAAAElFTkSuQmCC" />

            </div>
            <div style="flex-shrink: 0;">
                <!-- Ici, ajoutez votre image -->
                <img alt="Gray rectangle" style="" width="130px" height="130px" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAMgAAABkCAIAAABM5OhcAAABGklEQVR4nO3SwQ3AIBDAsNLJb3SWIEJC9gR5ZM3MB6f9twN4k7FIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIvEBtxYAkgpLmAeAAAAAElFTkSuQmCC" />
            </div>
        </div>

        <div style="display: flex; align-items: center; justify-content: start; width: 100%; max-width: 600px;margin-top:40px">
            <div style="flex-grow: 1; margin-right: 10px; margin-left:120px;width:200px">
            <h2>Lorem ipsum dolor sit amet, consectetur Lorem ipsum dolor sit amet, consectetur</h2>

                Lorem ipsum dolor sit amet, consectetur
                adipiscing elit. Sed do eiusmod temxwshslkdsdsslpor incididunt ut labore et dolore magna <br>aliqua. Lorem, ipsum dolor sit amet consectetur adipisicing elit. Totam sit<br> autem nihil omnis! Odit ipsum repellat, voluptas accusantium dolores adipisci ut voluptates eius cumque dicta obcaecati
                <img alt="Gray rectangle" style="margin-top:10px"   width="15%" height="20px" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAMgAAABkCAIAAABM5OhcAAABGklEQVR4nO3SwQ3AIBDAsNLJb3SWIEJC9gR5ZM3MB6f9twN4k7FIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIvEBtxYAkgpLmAeAAAAAElFTkSuQmCC" />

            </div>
            <div style="flex-shrink: 0;">
                <!-- Ici, ajoutez votre image -->
                <img alt="Gray rectangle" style="" width="130px" height="130px" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAMgAAABkCAIAAABM5OhcAAABGklEQVR4nO3SwQ3AIBDAsNLJb3SWIEJC9gR5ZM3MB6f9twN4k7FIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIvEBtxYAkgpLmAeAAAAAElFTkSuQmCC" />
            </div>
        </div>';
	} elseif ($name == 'commerce') {
		$out = '
		    <h1 style="margin-left:120px;">Lorem, ipsum dolor sit amet consectetur adipisicing elit sit amet consectetur</h1>
		    <h2 style="margin-left:120px;">Lorem, ipsum dolor sit amet consectetur adipisicing elitsit amet consectetur adipisicing </h2>

		    <div style="font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 16px; max-width: 600px; margin: 0 auto; box-sizing: border-box;">
		    <div style="display: flex;">
		        <div style="margin-bottom: 50px; flex: 1; padding-right: 8px;">
		            <div>
		                <img alt="Gray rectangle" style="margin-left:120px;margin-top:30px;" width="350px" height="100px" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAMgAAABkCAIAAABM5OhcAAABGklEQVR4nO3SwQ3AIBDAsNLJb3SWIEJC9gR5ZM3MB6f9twN4k7FIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIvEBtxYAkgpLmAeAAAAAElFTkSuQmCC" />
		            </div>
		            <div style="margin-left:120px;background-color: #e0e0e0; padding: 8px; margin-bottom: 8px; text-indent: 50px;">
		                Lorem ipsum dolor sit amet, consectetur adipiscing elit<br>Lorem ipsum dolor sit amet, consectetur adipiscing elit...
		            </div>
		        </div>

		            <br><br>
		        <div style="margin-bottom: 10px; flex: 1; padding-left: 8px;">
		            <div>
		                <img alt="Gray rectangle" style="margin-left:120px;margin-top:30px;" width="350px" height="100px" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAMgAAABkCAIAAABM5OhcAAABGklEQVR4nO3SwQ3AIBDAsNLJb3SWIEJC9gR5ZM3MB6f9twN4k7FIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIvEBtxYAkgpLmAeAAAAAElFTkSuQmCC" />
		            </div>
		                <div style="margin-left:120px;background-color: #e0e0e0; padding: 8px; margin-bottom: 8px; text-indent: 50px;">
		                Lorem ipsum dolor sit amet, consectetur adipiscing elit<br>Lorem ipsum dolor sit amet, consectetur adipiscing elit...
		            </div>
		        </div>
		    </div>
		    <div style="display: flex;">
		        <div style="margin-bottom: 50px; flex: 1; padding-right: 8px;">
		            <div>
		                <img alt="Gray rectangle" style="margin-left:120px;margin-top:30px;" width="350px" height="100px" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAMgAAABkCAIAAABM5OhcAAABGklEQVR4nO3SwQ3AIBDAsNLJb3SWIEJC9gR5ZM3MB6f9twN4k7FIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIvEBtxYAkgpLmAeAAAAAElFTkSuQmCC" />
		            </div>
		            <div style="margin-left:120px;background-color: #e0e0e0; padding: 8px; margin-bottom: 8px; text-indent: 50px;">
		                Lorem ipsum dolor sit amet, consectetur adipiscing elit<br>Lorem ipsum dolor sit amet, consectetur adipiscing elit...
		            </div>
		        </div>

		            <br><br>
		        <div style="margin-bottom: 10px; flex: 1; padding-left: 8px;">
		            <div>
		                <img alt="Gray rectangle" style="margin-left:120px;margin-top:30px;" width="350px" height="100px" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAMgAAABkCAIAAABM5OhcAAABGklEQVR4nO3SwQ3AIBDAsNLJb3SWIEJC9gR5ZM3MB6f9twN4k7FIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIuEsUgYi4SxSBiLhLFIGIvEBtxYAkgpLmAeAAAAAElFTkSuQmCC" />
		            </div>
		                <div style="margin-left:120px;background-color: #e0e0e0; padding: 8px; margin-bottom: 8px; text-indent: 50px;">
		                Lorem ipsum dolor sit amet, consectetur adipiscing elit<br>Lorem ipsum dolor sit amet, consectetur adipiscing elit...
		            </div>
		        </div>
		    </div>
		  </div>
		  ';
	} elseif ($name == 'text') {
		$out = '
        <h1>Lorem ipsum dolor sit amet consectetur adipisicing elit sit amet consectetur adipisicing elit.</h1>
        <h2>Lorem ipsum dolor sit amet consectetur adipisicing elit sit amet consectetur adipisicing elit.</h2>
        <p style="text-align: justify">
        Lorem ipsum dolor sit amet consectetur adipisicing elit. Commodi impedit molestias voluptatibus. Natus nulla sint totam illo? Hic name consequuntur id harum pariatur, quo illo quaerat minima tempore.
        Lorem ipsum dolor sit amet consectetur adipisicing elit. Commodi impedit molestias voluptatibus. Natus nulla sint totam illo? Hic name consequuntur id harum pariatur, quo illo quaerat minima tempore.
        Lorem ipsum dolor sit amet consectetur adipisicing elit. Commodi impedit molestias voluptatibus. Natus nulla sint totam illo? Hic name consequuntur id harum pariatur, quo illo quaerat minima tempore.
        Lorem ipsum dolor sit amet consectetur adipisicing elit. Commodi impedit molestias voluptatibus. Natus nulla sint totam illo? Hic name consequuntur id harum pariatur, quo illo quaerat minima tempore.
        Lorem ipsum dolor sit amet consectetur adipisicing elit. Commodi impedit molestias voluptatibus. Natus nulla sint totam illo? Hic name consequuntur id harum pariatur, quo illo quaerat minima tempore.
        Lorem ipsum dolor sit amet consectetur adipisicing elit. Commodi impedit molestias voluptatibus. Natus nulla sint totam illo? Hic name consequuntur id harum pariatur, quo illo quaerat minima tempore.
        Lorem ipsum dolor sit amet consectetur adipisicing elit. Commodi impedit molestias voluptatibus. Natus nulla sint totam illo? Hic name consequuntur id harum pariatur, quo illo quaerat minima tempore.
        </p>';
	} else {
		$out = '';
	}

	return $out;
}
