<?php
/* Copyright (C) 2010 Regis Houssin  <regis@dolibarr.fr>
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
 */

/**
 *   \file		htdocs/core/class/template.class.php
 *   \ingroup		core
 *   \brief			Fichier de la classe de gestion des templates
 *   \author		http://sourceforge.net/projects/litetemplate/
 *   \modifiedby	Regis Houssin
 *   \version		$Id$
 */

/**
 *    \class      Canvas
 *    \brief      Classe de la gestion des canvas
 */

class Template
{
	//Public
	
	// nom de la page tpl
	var $tplName;
	
	// code template
	var $tpl;
	
	// temps d'execution du code
	var $time;
	
	// repertoire cache par defaut
	var $cache_folder;
	
	// duree de vie d'un fichier cache
	var $cache_life;
	
	// active la gestion de cache
	var $cache_activate;
	
	// active la compression des fichiers de cache
	var $cache_compression;
	
	//Private
	
	var $cache_isExpired;
	
	// active/desactive le debug
	var $debug;
	
	// tableau contenant les informations de debugage
	var $error;
	
	
   /**
	*   \brief      Constructor.
	*   \param      DB      Database handler
	*/
	function Template()
	{
		$this->tpl = '';
		$this->tplName = '';
		$this->time = microtime();
		// cache
		$this->cache_folder = '_cache/';
		$this->cache_life = '60'; // 86400 == 1 day ; 604800 == 1 week ; 1814400 == 1 month
		$this->cache_activate = false;
		$this->cache_compression = false;
		//debug
		$this->debug = false;
		//private
		$this->cache_isExpired = true;
		$this->error = array();
	}
	
	/**
	 *  \brief 		stipule le fichier template a utiliser
	 *  \param 		$file
	 *  \return 	void
	 */
	function file($file)
	{
		global $langs;
		
		$this->tplName = $file;
		
		if($this->isExpiredCache())
		{
			$this->cache_isExpired = true;
			
			if (!$this->tpl = file_get_contents($file))
			{
				$this->error[] = 'Warning! probleme lors de la recuperation du fichier '.$file;
			}
		}
		else
		{
			$this->cache_isExpired = false;
		}
	}
	
	/**
	 *  \brief		Permet de passer un tableau array( key=>valeur,...)
	 *  			ou les clefs correspondent aux balises dans le fichier
	 *  			template qui seront remplacées par les valeurs.
	 *  \param 		$tag_array
	 *  \return 	void
	 */
	function assign($tag_array)
	{
		if(!$this->cache_activate or $this->cache_isExpired)
		{
			foreach( $tag_array as $key => $value)
			{
				$this->tpl = str_replace('{$'.$key.'}',str_replace('$','&#36;',$value),$this->tpl);
			}
		}
	
	}
	
	/**
	 *  \brief		Permet de realiser des boucles les valeur (tableau) entre les balises.
	 *  			exemple {LOOP id=1}{$NOM}:{$PRENOM}{/LOOP}.
	 *  \param 		$tag
	 *  \param 		$id
	 * 	\param		$tag_array
	 *	\return 	void
	 */
	function assignTag($tag,$id,$tag_array)
	{
		if(!$this->cache_activate || $this->cache_isExpired)
		{
			if( $this->checkArray($tag_array) )
			{
				reset($tag_array);
				$num_key = count($tag_array); //nbre de key
				$num_value = count($tag_array[key($tag_array)]); //nbre de value
				$tmp = $this->findTag($tag,$id); //la chaine entre les balises
				
				for($i=0;$i<$num_value;$i++)
				{
					$array[$i] = $tmp;
					reset($tag_array);
					
					for( $j=0;$j<$num_key;$j++)
					{
						$array[$i] = str_replace('{$'.key($tag_array).'}',str_replace('$','&#36;',$tag_array[key($tag_array)][$i]),$array[$i]);
						next($tag_array);
					}
				}
				
				//on concatene les arrays
				for ($i=1;$i<count($array);$i++)
				{
					$array[0].= $array[$i];
				}
				
				$replace = '{'.$tag.' id='.$id.'}'.$tmp.'{/'.$tag.'}';
				$this->tpl = str_replace($replace,$array[0],$this->tpl);
			}
			else
			{
				$this->error[]="Warning! erreur dans la taille des tableaux de la balise $tag id=$id";
			}
		}
	}
	
