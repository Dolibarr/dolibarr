<?php
use Luracast\Restler\Restler;
use Luracast\Restler\Util;

$template_vars = $data;//get_defined_vars();

unset($template_vars['response']);
unset($template_vars['api']);
unset($template_vars['request']);
unset($template_vars['stages']);
$template_vars['request'] = $data['request'];
$template_vars['stages'] = $data['stages'];

$call_trace = '';

function exceptions()
{
    global $call_trace;
    $r = Util::$restler;
    $source = $r->_exceptions;
    if (count($source)) {
        $source = end($source);
        $traces = array();
        do {
            $traces += $source->getTrace();
        } while ($source = $source->getPrevious());
        $traces += debug_backtrace();
        $call_trace
            = parse_backtrace($traces, 0);
    } else {
        $call_trace
            = parse_backtrace(debug_backtrace());
    }

}
exceptions();

function parse_backtrace($raw, $skip = 1)
{
    $output = "";
    foreach ($raw as $entry) {
        if ($skip-- > 0) {
            continue;
        }
        //$output .= print_r($entry, true) . "\n";
        $output .= "\nFile: " . $entry['file'] . " (Line: " . $entry['line'] . ")\n";
        if (isset($entry['class']))
            $output .= $entry['class'] . "::";
        $output .= $entry['function']
            . "( " . json_encode($entry['args']) . " )\n";
    }
    return $output;
}


//print_r(get_defined_vars());
//print_r($response);
$icon;
if ($success && isset($api)) {
    $arguments = implode(', ', $api->parameters);
    $icon = "<icon class=\"success\"></icon>";
    $title = "{$api->className}::"
        . "{$api->methodName}({$arguments})";
} else {
    if (isset($response['error']['message'])) {
        $icon = '<icon class="denied"></icon>';
        $title = end(explode(':',$response['error']['message'],2));
    } else {
        $icon = '<icon class="warning"></icon>';
        $title = 'No Matching Resource';
    }
}
function render($data, $shadow=true)
{
    $r = '';
    if (empty($data))
        return $r;
    $r .= $shadow ? "<ul class=\"shadow\">\n": "<ul>\n";
    if (is_array($data)) {
        // field name
        foreach ($data as $key => $value) {
            $r .= '<li>';
            $r .= is_numeric($key)
                ? "<strong>[$key]</strong> "
                : "<strong>$key: </strong>";
            $r .= '<span>';
            if (is_array($value)) {
                // recursive
                $r .= render($value,false);
            } else {
                // value, with hyperlinked hyperlinks
                if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                }
                $value = htmlentities($value, ENT_COMPAT, 'UTF-8');
                if (strpos($value, 'http://') === 0) {
                    $r .= '<a href="' . $value . '">' . $value . '</a>';
                } else {
                    $r .= $value;
                }
            }
            $r .= "</span></li>\n";
        }
    } elseif (is_bool($data)) {
        $r .= '<li>' . ($data ? 'true' : 'false') . '</li>';
    } else {
        $r .= "<li><strong>$data</strong></li>";
    }
    $r .= "</ul>\n";
    return $r;
}
$reqHeadersArr = array();
$requestHeaders = $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI'] . ' ' . $_SERVER['SERVER_PROTOCOL'] . PHP_EOL;
foreach ($reqHeadersArr as $key => $value) {
    if ($key == 'Host')
        continue;
    $requestHeaders .= "$key: $value" . PHP_EOL;
}
// $requestHeaders = $this->encode(apache_request_headers(), FALSE,
// FALSE);
$responseHeaders = implode(PHP_EOL, headers_list()).PHP_EOL.'Status: HTTP/1.1 ';
$responseHeaders .= Util::$restler->responseCode.' '.\Luracast\Restler\RestException::$codes[Util::$restler->responseCode];

?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $title?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <style>
        <?php include __DIR__.'/debug.css'; ?>
    </style>
</head>
<body>
<div id="breadcrumbs-one">
    <?php
    if(Util::$restler->exception){
        $stages =  Util::$restler->exception->getStages();
        $curStage = Util::$restler->exception->getStage();
        foreach($stages['success'] as $stage){
            echo "<a href=\"#\">$stage</a>";
        }
        foreach($stages['failure'] as $stage){
            echo '<a href="#" class="failure">'
                . $stage
                . ($stage==$curStage ? ' <span class="state"/> ' : '')
                . '</a>';
        }
    } else {
        foreach(Util::$restler->_events as $stage){
            echo "<a href=\"#\">$stage</a>";
        }
    }
    ?>
</div>
<header>
    <h1><?php echo $title ?></h1>
</header>
<article>

    <h2>Response:<right><?php echo $icon;?></right></h2>
    <pre class="header"><?php echo $responseHeaders ?></pre>
    <?php echo render($response); ?>
    <h2>Additional Template Data:</h2>
    <?php echo render($template_vars); ?>
    <p>Restler v<?php echo Restler::VERSION?></p>
</article>
</body>
</html>