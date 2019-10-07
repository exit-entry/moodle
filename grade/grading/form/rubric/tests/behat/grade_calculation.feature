@gradingform @gradingform_rubric
Feature: Converting rubric score to grades
  In order to use and refine rubrics to grade students
  As a teacher
  I need to be able to use different grade settings

  @javascript
  Scenario:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1 | topics |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Test assignment 1 name |
      | Description | Test assignment description |
      | Grading method | Rubric |
    When I go to "Test assignment 1 name" advanced grading definition page
    # Defining a rubric.
    And I set the following fields to these values:
      | Name | Assignment 1 rubric |
      | Description | Rubric test description |
    And I define the following rubric:
      | Criterion 1 | Level 11 | 0 | Level 12 | 5 | Level 13 | 10 |
      | Criterion 2 | Level 21 | 0 | Level 22 | 5 | Level 23 | 10 |
      | Criterion 3 | Level 31 | 0 | Level 32 | 5 | Level 33 | 10 |
    And I press "Save rubric and make it ready"
    And I am on "Course 1" course homepage
    And I wait "3" seconds
    # Grading a student.
    And I go to "Student 1" "Test assignment 1" activity advanced grading page
    And I grade by filling the rubric with:
      | Criterion 1 | 10 | Very good |
      | Criterion 2 | 5  | You can do it better |
      | Criterion 3 | 5  | Not good |
    And I save the advanced grading form
    # Checking that the user grade is correct.
    And I wait "3" seconds
    And I should see "66.67" in the "student1@example.com" "table_row"
    And I log out