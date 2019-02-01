Feature: Admin
  In order to validate the admin interface
  As an admin
  I need to be able to login in the admin area and see the admin options

  Background: Admin login
    Given I am on "/login"
    When I fill in "admin" for "username"
      And I fill in "admin" for "password"
    When I press "Log in"
    Then I should be on "/dapadmin/"
      And the response status code should be 200
      And I should see "DAP GraphQL"
      And I should see "Admin"
      And I should see "View my profile"        
      And I should see "Log out"           

  Scenario: Check admin page
    Given I am on "/dapadmin"
    Then I should see "Administration" 
    When I follow "Administration" 
    Then I should be on "/dapadmin/admin/"
      And the response status code should be 200
      And I should see "admin" 
      And I should see "Users"     

  Scenario: Check logout
    Given I am on "/dapadmin"
    When I follow "Log out" 
    Then I should be on homepage
      And the response status code should be 200
      And I should see "Log in"