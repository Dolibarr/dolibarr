<?php

namespace ParseCsv\tests\properties;

class SortByTest extends BaseClass {

    public function testSortByRating() {
        $this->csv->sort_by = 'rating';
        $this->csv->conditions = 'title does not contain Blood';
        $this->_compareWithExpected([
            // Rating 0
            'The Killing Kind',
            'The Third Secret',

            // Rating 3
            'The Last Templar',
            'The Broker (Paperback)',

            // Rating 4
            'Deception Point (Paperback)',
            'The Rule of Four (Paperback)',
            'The Da Vinci Code (Hardcover)',

            // Rating 5
            'State of Fear (Paperback)',
            'Prey',
            'Digital Fortress : A Thriller (Mass Market Paperback)',
            'Angels & Demons (Mass Market Paperback)',
        ]);
    }

    public function testReverseSortByRating() {
        $this->csv->sort_by = 'rating';
        $this->csv->conditions =
            'title does not contain Prey AND ' .
            'title does not contain Fortress AND ' .
            'title does not contain Blood AND ' .
            'title does not contain Fear';
        $this->csv->sort_reverse = true;
        $this->_compareWithExpected([

            // Rating 5
            'Angels & Demons (Mass Market Paperback)',
            'The Traveller',

            // Rating 4
            'The Da Vinci Code (Hardcover)',
            'The Rule of Four (Paperback)',
            'Deception Point (Paperback)',

            // Rating 3
            'The Broker (Paperback)',
            'The Last Templar',

            // Rating 0
            'The Third Secret',
            'The Killing Kind',
        ]);
    }
}
