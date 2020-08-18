Feature: user login
  As a/an user/admin
  I want to login to my account
  So that I can access my functionalities

  Background:
    Given the user has browsed to the login page

  Scenario: Admin users should be able to login successfully
    When user logs in with username "dolibarr" and password "password"
    Then the user should be logged in successfully

  Scenario: Admin users with empty fields should not be able to login
    When user logs in with username "" and password ""
    Then the user should not be logged in successfully

  Scenario Outline: User logs in with invalid credentials
    When user logs in with username "<username>" and password "<password>"
    Then error message "Bad value for login or password" should be displayed in the webUI
    And the user should not be logged in successfully
    Examples:
      | username | password |
      | dolibar  | pasword  |
      | dolibarr | pasword  |
      | dolibarr |          |
      | dolibar  |          |
      | dolibar  | password |