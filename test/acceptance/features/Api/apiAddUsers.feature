Feature: Add user
  As an admin
  I want to add users
  So that the authorized access is possible

  Scenario: Admin adds user without permission
    Given the user with login "harrypotter@gmail.com" does not exist
    When the admin creates user with following details using API
      | last name | Potter                |
      | login     | harrypotter@gmail.com |
      | password  | password              |
    Then the response status code should be "200"
    And user with login "harrypotter@gmail.com" should exist

  Scenario: Admin creates already existing user
    Given the admin has created the following users
      | login | last name | password |
      | Harry | Potter    | hello123 |
    When the admin creates user with following details using API
      | last name | Potter   |
      | login     | Harry    |
      | password  | hello123 |
    Then the response status code should be "500"
    And the response message should be "ErrorLoginAlreadyExists"

  Scenario Outline: Admin adds user with incomplete essential credentials
    Given the user with login "Harry" does not exist
    When the admin creates user with following details using API
      | last name | <last name> |
      | login     | Harry       |
      | password  | <password>  |
    Then the response status code should be "200"
    And user with login "Harry" should exist
    Examples:
      | last name | password |
      |           |          |
      | Manson    |          |
      |           | 123      |

  Scenario Outline: Admin adds user without login
    Given the user with login "harrypotter@gmail.com" does not exist
    When the admin creates user with following details using API
      | last name | <last name> |
      | login     |             |
      | password  | <password>  |
    Then the response status code should be "500"
    And the response message should be "Field 'Login' is required"
    Examples:
      | last name | password |
      | Potter    | Hello123 |
      | Potter    |          |
      |           | hello123 |

  Scenario Outline: Admin adds user with last name as special characters
    Given the user with login "<login>" does not exist
    When the admin creates user with following details using API
      | last name | <last name> |
      | login     | <login>     |
      | password  | password    |
    Then the response status code should be "200"
    And user with login "<login>" should exist
    Examples:
      | last name     | login                  |
      | swi@          | s$5^2                  |
      | g!!@%ui       | नेपाली                 |
      | swikriti@h    | सिमप्ले $%#?&@name.txt |
      | !@#$%^&*()-_+ | España§àôœ€            |

  Scenario: Non-admin user with api key adds user
    Given the admin has created the following users
      | login | last name | password | api_key     |
      | Harry | Potter    | hello123 | harrypotter |
    When the non-admin user "Harry" with password "hello123" creates user with following details using API
      | last name | Potter   |
      | login     | Ginny    |
      | password  | password |
    Then the response status code should be "200"
    And user with login "Ginny" should exist
