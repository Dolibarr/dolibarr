<?php
require "search_functions.php";

$mode = array_key_exists('v', $_GET)?$_GET['v']:"";
$query = array_key_exists('query', $_GET)?$_GET['query']:"";
$query = preg_replace("/[^a-zA-Z0-9\-\_\.\x80-\xFF]/i", " ", $query );

$query_results = run_query($query);

switch ($mode)
{
  case "opensearch.xml":
    opensearch_description();
    break;
  case "json":
    opensearch_json_results($query, $query_results);
    break;
  case "xml":
    opensearch_xml_results($query, $query_results);
    break;
  default:
    invalid_format($query, $query_results);
    break;
}

function opensearch_description()
{
  global $config;
  global $translator;

  $shortname = $translator['search']." ".$config['PROJECT_NAME'];
  $link = "http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']);
  header("Content-Type: application/xml");
  echo <<<END_OPENSEARCH
<?xml version="1.0" encoding="UTF-8"?>
<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/">
<ShortName>$shortname</ShortName>
<Description>Doxygen Search</Description>
<InputEncoding>UTF-8</InputEncoding>
<!--
<Image height="16" width="16" type="image/x-icon">
http://dev.squello.com/doc/html/favicon.ico</Image>
-->
<Url type="text/html" method="GET"
template="$link/search.php?query={searchTerms}" />
<Url type="application/x-suggestions+json" method="GET"
template="$link/search_opensearch.php?v=json&amp;query={searchTerms}" />
<Url type="application/x-suggestions+xml" method="GET"
template="$link/search_opensearch.php?v=xml&amp;query={searchTerms}" />
</OpenSearchDescription>
END_OPENSEARCH;
}

function opensearch_xml_results($query, array $results)
{
  // Much as I hate copy'n'paste code re-use, this is for testing;
  // I expect a richer version to come soon.
  // Although I hate that IE does this richer than FF more...
  $qs_results = array();
  foreach ($results as $i => $val)
  {
    foreach ($val['words'] as $j => $word)
    {
      if (array_key_exists($word, $qs_results))
        $qs_results[$word['match']]++;
      else
        $qs_results[$word['match']] = 1;
    }
  }
  $result = <<<END_FRAG
<?xml version="1.0"?>
<SearchSuggestion xmlns="http://schemas.microsoft.com/Search/2008/suggestions">
<Query>$query</Query>
<Section>
END_FRAG;
  foreach ($qs_results as $word => $count)
  {
    $result .= <<<END_FRAG
<Item>
<Text>$word</Text>
<Description>$count results</Description>
</Item>
END_FRAG;
  }
  $result .= <<<END_FRAG
</Section>
</SearchSuggestion>
END_FRAG;
  echo $result;
}

function opensearch_json_results($query, array $results)
{
  $qs_results = array();
  foreach ($results as $i => $val)
  {
    foreach ($val['words'] as $j => $word)
    {
      if (array_key_exists($word, $qs_results))
        $qs_results[$word['match']]++;
      else
        $qs_results[$word['match']] = 1;
    }
  }
  $result = '["'.$query.'", [';
  $json_words = "";
  $json_descriptions = "";
  $i = 0;
  foreach ($qs_results as $word => $count)
  {
    if ($i != 0)
    {
      $json_words .= ", ";
      $json_descriptions .= ", ";
    }
    $json_words .= '"'.$word.'"';
    $json_descriptions .= '"'.$count.' result'.($count==1?'':'s').'"';
    $i++;
  }
  print "[\"$query\", [$json_words],[$json_descriptions]]";
}

function invalid_format($query, array $results)
{
  print "Search results for '$query':\n\n";
  print_r($results);
}
?>