	/**
	 *  \brief 		Permet d'inclure un fichier html,php.
	 *  \param 		$id
	 *  \param 		$file
	 * 	\return 	void
	 */
	function assignInclude($id,$file="")
	{
		if(!$this->cache_activate || $this->cache_isExpired)
		{
			if( empty($file) )
			{
				$filename = $this->findTag("INCLUDE",$id,"FILE") or exit("erreur sur la balise $id");
				
				$tmp = $this->getIncludeContents($filename);
				$this->tpl = str_replace("{INCLUDE id=$id file=$filename}",$tmp,$this->tpl);
			}
			else
			{
				$tmp = $this->getIncludeContents($file);
				$this->tpl = str_replace("{INCLUDE id=$id}",$tmp,$this->tpl);
			}
		}
	}
	
	/**
	 * 	\brief		Permet d'inclure une liste deroulante.
	 * 	\param		$name
	 * 	\param		$array
	 * 	\param		$selected
	 * 	\param 		$htmlAttribut
	 */
	function htmlSelect($name,$array,$selected="",$htmlAttribut="")
	{
		if(!$this->cache_activate || $this->cache_isExpired)
		{
			//on test la balise avec l'option selected
			$select = $this->findTag("HTMLSELECT",$name,"SELECTED");
			
			if(!$select)
			{
				//si pas trouve on cherche sans l'option selected
				if($this->findTag("HTMLSELECT",$name))
				{
					$tmp = $this->creatHtmlSelect($name,$array,$selected,$htmlAttribut);
					$this->tpl = str_replace("{HTMLSELECT id=$name}",$tmp,$this->tpl);
				}
				else
				{
					$this->error[] = "Warning : Impossible de trouver HTMLSELEC $name";
				}
			}
			else
			{
				$tmp = $this->creatHtmlSelect($name,$array,$select,$htmlAttribut);
				$this->tpl = str_replace("{HTMLSELECT id=$name selected=$select}",$tmp,$this->tpl);
			}
		}
	}
	
	/**
	 * 	\brief		Permet d'afficher le fichier apres les modifications effectuer.
	 * 	\return 	le telmplate avec les modifications
	 */
	function view()
	{
		if(!$this->cache_activate || $this->cache_isExpired)
		{
			$this->assignAutoInclude();
			if(!$this->debug)
			{
				$this->clearTag();
			}
		}
		if(!$this->cache_activate)
		{
			echo $this->tpl;
		}
		elseif($this->cache_isExpired)
		{
			$this->putCache($this->returnTpl());
			echo $this->tpl;
		}
		else
		{
			echo $this->getCache();
		}
		$this->time = $this->microTimeDiff($this->time,microtime());
	}
	
	/**
	 * 	\brief		permet de recuperer le contenus de la variable tpl.
	 * 	\return 	tpl variable
	 */
	function returnTpl()
	{
		return $this->tpl;
	}
	
	/**
	 * 	\brief		Permet d'inclure des fonctions contenues dans le dossier addon, par defaut.
	 * 	\param 		$dir
	 * 	TODO experimental
	 */
	function addOn($dir = 'addon/')
	{
		if (is_dir($dir))
		{
			if ($dh = opendir($dir))
			{
				while (($file = readdir($dh)) !== false)
				{
					if($file != "." && $file != "..")
					{
						include_once $dir.$file;
					}
				}
				closedir($dh);
			}
			else
			{
				$this->error[] = 'Warning! le repertoire "'.$dir.'" ne peut pas etre ouvert';
			}
		}
		else
		{
			$this->error[] = 'Warning! le repertoire "'.$dir.'" est introuvable';
		}
	}
	
