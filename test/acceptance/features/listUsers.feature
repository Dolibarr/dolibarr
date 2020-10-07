Feature: admin views userlist
  As an admin
  I want to view list of users
  So that I can manage created users

  Background:
    Given the admin has logged in using the webUI

  Scenario: Admin should be able to view list of created users
    When admin browses to the list of users page
    Then following user should be listed in the users list
      | login    | lastname   |
      | dolibarr | SuperAdmin |
    And number of created users should be 1

  Scenario: Admin user should be able to see number of created user
    Given the admin has created following users
      | login    | lastname | password    |
      | Mark     | Manson   | Mark123     |
      | Rachel   | Green    | Rachel123   |
      | Chandler | Bing     | Chandler123 |
    When admin browses to the list of users page
    Then following user should be listed in the users list
      | login    | lastname   |
      | dolibarr | SuperAdmin |
      | Mark     | Manson     |
      | Rachel   | Green      |
      | Chandler | Bing       |
    And number of created users should be 4
