<?php
require_once "search_config.php";

function end_form($value)
{
  global $config;
  global $translator;
  if ($config['DISABLE_INDEX'] == false)
  {
    echo "</div>\n";
  }
  if ($config['GENERATE_TREEVIEW'])
  {
    echo $translator['split_bar'];
  }
}

function search_results()
{
  global $translator;
  return $translator['search_results_title'];
}

function matches_text($num)
{
  global $translator;
  $string = $translator['search_results'][($num>2)?2:$num];
  // The eval is used so that translator strings can contain $num.
  eval("\$result = \"$string\";");
  return $result;
}

function report_matches()
{
  global $translator;
  return $translator['search_matches'];
}

function readInt($file)
{
  $b1 = ord(fgetc($file)); $b2 = ord(fgetc($file));
  $b3 = ord(fgetc($file)); $b4 = ord(fgetc($file));
  return ($b1<<24)|($b2<<16)|($b3<<8)|$b4;
}

function readString($file)
{
  $result="";
  while (ord($c=fgetc($file))) $result.=$c;
  return $result;
}

function readHeader($file)
{
  $header =fgetc($file); $header.=fgetc($file);
  $header.=fgetc($file); $header.=fgetc($file);
  return $header;
}

function computeIndex($word)
{
  // Simple hashing that allows for substring search
  if (strlen($word)<2) return -1;
  // high char of the index
  $hi = ord($word{0});
  if ($hi==0) return -1;
  // low char of the index
  $lo = ord($word{1});
  if ($lo==0) return -1;
  // return index
  return $hi*256+$lo;
}

function search($file,$word,&$statsList)
{
  $index = computeIndex($word);
  if ($index!=-1) // found a valid index
  {
    fseek($file,$index*4+4); // 4 bytes per entry, skip header
    $index = readInt($file);
    if ($index) // found words matching the hash key
    {
      $start=sizeof($statsList);
      $count=$start;
      fseek($file,$index);
      $w = readString($file);
      while ($w)
      {
        $statIdx = readInt($file);
        if ($word==substr($w,0,strlen($word)))
        { // found word that matches (as substring)
          $statsList[$count++]=array(
              "word"=>$word,
              "match"=>$w,
              "index"=>$statIdx,
              "full"=>strlen($w)==strlen($word),
              "docs"=>array()
              );
        }
        $w = readString($file);
      }
      $totalHi=0;
      $totalFreqHi=0;
      $totalFreqLo=0;
      for ($count=$start;$count<sizeof($statsList);$count++)
      {
        $statInfo = &$statsList[$count];
        $multiplier = 1;
        // whole word matches have a double weight
        if ($statInfo["full"]) $multiplier=2;
        fseek($file,$statInfo["index"]); 
        $numDocs = readInt($file);
        $docInfo = array();
        // read docs info + occurrence frequency of the word
        for ($i=0;$i<$numDocs;$i++)
        {
          $idx=readInt($file); 
          $freq=readInt($file); 
          $docInfo[$i]=array("idx"  => $idx,
                             "freq" => $freq>>1,
                             "rank" => 0.0,
                             "hi"   => $freq&1
                            );
          if ($freq&1) // word occurs in high priority doc
          {
            $totalHi++;
            $totalFreqHi+=$freq*$multiplier;
          }
          else // word occurs in low priority doc
          {
            $totalFreqLo+=$freq*$multiplier;
          }
        }
        // read name and url info for the doc
        for ($i=0;$i<$numDocs;$i++)
        {
          fseek($file,$docInfo[$i]["idx"]);
          $docInfo[$i]["name"]=readString($file);
          $docInfo[$i]["url"]=readString($file);
        }
        $statInfo["docs"]=$docInfo;
      }
      $totalFreq=($totalHi+1)*$totalFreqLo + $totalFreqHi;
      for ($count=$start;$count<sizeof($statsList);$count++)
      {
        $statInfo = &$statsList[$count];
        $multiplier = 1;
        // whole word matches have a double weight
        if ($statInfo["full"]) $multiplier=2;
        for ($i=0;$i<sizeof($statInfo["docs"]);$i++)
        {
          $docInfo = &$statInfo["docs"];
          // compute frequency rank of the word in each doc
          $freq=$docInfo[$i]["freq"];
          if ($docInfo[$i]["hi"])
          {
            $statInfo["docs"][$i]["rank"]=
              (float)($freq*$multiplier+$totalFreqLo)/$totalFreq;
          }
          else
          {
            $statInfo["docs"][$i]["rank"]=
              (float)($freq*$multiplier)/$totalFreq;
          }
        }
      }
    }
  }
  return $statsList;
}

