Feature: enable/disable modules/applications
  As an admin
  I want to be able to enable or disable modules or applications
  So that I can work on the required modules

  Scenario: admin enables the modules in the human resource management section
    Given the administrator has logged in using the webUI
    When the administrator browses to the modulesApplications page
    Then the "USERS & GROUPS" module should be auto-enabled
    And the number of activated modules should be 0
    When the administrator enables the following modules:
      | modules                  |
      | Members                  |
      | Leave Request Management |
      | Expense Reports          |
    Then the number of activated modules should be 3
    And the following modules should be displayed in the navigation bar:
    | modules |
    | Members |
    | HRM     |

