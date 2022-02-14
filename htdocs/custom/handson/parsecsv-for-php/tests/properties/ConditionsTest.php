<?php

namespace ParseCsv\tests\properties;

class ConditionsTest extends BaseClass {

    public function testNotDanBrown() {
        $this->csv->conditions = 'author does not contain dan brown';

        $this->_compareWithExpected([
            'The Killing Kind',
            'The Third Secret',
            'The Last Templar',
            'The Traveller',
            'Crisis Four',
            'Prey',
            'The Broker (Paperback)',
            'Without Blood (Paperback)',
            'State of Fear (Paperback)',
            'The Rule of Four (Paperback)',
        ]);
    }

    public function testRatingEquals() {
        $rating_of_3 = [
            'The Last Templar',
            'The Broker (Paperback)',
            'Without Blood (Paperback)',
        ];
        $this->csv->conditions = 'rating = 3';
        $this->_compareWithExpected($rating_of_3);
        $this->csv->conditions = 'rating is 3';
        $this->_compareWithExpected($rating_of_3);
        $this->csv->conditions = 'rating equals 3';
        $this->_compareWithExpected($rating_of_3);
    }

    public function testRatingNotEquals() {
        $rating_not_4 = [
            'The Killing Kind',
            'The Third Secret',
            'The Last Templar',
            'The Traveller',
            'Prey',
            'The Broker (Paperback)',
            'Without Blood (Paperback)',
            'State of Fear (Paperback)',
            'Digital Fortress : A Thriller (Mass Market Paperback)',
            'Angels & Demons (Mass Market Paperback)',
        ];
//        $this->csv->conditions = 'rating != 4';
//        $this->_compareWithExpected($rating_not_4);
        $this->csv->conditions = 'rating is not 4';
        $this->_compareWithExpected($rating_not_4);
//        $this->csv->conditions = 'rating does not contain 4';
//        $this->_compareWithExpected($rating_not_4);
    }

    public function testRatingLessThan() {
        $less_than_1 = [
            'The Killing Kind',
            'The Third Secret',
        ];
        $this->csv->conditions = 'rating < 1';
        $this->_compareWithExpected($less_than_1);
        $this->csv->conditions = 'rating is less than 1';
        $this->_compareWithExpected($less_than_1);
    }

    public function testRatingLessOrEquals() {
        $less_or_equals_1 = [
            'The Killing Kind',
            'The Third Secret',
        ];
        $this->csv->conditions = 'rating <= 1';
        $this->_compareWithExpected($less_or_equals_1);
        $this->csv->conditions = 'rating is less than or equals 1';
        $this->_compareWithExpected($less_or_equals_1);
    }

    public function testRatingGreaterThan() {
        $greater_4 = [
            'The Traveller',
            'Prey',
            'State of Fear (Paperback)',
            'Digital Fortress : A Thriller (Mass Market Paperback)',
            'Angels & Demons (Mass Market Paperback)',
        ];
        $this->csv->conditions = 'rating > 4';
        $this->_compareWithExpected($greater_4);
        $this->csv->conditions = 'rating is greater than 4';
        $this->_compareWithExpected($greater_4);
    }

    public function testRatingGreaterOrEquals() {
        $greater_or_equal_4 = [
            'The Traveller',
            'Crisis Four',
            'Prey',
            'State of Fear (Paperback)',
            'The Rule of Four (Paperback)',
            'Deception Point (Paperback)',
            'Digital Fortress : A Thriller (Mass Market Paperback)',
            'Angels & Demons (Mass Market Paperback)',
            'The Da Vinci Code (Hardcover)',
        ];
        $this->csv->conditions = 'rating >= 4';
        $this->_compareWithExpected($greater_or_equal_4);
        $this->csv->conditions = 'rating is greater than or equals 4';
        $this->_compareWithExpected($greater_or_equal_4);
    }

    public function testTitleContainsSecretOrCode() {
        $this->csv->conditions = 'title contains code OR title contains SECRET';

        $this->_compareWithExpected([
            'The Third Secret',
            'The Da Vinci Code (Hardcover)',
        ]);
    }
}
