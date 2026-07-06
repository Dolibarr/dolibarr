Feature: user login
  As a user/admin
  I want to login to my account
  So that I can have access to my functionality

  Background:
    Given the user has browsed to the login page

  Scenario: Admin user should be able to login successfully
    When user logs in with username "dolibarr" and password "password"
    Then the user should be directed to the homepage

  Scenario: Admin user with empty credentials should not be able to login
    When user logs in with username "" and password ""
    Then the user should not be able to login

  Scenario Outline: user logs in with invalid credentials
    When user logs in with username "<username>" and password "<password>"
    Then the user should not be able to login
    And error message "Bad value for login or password" should be displayed in the webUI
    Examples:
      | username | password |
      | dolibarr | pass     |
      | dolibarr | passw    |
      | dolibarr |          |
      | dolibarr | password |
