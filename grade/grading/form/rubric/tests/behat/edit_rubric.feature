@gradingform @gradingform_rubric
Feature: Rubrics can be created and edited
  In order to use and refine rubrics to grade students
  As a teacher
  I need to edit previously used rubrics

  @javascript
  Scenario: I can use rubrics to grade and edit them later updating students grades
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
    # Grading а student.
    And I go to "Student 1" "Test assignment 1 name" activity advanced grading page
    And I grade by filling the rubric with:
      | Criterion 1 | 10 | Very good |
    And I press "Save changes"
    And I wait "3" seconds
    # Checking that it complains if you don't select a level for each criterion.
    And I should see "Please choose something for each criterion"
    And I grade by filling the rubric with:
      | Criterion 1 | 10 | Very good |
      | Criterion 2 | 5  | Mmmm, you can do it better |
      | Criterion 3 | 5  | Not good |
    And I complete the advanced grading form with these values:
      | Feedback comments | In general... work harder... |
    # Checking that the user grade is correct.
    And I wait "3" seconds
    And I should see "66.67" in the "student1@example.com" "table_row"
    # Updating the user grade.
    And I go to "Student 1" "Test assignment 1 name" activity advanced grading page
    And I grade by filling the rubric with:
      | Criterion 1 | 5 | Bad, I changed my mind |
      | Criterion 2 | 5 | Mmmm, you can do it better |
      | Criterion 3 | 5 | Not good |
    #And the level with "50" points was previously selected for the rubric criterion "Criterion 1"
    #And the level with "20" points is selected for the rubric criterion "Criterion 1"
    And I save the advanced grading form
    And I wait "3" seconds
    And I should see "50.00" in the "student1@example.com" "table_row"
    And I log out
    # Viewing it as a student.
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test assignment 1 name"
    And I should see "50.00" in the ".feedback" "css_element"
    And I should see "Rubric test description" in the ".feedback" "css_element"
    And I should see "In general... work harder..."
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I wait "3" seconds
    # Editing a rubric definition without regrading students.
    And I go to "Test assignment 1 name" advanced grading definition page
    And "Save as draft" "button" should not exist
    And I click on "Move down" "button" in the "Criterion 1" "table_row"
    And I replace "Level 11" rubric level with "Level 11 edited" in "Criterion 1" criterion
    And I press "Save"
    And I should see "You are about to save changes to a rubric that has already been used for grading."
    And I set the field "menurubricregrade" to "Do not mark for regrade"
    And I press "Continue"
    And I am on "Course 1" course homepage
    And I wait "3" seconds
    And I log out
    # Check that the student still sees the grade.
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test assignment 1 name"
    And I should see "50.00" in the ".feedback" "css_element"
    And I log out
    # Editing a rubric with significant changes.
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I go to "Test assignment 1 name" advanced grading definition page
    And I click on "Move down" "button" in the "Criterion 2" "table_row"
    And I replace "5" rubric level with "6" in "Criterion 1" criterion
    And I press "Save"
    And I should see "You are about to save significant changes to a rubric that has already been used for grading."
    And I press "Continue"
    And I am on "Course 1" course homepage
    And I wait "3" seconds
    And I log out
    # Check that the student doesn't see the grade.
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test assignment 1 name"
    And I should see "50.00" in the ".feedback" "css_element"
    And I log out
    # Regrade student.
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test assignment 1 name"
    And I go to "Student 1" "Test assignment 1 name" activity advanced grading page
    And I should see "The rubric definition was changed after this student had been graded. The student can not see this rubric until you check the rubric and update the grade."
    And I save the advanced grading form
    And I log out
    # Check that the student sees the grade again.
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test assignment 1 name"
    And I should see "53.33" in the ".feedback" "css_element"
    And I log out
    # Hide all rubric info for students
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I go to "Test assignment 1 name" advanced grading definition page
    And I set the field "Allow users to preview rubric (otherwise it will only be displayed after grading)" to ""
    And I set the field "Display rubric description during evaluation" to ""
    And I set the field "Display rubric description to those being graded" to ""
    And I set the field "Display points for each level during evaluation" to ""
    And I set the field "Display points for each level to those being graded" to ""
    And I press "Save"
    And I set the field "menurubricregrade" to "Do not mark for regrade"
    And I press "Continue"
    And I am on "Course 1" course homepage
    And I wait "3" seconds
    And I log out
    # Students should not see anything.
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test assignment 1 name"
    And I should not see "Criterion 1" in the ".submissionstatustable" "css_element"
    And I should not see "Criterion 2" in the ".submissionstatustable" "css_element"
    And I should not see "Criterion 3" in the ".submissionstatustable" "css_element"
    And I should not see "Rubric test description" in the ".feedback" "css_element"
