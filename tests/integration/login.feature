Feature: Login
  @e2e-test
  Scenario: Can login as admin

    Given I am on "/user"
    Then I should see the form "#user-login-form"
    When I enter test credentials
    When I submit the form
    When I reset context
    Then I preserve cookies
  
  @e2e-test
  Scenario: Can add article
    Given I am on "/node/add/article"
    Then I should see the form "#node-article-form"
    When I fill out text fields with values
      | label                | text                                                 |
      | Title                | zzz - Integration test article                       |
    Then I select "2022" from the field labeled "Year"
    And I fill out the ckeditor labeled "Body" with "Body text example"
    Then I select "CSC" from the field labeled "Group"
    And I clear context
    Then I click the "#edit-actions--2 #edit-submit--2" element
    Then I preserve cookies

  @e2e-test
  Scenario: Can add basic page
    Given I am on "/node/add/page"
    Then I should see the form "#node-page-form"
    When I fill out text fields with values
      | label                | text                                                 |
      | Title                | zzz - Integration test basic page                    |
    And I fill out the ckeditor labeled "Body" with "Body text example"
    Then I select "CSC" from the field labeled "Group"
    And I clear context
    Then I click the "#edit-actions--2 #edit-submit--2" element
    Then I preserve cookies

  @e2e-test
  Scenario: Can add landing page
    Given I am on "/node/add/landing_page"
    Then I should see the form "#node-landing-page-form"
    When I fill out text fields with values
      | label                | text                                                 |
      | Title                | zzz - Integration test landing page                  |
    And I fill out the ckeditor labeled "Body" with "Body text example"
    Then I select "CSC" from the field labeled "Group"
    And I clear context
    Then I click the "#edit-actions--2 #edit-submit--2" element
    Then I preserve cookies

  @e2e-test
  Scenario: Can add magazine
    Given I am on "/node/add/magazine"
    Then I should see the form "#node-magazine-form"
    When I fill out text fields with values
      | label                | text                                                 |
      | Title                | zzz - Integration test magazine                      |
    And I fill out the ckeditor labeled "Body" with "Body text example"
    Then I select "Other" from the field labeled "Magazine Type"
    Then I select "2022" from the field labeled "Year"
    And I clear context
    Then I click the "#edit-actions--2 #edit-submit--2" element
    Then I preserve cookies

  @e2e-test
  Scenario: Can add opportunities item
    Given I am on "/node/add/opportunities_item"
    Then I should see the form "#node-opportunities-item-form"
    When I fill out text fields with values
      | label                | text                                                 |
      | Title                | zzz - Integration test opportunities item            |
    And I fill out the ckeditor labeled "Body" with "Body text example"
    Then I select "CSC" from the field labeled "Group"
    Then I click the "Add media" button
    Then I set context to ".ui-dialog"
    Then I should see "Allowed types"
    When I fill in the field labeled "Add files" with file "jpg.jpg" of type "image/jpg"
    Then I click the "button.media-library-select" element
    And I clear context
    Then I click the "#edit-actions--2 #edit-submit--2" element
    Then I preserve cookies

  @e2e-test
  Scenario: Can add person
    Given I am on "/node/add/person"
    Then I should see the form "#node-person-form"
    When I fill out text fields with values
      | label                | text                                                 |
      | Name                | zzz - Integration test person                         |
    And I fill out the ckeditor labeled "Body" with "Body text example"
    Then I select "CSC" from the field labeled "Group"
    Then I click the "Add media" button
    Then I set context to ".ui-dialog"
    Then I should see "Allowed types"
    When I fill in the field labeled "Add file" with file "jpg.jpg" of type "image/jpg"
    Then I should see "Remove"
    Then I fill out the field labeled "Alternative text" with "alt text"
    Then I click the "button.form-submit" element
    When I should see "Allowed types"
    Then I click the "button.form-submit" element
    And I clear context
    Then I click the "#edit-actions--2 #edit-submit--2" element
    Then I preserve cookies

  @e2e-test
  Scenario: Can add timeline item
    Given I am on "/node/add/timeline_item"
    Then I should see the form "#node-timeline-item-form"
    When I fill out text fields with values
      | label                | text                                                 |
      | Title                | zzz - Integration test timeline item                 |
    And I fill out the ckeditor labeled "Body" with "Body text example"
    And I clear context
    Then I click the "#edit-actions--2 #edit-submit--2" element