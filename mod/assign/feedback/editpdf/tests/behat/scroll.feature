@mod @mod_assign @assignfeedback @assignfeedback_editpdf @_file_upload
Feature: In an assignment, teacher can scroll PDF files during grading
  As a teacher
  I need to use the PDF editor

  @javascript
  Scenario: Submit a PDF files as a student and scroll the PDF as a teacher during grading
    # Be sure to check out ghostscript
    Given ghostscript is installed
    And the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Test assignment name |
      | Description | Submit your PDF file |
      | assignsubmission_file_enabled | 1 |
      | Maximum number of uploaded files | 2 |
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    And I press "Add submission"
    # student uploads pdf document
    And I upload "mod/assign/feedback/editpdf/tests/fixtures/submission.pdf" file to "File submissions" filemanager
    And I press "Save changes"
    And I should see "Submitted for grading"
    And I should see "submission.pdf"
    And I should see "Not graded"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    And I navigate to "View all submissions" in current page administration
    # The teacher views the documents uploaded by the student during grading
    And I click on "Grade" "link" in the "Submitted for grading" "table_row"
    # The process of converting a document takes a lot of time, so be sure to wait for it to complete
    And I wait for the complete PDF to load
    # I see the first page of the generated document
    And I should see "Page 1 of 2"
    # Scrolling down to the bottom of the current page
    And I scroll down
    # I see the next page
    And I should see "Page 2 of 2"
    # Scrolling down to the bottom of the current page again
    And I scroll down
    # Nothing happens because i'm on the last page
    And I should see "Page 2 of 2"
    # Scrolling up to the beginning of the current page
    And I scroll up
    # I see the previous page
    And I should see "Page 1 of 2"
    # Scrolling up to the beginning of the current page again
    And I scroll up
    # Nothing happens because i'm on the first page
    And I should see "Page 1 of 2"