Feature: Homepage
  In order to validate the homepage
  As a visitor
  I need to be able to see the valid homepage

  Background: Check homepage
    Given I am on homepage
    Then the response status code should be 200
      And I should see "Digital Asset Platform"

  Scenario: Not see menu
    Given I should not see "DAP GraphQL"

  Scenario: Not available to access admin area
    Given I go to "/dapadmin"
    Then I should be on "/login"
    Then the response status code should be 200

  Scenario: Check Login page a d invalid credentials
    Given I am on homepage
    When I follow "Log in" 
    Then I should be on "/login"
      And the response status code should be 200
      And I should see "username"
      And I should see "password"           

  Scenario: Check invalid login with wrong credentials
    Given I am on "/login"  
    Given I fill in "admin" for "username"
      And I fill in "noadmin" for "password"
    When I press "Log in"
    Then I should be on "/login"
      And the response status code should be 200
      And I should see "Invalid credentials."                 
