#!/usr/bin/env php
<?php
/*
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file 	dev/tools/spider.php
 * \brief 	Script to spider Dolibarr app.
 *
 * To use it:
 * - Disable module "bookmark"
 * - Exclude param  optioncss, token, sortfield, sortorder
 */

$crawledLinks=array();
const MAX_DEPTH=2;


/**
 * @param string $url	URL
 * @param string $depth	Depth
 * @return string		String
 */
function followLink($url, $depth = 0)
{
	global $crawledLinks;
	$crawling=array();
	if ($depth>MAX_DEPTH) {
		echo "<div style='color:red;'>The Crawler is giving up!</div>";
		return;
	}
	$options=array(
		'http'=>array(
			'method'=>"GET",
			'user-agent'=>"gfgBot/0.1\n"
		)
	);
	$context=stream_context_create($options);
	$doc=new DomDocument();
	@$doc->loadHTML(file_get_contents($url, false, $context));
	$links=$doc->getElementsByTagName('a');
	$pageTitle=getDocTitle($doc, $url);
	$metaData=getDocMetaData($doc);
	foreach ($links as $i) {
		$link=$i->getAttribute('href');
		if (ignoreLink($link)) continue;
		$link=convertLink($url, $link);
		if (!in_array($link, $crawledLinks)) {
			$crawledLinks[]=$link;
			$crawling[]=$link;
			insertIntoDatabase($link, $pageTitle, $metaData, $depth);
		}
	}
	foreach ($crawling as $crawlURL)
		followLink($crawlURL, $depth+1);
}

/**
 * @param string $site	Site
 * @param string $path	Path
 * @return string		String
 */
function convertLink($site, $path)
{
	if (substr_compare($path, "//", 0, 2)==0)
		return parse_url($site)['scheme'].$path;
	elseif (substr_compare($path, "http://", 0, 7)==0 or
		substr_compare($path, "https://", 0, 8)==0 or
		substr_compare($path, "www.", 0, 4)==0)
		return $path;
	else return $site.'/'.$path;
}

/**
 * @param string $url	URL
 * @return boolean
 */
function ignoreLink($url)
{
	return $url[0]=="#" or substr($url, 0, 11) == "javascript:";
}

/**
 * @param string 	$link		URL
 * @param string	$title		Title
 * @param string 	$metaData	Array
 * @param int 		$depth		Depth
 * @return void
 */
function insertIntoDatabase($link, $title, &$metaData, $depth)
{
	//global $crawledLinks;

	echo "Inserting new record {URL= ".$link.", Title = '$title', Description = '".$metaData['description']."', Keywords = ' ".$metaData['keywords']."'}<br/><br/><br/>";

	//²$crawledLinks[]=$link;
}

/**
 * @param string 	$doc		Doc
 * @param string	$url		URL
 * @return string				URL/Title
 */
function getDocTitle(&$doc, $url)
{
	$titleNodes=$doc->getElementsByTagName('title');
	if (count($titleNodes)==0 or !isset($titleNodes[0]->nodeValue))
		return $url;
	$title=str_replace('', '\n', $titleNodes[0]->nodeValue);
	return (strlen($title)<1)?$url:$title;
}

/**
 * @param string 	$doc		Doc
 * @return array				Array
 */
function getDocMetaData(&$doc)
{
	$metaData=array();
	$metaNodes=$doc->getElementsByTagName('meta');
	foreach ($metaNodes as $node)
		$metaData[$node->getAttribute("name")] = $node->getAttribute("content");
	if (!isset($metaData['description']))
		$metaData['description']='No Description Available';
	if (!isset($metaData['keywords'])) $metaData['keywords']='';
	return array(
		'keywords'=>str_replace('', '\n', $metaData['keywords']),
		'description'=>str_replace('', '\n', $metaData['description'])
	);
}


followLink("http://localhost/dolibarr_dev/htdocs");