function combine_results($results,&$docs)
{
  foreach ($results as $wordInfo)
  {
    $docsList = &$wordInfo["docs"];
    foreach ($docsList as $di)
    {
      $key=$di["url"];
      $rank=$di["rank"];
      if (isset($docs[$key]))
      {
        $docs[$key]["rank"]+=$rank;
      }
      else
      {
        $docs[$key] = array("url"=>$key,
            "name"=>$di["name"],
            "rank"=>$rank
            );
      }
      $docs[$key]["words"][] = array(
               "word"=>$wordInfo["word"],
               "match"=>$wordInfo["match"],
               "freq"=>$di["freq"]
               );
    }
  }
  return $docs;
}

function filter_results($docs,&$requiredWords,&$forbiddenWords)
{
  $filteredDocs=array();
  while (list ($key, $val) = each ($docs)) 
  {
    $words = &$docs[$key]["words"];
    $copy=1; // copy entry by default
    if (sizeof($requiredWords)>0)
    {
      foreach ($requiredWords as $reqWord)
      {
        $found=0;
        foreach ($words as $wordInfo)
        { 
          $found = $wordInfo["word"]==$reqWord;
          if ($found) break;
        }
        if (!$found) 
        {
          $copy=0; // document contains none of the required words
          break;
        }
      }
    }
    if (sizeof($forbiddenWords)>0)
    {
      foreach ($words as $wordInfo)
      {
        if (in_array($wordInfo["word"],$forbiddenWords))
        {
          $copy=0; // document contains a forbidden word
          break;
        }
      }
    }
    if ($copy) $filteredDocs[$key]=$docs[$key];
  }
  return $filteredDocs;
}

function compare_rank($a,$b)
{
  if ($a["rank"] == $b["rank"]) 
  {
    return 0;
  }
  return ($a["rank"]>$b["rank"]) ? -1 : 1; 
}

function sort_results($docs,&$sorted)
{
  $sorted = $docs;
  usort($sorted,"compare_rank");
  return $sorted;
}

function report_results(&$docs)
{
  echo "<div class=\"header\">";
  echo "  <div class=\"headertitle\">\n";
  echo "    <h1>".search_results()."</h1>\n";
  echo "  </div>\n";
  echo "</div>\n";
  echo "<div class=\"searchresults\">\n";
  echo "<table cellspacing=\"2\">\n";
  $numDocs = sizeof($docs);
  if ($numDocs==0)
  {
    echo "  <tr>\n";
    echo "    <td colspan=\"2\">".matches_text(0)."</td>\n";
    echo "  </tr>\n";
  }
  else
  {
    echo "  <tr>\n";
    echo "    <td colspan=\"2\">".matches_text($numDocs);
    echo "\n";
    echo "    </td>\n";
    echo "  </tr>\n";
    $num=1;
    foreach ($docs as $doc)
    {
      echo "  <tr>\n";
      echo "    <td align=\"right\">$num.</td>";
      echo     "<td><a class=\"el\" href=\"".$doc["url"]."\">".$doc["name"]."</a></td>\n";
      echo "  <tr>\n";
      echo "    <td></td><td class=\"tiny\">".report_matches()." ";
      foreach ($doc["words"] as $wordInfo)
      {
        $word = $wordInfo["word"];
        $matchRight = substr($wordInfo["match"],strlen($word));
        echo "<b>$word</b>$matchRight(".$wordInfo["freq"].") ";
      }
      echo "    </td>\n";
      echo "  </tr>\n";
      $num++;
    }
  }
  echo "</table>\n";
  echo "</div>\n";
}

function run_query($query)
{
  if(strcmp('4.1.0', phpversion()) > 0) 
  {
    die("Error: PHP version 4.1.0 or above required!");
  }
  if (!($file=fopen("search/search.idx","rb"))) 
  {
    die("Error: Search index file could NOT be opened!");
  }
  if (readHeader($file)!="DOXS")
  {
    die("Error: Header of index file is invalid!");
  }
  $results = array();
  $requiredWords = array();
  $forbiddenWords = array();
  $foundWords = array();
  $word=strtok($query," ");
  while ($word) // for each word in the search query
  {
    if (($word{0}=='+')) { $word=substr($word,1); $requiredWords[]=$word; }
    if (($word{0}=='-')) { $word=substr($word,1); $forbiddenWords[]=$word; }
    if (!in_array($word,$foundWords))
    {
      $foundWords[]=$word;
      search($file,strtolower($word),$results);
    }
    $word=strtok(" ");
  }
  fclose($file);
  $docs = array();
  combine_results($results,$docs);
  // filter out documents with forbidden word or that do not contain
  // required words
  $filteredDocs = filter_results($docs,$requiredWords,$forbiddenWords);
  // sort the results based on rank
  $sorted = array();
  sort_results($filteredDocs,$sorted);
  return $sorted;
}

function main()
{
  $query = "";
  if (array_key_exists("query", $_GET))
  {
    $query=$_GET["query"];
  }
  $sorted = run_query($query);
  // Now output the HTML stuff...
  // End the HTML form
  end_form(preg_replace("/[^a-zA-Z0-9\-\_\.\x80-\xFF]/i", " ", $query ));
  // report results to the user
  report_results($sorted);
}
?>
