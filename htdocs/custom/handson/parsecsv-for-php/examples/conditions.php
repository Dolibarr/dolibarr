<pre>
<?php


# include parseCSV class.
require __DIR__ . '/../vendor/autoload.php';

use ParseCsv\Csv;


# create new parseCSV object.
$csv = new Csv();


# Example conditions:
// $csv->conditions = 'title contains paperback OR title contains hardcover';
$csv->conditions = 'author does not contain dan brown';
// $csv->conditions = 'rating < 4 OR author is John Twelve Hawks';
// $csv->conditions = 'rating > 4 AND author is Dan Brown';
// $csv->conditions = 'rating is greater than 4';


# Parse '_books.csv' using automatic delimiter detection.
$csv->auto('_books.csv');


# Output result.
// print_r($csv->data);


?>
</pre>
<style type="text/css" media="screen">
    table {
        background-color: #BBB;
    }

    th {
        background-color: #EEE;
    }

    td {
        background-color: #FFF;
    }
</style>
<table>
    <tr>
        <?php foreach ($csv->titles as $value): ?>
            <th><?php echo $value; ?></th>
        <?php endforeach; ?>
    </tr>
    <?php foreach ($csv->data as $key => $row): ?>
        <tr>
            <?php foreach ($row as $value): ?>
                <td><?php echo $value; ?></td>
            <?php endforeach; ?>
        </tr>
    <?php endforeach; ?>
</table>