	/**
	 * 	\brief		permet de recuperer un fichier en cache. Retourne false en cas de timelife depasse ou si le fichier cache n existe pas
	 * 	\param 		$filename
	 * 	\return 	le contenus du template ou false en cas d'echec
	 */
	function getCache($filename='')
	{
		$filename = (empty($filename))?$this->tplName:$filename;
		
		$filename_md5 = md5($filename);
		$path_file = $this->cache_folder.$filename_md5;
		
		//on test si le fichier existe dans le cache
		if(file_exists($path_file))
		{
			if(!$this->cache_compression)
			{
				$handle = fopen($path_file, "rb");
				$contents = fread($handle, filesize($path_file)+1);
				fclose ($handle);
			}
			else
			{
				$contents =  $this->getGzFile($path_file);
			}
			
			return $contents;
		}
		else
		{
			//pas de fichier en cache
			return false;
		}
	}
	
	/**
	 * 	\brief		permet de mettre un fichier en cache.
	 * 	\param		$filename
	 * 	\param 		$contents
	 */
	function putCache($contents,$filename='')
	{
		$filename = (empty($filename))?$this->tplName:$filename;
		
		if(!is_dir($this->cache_folder))
		{
			if(!mkdir($this->cache_folder, 0755))
			{
				$this->error[] = 'Warning! le repertoire de cache"'.$this->cache_folder.'" n\'a pu etre cree';
			}
		}
		
		$filename_md5 = md5($filename);
		$path_file = $this->cache_folder.$filename_md5;
		
		if(!$this->cache_compression)
		{
			$handle = fopen($path_file, "w");
			
			if (fwrite($handle, $contents) === FALSE)
			{
				$this->error[] = "Warning :fwrite Impossible d'ecrire dans le fichier $path_file";
			}
			
			fclose($handle);
		}
		else
		{
			$handle = gzopen($path_file, "w");
			
			if (gzputs($handle, $contents) === FALSE)
			{
				$this->error[] = "Warning :gzputs Impossible d\'ecrire dans le fichier ".$path;
			}
			
			gzclose($handle);
		}
	}
	
	/**
	 * 	\brief		Retourne les messages d'erreur.
	 * 	\return 	array
	 */
	function getError()
	{
		return $this->error;
	}


	/*************************/
	/*    Private function   */
	/*************************/

	/**
	 * 	\brief		Permet de recuperer la sortie d'un fichier.
	 * 	\return 	contents
	 */
	function getIncludeContents($filename)
	{
		if (is_file($filename))
		{
			ob_start();
			include $filename;
			$contents = ob_get_contents();
			ob_end_clean();
			return $contents;
		}
		else
		{
			$this->error[]="Warning! pas de fichier selectionne : $filename";
		}
	}
	
	/**
	 * 	\brief		Permet de recuperer la sortie d'un fichier compresse.
	 * 	\return 	contents
	 */
	function getGzFile($filename)
	{
		if (is_file($filename))
		{
			ob_start();
			readgzfile($filename);
			$contents = ob_get_contents();
			ob_end_clean();
			return $contents;
		}
		else
		{
			$this->error[]="Warning! pas de fichier selectionne : $filename";
		}
	}
	
	/**
	 * 	\brief		permet de recuperer une zone de txt entre 2 balises { BALISE id=1 } chaine de caractere { /BALISE }
	 * 	\return 	array
	 */
	function findTag($tag,$id,$option="")
	{
		//option peut etre egale a FILE
		if( empty($option) )
		{
			//retourne la chaine si il la trouve et rien sinon
			@preg_match("/(\{".$tag." id=)(".$id.")(})(.*?)(\{\/".$tag."})/ism",$this->tpl,$result);

	        if(empty($result[4]))
	        {
	        	preg_match("/\{".$tag." id=(".$id.")}/ism",$this->tpl,$result);
	        	return $result[1];
	        }
	        
	        return $result[4];
	    }
	    elseif($option == "FILE")
	    {
	    	//retourne le nom du fichier si il la trouve et rien sinon
	    	@preg_match("/\{".$tag." id=".$id." file=(.*?)}/ism",$this->tpl,$result);
	    	return $result[1];
	    }
	    elseif($option == "SELECTED")
	    {
	    	//retourne le nom selected si il la trouve et rien sinon
	    	@preg_match("/\{".$tag." id=".$id." selected=(.*?)}/ism",$this->tpl,$result);
	    	return $result[1];
	    }
	    else
	    {
	    	return 0;
	    }
	}
	
