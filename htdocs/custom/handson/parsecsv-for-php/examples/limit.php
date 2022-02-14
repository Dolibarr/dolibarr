<pre>
<?php


# include parseCSV class.
require __DIR__ . '/../vendor/autoload.php';

use ParseCsv\Csv;


# create new parseCSV object.
$csv = new Csv();


# if sorting is enabled, the whole CSV file
# will be processed and sorted and then rows
# are extracted based on offset and limit.
#
# if sorting is not enabled, then the least
# amount of rows to satisfy offset and limit
# settings will be processed. this is useful
# with large files when you only need the
# first 20 rows for example.
$csv->sort_by = 'title';


# offset from the beginning of the file,
# ignoring the first X number of rows.
$csv->offset = 2;

# limit the number of returned rows.
$csv->limit = 3;


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
