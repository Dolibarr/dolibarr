Feature: list users
  As an admin user
  I want to view the list of users
  So that I can manage users

  Background:
    Given the administrator has logged in using the webUI

  Scenario: Admin user should be able to see list of created users when no new users are created
    When the administrator browses to the list of users page using the webUI
    Then following users should be displayed in the users list
      | login    | last name  |
      | dolibarr | SuperAdmin |
    And the number of created users should be 1

  Scenario: Admin user should be able to see number of created users
    Given the admin has created the following users
      | login    | last name | password |
      | Harry    | Potter    | hello123 |
      | Hermoine | Granger   | hello123 |
      | Ron      | Weasley   | hello123 |
    When the administrator browses to the list of users page using the webUI
    Then following users should be displayed in the users list
      | login    | last name  |
      | dolibarr | SuperAdmin |
      | Harry    | Potter     |
      | Hermoine | Granger    |
      | Ron      | Weasley    |
    And the number of created users should be 4
