<?php


# include parseCSV class.
require __DIR__ . '/../vendor/autoload.php';

use ParseCsv\Csv;


# Create new parseCSV object.
$csv = new Csv();

# When saving, don't write the header row:
$csv->heading = false;

# Specify which columns to write, and in which order.
# We won't output the 'Awesome' column this time.
$csv->titles = ['Age', 'Name'];

# Data to write:
$csv->data = [
    0 => ['Name' => 'Anne', 'Age' => 45, 'Awesome' => true],
    1 => ['Name' => 'John', 'Age' => 44, 'Awesome' => false],
];

# Then we save the file to the file system:
$csv->save('people.csv');