	/**
	 * 	\brief		Test si il y a le meme nombre de d'element dans les sous tableau array(key=>array(),key=>array...)
	 * 	\return 	array
	 */
	function checkArray($array)
	{
		reset($array);
		$return = true;
		$num = count($array[key($array)]);
		
		for ($i = 0; $i < count($array); $i++)
		{
			if($num != count($array[key($array)]))
			{
				$return=false;
			}
			next($array);
		}
		
		return $return;
	}
	
	/**
	 * 	\brief		Supprime les balises templates non utiliser dans le template
	 */
	function clearTag()
	{
		$tag = '[a-zA-Z0-9_]{1,}';
		$id = '[a-zA-Z0-9_]{1,}';
		
		//supprime les balises simples
		$this->tpl = preg_replace('/\{\$'.$tag.'\}/i','',$this->tpl);
		//supprime les balises dynamiques
		$this->tpl = preg_replace('/(\{'.$tag.' id=)('.$id.')(})(.*?)(\{\/'.$tag.'})/ism','',$this->tpl);
		$this->tpl = preg_replace('/(\{'.$tag.' id=)('.$id.')(})/ism','',$this->tpl);
		$this->tpl = preg_replace('/(\{'.$tag.' id=)('.$id.') (file=(.*?)})/ism','',$this->tpl);
	}
	
	/**
	 * 	\brief		Fait la difference entre deux temps de type microtime
	 */
	function microTimeDiff($time_begin,$time_end)
	{
		$a=explode(' ',$time_begin);
		$b=explode(' ',$time_end);
		
		return $b[0]-$a[0]+$b[1]-$a[1];
	}
	
	/**
	 * 	\brief		Construit un html select en fonction du selected
	 * 	TODO remplacer par select_array() ?
	 */
	function creatHtmlSelect($name,$array,$selected,$attribut)
	{
		$tmp = '<Select name="'.$name.'" '.$attribut.' >'."\n";
		        
        foreach($array as $key=>$value)
        {
        	if( $key == $selected)
        	{
        		$tmp.= '<option value="'.$key.'" SELECTED >'.$value.'</option>'."\n";
        	}
        	else
        	{
        		$tmp.= '<option value="'.$key.'">'.$value.'</option>'."\n";
        	}
        }
        $tmp.= '</select>';
        return $tmp;
	}
	
	/**
	 * 	\brief		Parse automatique des {INCLUDE file=filename.php}
	 */
	function assignAutoInclude()
	{
		$patern = "@\{INCLUDE file=(.*)\}@";
		preg_match_all($patern,$this->tpl,$regs);
		
		foreach($regs[1] as $file)
		{
			$tmp = $this->getIncludeContents($file);
			$this->tpl = str_replace("{INCLUDE file=$file}",$tmp,$this->tpl);
		}
	}
	
	/**
	 * 	\brief		Delai expiration du cache
	 */
	function isExpiredCache($filename='')
	{
		$filename = (empty($filename))?$this->tplName:$filename;
		$filename_md5 = md5($filename);
		$path_file = $this->cache_folder.$filename_md5;
		
		//on test si le fichier existe dans le cache
		if(file_exists($path_file))
		{
			clearstatcache();
			$diff = time() - filemtime($path_file);
			
			// si la duree de vie du fichier cache est bonne (il n'est donc pas expire) on renvoi false, le cache n'est pas expire
			if( $diff < $this->cache_life)
			{
				return false;
			}
			else
			{
				//le temps de vie du fichier cache est depasse
				return true;
			}
		}
		else
		{
			//pas de fichier en cache = delai depasse
			return true;
		}
	}
	
}

?>