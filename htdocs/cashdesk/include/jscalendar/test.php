<?php

$lang = $_GET['lang'];
if (!$lang) {
    $lang = $_REQUEST['lang'];
}
if (!$lang) {
    $lang = 'en';
}
setcookie('lang', $lang);

?>
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML//EN">
<html>
<head>
<title>
Test for calendar.php
</title>

<?php

// put here the correct path to "calendar.php"; don't move the file
// "calendar.php" -- I think it's best if you leave it inside the
// "/jscalendar/" directory.  Just put here the correct path to it, such as
// "../jscalendar/calendar.php" or something.
require_once ('calendar.php');

// parameters to constructor:
//     1. the absolute URL path to the calendar files
//     2. the languate used for the calendar (see the lang/ dir)
//     3. the theme file used for the clanedar, without the ".css" extension
//     4. boolean that specifies if the "_stripped" files are to be loaded
//        The stripped files are smaller as they have no whitespace and comments
$calendar = new DHTML_Calendar('/jscalendar/', $lang, 'calendar-win2k-2', false);

// call this in the <head> section; it will "echo" code that loads the calendar
// scripts and theme file.
$calendar->load_files();

?>

</head>

<body>

<?php if ($_REQUEST['submitted']) { ?>

<h1>Form submitted</h1>

<?php foreach ($_REQUEST as $key => $val) {
    echo htmlspecialchars($key) . ' = ' . htmlspecialchars($val) . '<br />';
} ?>

<?php } else { ?>

<h1>Calendar.php test</h1>

     <form action="test.php" method="get">
     Select language: <select name="lang" onchange="this.form.submit()">
     <?php
$cwd = getcwd();
chdir('lang');
foreach (glob('*.js') as $filename) {
    $l = preg_replace('/(^calendar-|.js$)/', '', $filename);
    $selected = '';
    if ($l == $lang)
        $selected = 'selected="selected" ';
    $display = $l;
    if ($l == 'en')
        $display = 'EN';
    echo '<option ' . $selected . 'value="' . $l . '">' . $display . '</option>';
}
     ?>
     </select>
     <blockquote style="font-size: 90%">
       <b>NOTE</b>: as of this release, 0.9.6, only "EN" and "RO", which I
       maintain, function correctly.  Other language files do not work
       because they need to be updated.  If you update some language file,
       please consider sending it back to me so that I can include it in the
       calendar distribution.
     </blockquote>
     </form>

     <form action="test.php" method="get">
     <input type="hidden" name="submitted" value="1" />

     <table>
     <tr>
     <td>
       Date 1:
     </td>
     <td>
       <?php $calendar->make_input_field(
           // calendar options go here; see the documentation and/or calendar-setup.js
           array('firstDay'       => 1, // show Monday first
                 'showsTime'      => true,
                 'showOthers'     => true,
                 'ifFormat'       => '%Y-%m-%d %I:%M %P',
                 'timeFormat'     => '12'),
           // field attributes go here
           array('style'       => 'width: 15em; color: #840; background-color: #ff8; border: 1px solid #000; text-align: center',
                 'name'        => 'date1',
                 'value'       => strftime('%Y-%m-%d %I:%M %P', strtotime('now')))); ?>
     </td>
     </tr>
     </table>

     <hr />
     <button>Submit</button>

     </form>

<?php } ?>

</body>
</html>
