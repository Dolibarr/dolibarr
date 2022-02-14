<?php

// This file should not be used at all! Instead, please use Composer's autoload.
// It purely exists to reduce the maintenance burden for existing code using
// this repository.

// Check if people used Composer to include this project in theirs
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/src/enums/AbstractEnum.php';
    require __DIR__ . '/src/enums/DatatypeEnum.php';
    require __DIR__ . '/src/enums/FileProcessingModeEnum.php';
    require __DIR__ . '/src/enums/SortEnum.php';
    require __DIR__ . '/src/extensions/DatatypeTrait.php';
    require __DIR__ . '/src/Csv.php';
} else {
    require __DIR__ . '/vendor/autoload.php';
}


// This wrapper class should not be used by new projects. Please look at the
// examples to find the up-to-date way of using this repo.
class parseCSV extends ParseCsv\Csv {

}
