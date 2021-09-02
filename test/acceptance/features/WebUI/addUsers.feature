Feature: Add user
  As an admin
  I want to add users
  So that the authorized access is possible

  Background:
    Given the administrator has logged in using the webUI
    And the administrator has browsed to the new users page

  Scenario: Admin adds user without permission
    When the admin creates user with following details
      | last name | Potter                |
      | login     | harrypotter@gmail.com |
      | password  | password              |
    Then new user "Potter" should be created
    And message "This user has no permissions defined" should be displayed in the webUI

  Scenario Outline: Admin adds user with permission
    When the admin creates user with following details
      | last name     | Potter                |
      | login         | harrypotter@gmail.com |
      | password      | password              |
      | administrator | <administrator>       |
      | gender        | <gender>              |
    Then message "This user has no permissions defined" <shouldOrShouldNot> be displayed in the webUI
    And new user "Potter" should be created
    Examples:
      | administrator | gender | shouldOrShouldNot |
      | No            |        | should            |
      | No            | Man    | should            |
      | No            | Woman  | should            |
      | Yes           |        | should not        |
      | Yes           | Man    | should not        |
      | Yes           | Woman  | should not        |

  Scenario Outline: Admin adds user with last name as special characters
    When the admin creates user with following details
      | last name | <last name> |
      | login     | harry       |
      | password  | password    |
    Then message "This user has no permissions defined" should be displayed in the webUI
    And new user "<last name>" should be created
    Examples:
      | last name                  |
      | swi@                       |
      | g!!@%ui                    |
      | swikriti@h                 |
      | !@#$%^&*()-_+=[]{}:;,.<>?~ |
      | $w!kr!t!                   |
      | España§àôœ€                |
      | नेपाली                     |
      | सिमप्ले $%#?&@name.txt     |

  Scenario Outline: Admin adds user with incomplete essential credentials
    When the admin creates user with following details
      | last name | <last name> |
      | login     | <login>     |
      | password  | <password>  |
    Then message "<message>" should be displayed in the webUI
    And new user "<last name>" should not be created
    Examples:
      | last name | login          | password | message                                     |
      |           |                |          | Name is not defined.\nLogin is not defined. |
      | Joseph    |                |          | Login is not defined.                       |
      |           | john@gmail.com |          | Name is not defined.                        |
      | Joseph    |                | hihi     | Login is not defined.                       |

  Scenario: Admin adds user with incomplete essential credentials
    When the admin creates user with following details
      | last name | Doe  |
      | login     | John |
      | password  |      |
    Then message "This user has no permissions defined" should be displayed in the webUI
    And new user "Doe" should be created

  Scenario: Admin tries to add user with pre-existing login credential
    Given a user has been created with following details
      | login | last name | password |
      | Tyler | Joseph    | pass1234 |
    And the administrator has browsed to the new users page
    When the admin creates user with following details
      | last name | Dun      |
      | login     | Tyler    |
      | password  | pass1234 |
    Then message "Login already exists." should be displayed in the webUI
    And new user "Dun" should not be created
