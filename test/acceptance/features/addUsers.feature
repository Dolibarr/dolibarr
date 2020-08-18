Feature: add users
  As an admin
  I want to add users
  So that authorised access is given

  Background:
    Given the admin has logged in using the webUI
    And the admin has browsed to the new users page

  Scenario: Admin adds users with no permission
    When the admin creates user with following details
      | lastname      | Buffay        |
      | login         | abc@gmail.com |
      | password      | password      |
      | administrator | No            |
    Then new user "Buffay" should be created
    And message "This user has no permissions defined" should be displayed in the webUI

  Scenario Outline: Admin adds users with permission
    When the admin creates user with following details
      | lastname      | Buffay          |
      | login         | abc@gmail.com   |
      | password      | password        |
      | administrator | <administrator> |
      | gender        | <gender>        |
    Then message "This user has no permissions defined" <shouldOrShouldnot> be displayed in the webUI
    And new user "Buffay" should be created
    Examples:
      | administrator | gender | shouldOrShouldnot |
      | No            |        | should            |
      | No            | Man    | should            |
      | No            | Woman  | should            |
      | Yes           |        | shouldnot         |
      | Yes           | Man    | shouldnot         |
      | Yes           | Woman  | shouldnot         |

  Scenario Outline: Admin adds user with incomplete credentials
    When the admin creates user with following details
      | lastname | <lastname> |
      | login    | <login>    |
      | password | <password> |
    Then message "<message>" should be displayed in the webUI
    And new user "<lastname>" should not be created
    Examples:
      | lastname  | login         | password | message                                     |
      |           |               |          | Name is not defined.\nLogin is not defined. |
      | Buffay    |               | abc      | Login is not defined.                       |
      |           | abc@gmail.com | abc      | Name is not defined.                        |
      | Tribbiani |               |          | Login is not defined.                       |

  Scenario Outline: Admin adds users with special character
    When the admin creates user with following details
      | lastname | <last name>   |
      | login    | abc@gmail.com |
      | password | password      |
    Then message "This user has no permissions defined" should be displayed in the webUI
    And new user "<last name>" should be created
    Examples:
      | last name               |
      | #dhikari                |
      | 2@2@                    |
      | @#$%                    |
      | AS123@                  |
      | नेपाली                  |
      | España§àôœ€             |
      | @#$%^&*()-_+=[]{}:;     |
      | सिमप्ले $%#?&@ name.txt |

  Scenario: Admin tries to add users with pre existing user's login
    Given the user has been created with following details
      | lastname | login               | password |
      | Buffay   | amuna.adk@gmail.com | 12345    |
    And the admin has browsed to the new users page
    When the admin creates user with following details
      | lastname | Manson              |
      | login    | amuna.adk@gmail.com |
      | password | 5678                |
    Then message "Login already exists." should be displayed in the webUI
    And new user "Manson" should not be created

  Scenario: Admin adds user with incomplete credentials
    When the admin creates user with following details
      | lastname | Buffay        |
      | login    | abc@gmail.com |
      | password |               |
    Then message "This user has no permissions defined" should be displayed in the webUI
    And new user "Buffay" should not be created