Feature: Session
  In order to validate the API session interface
  As a visitor
  I need to be able to register in the API

  Background: Session Actions
    Given I am on homepage

  Scenario: User register login
    Given I am on "/register"
  
  Scenario: Redirect after login
    When I follow "Log in"
    And I fill in "ricardo" for "Username"
    And I fill in "123456789" for "Password"