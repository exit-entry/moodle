<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Contains renderer used for displaying rubric
 *
 * @package    gradingform_rubric
 * @copyright  2011 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Grading method plugin renderer
 *
 * @package    gradingform_rubric
 * @copyright  2011 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gradingform_rubric_renderer extends plugin_renderer_base {

    /**
     * This function returns html code for displaying criterion. Depending on $mode it may be the
     * code to edit rubric, to preview the rubric, to evaluate somebody or to review the evaluation.
     *
     * This function may be called from display_rubric() to display the whole rubric, or it can be
     * called by itself to return a template used by JavaScript to add new empty criteria to the
     * rubric being designed.
     * In this case it will use macros like {NAME}, {LEVELS}, {CRITERION-id}, etc.
     *
     * When overriding this function it is very important to remember that all elements of html
     * form (in edit or evaluate mode) must have the name $elementname.
     *
     * Also JavaScript relies on the class names of elements and when developer changes them
     * script might stop working.
     *
     * @param int $mode rubric display mode, see {@link gradingform_rubric_controller}
     * @param array $options display options for this rubric, defaults are: {@link gradingform_rubric_controller::get_default_options()}
     * @param string $elementname the name of the form element (in editor mode) or the prefix for div ids (in view mode)
     * @param array|null $criterion criterion data
     * @param string $levelsstr evaluated templates for this criterion levels
     * @param array|null $value (only in view mode) teacher's feedback on this criterion
     * @throws coding_exception
     * @return string
     */
    public function criterion_template($mode, $options, $elementname = '{NAME}', $criterion = null, $levelsstr = '{LEVELS}', $value = null) {
        $data = new stdClass();
        $sortorderButton = new stdClass();
        $description = new stdClass();
        $hiddenDescription = new stdClass();
        $criteriaWeightObj = new stdClass();
        $levels = new stdClass();
        $remarkinfo = new stdClass();

        // TODO MDL-31235 description format, remark format
        if ($criterion === null || !is_array($criterion) || !array_key_exists('id', $criterion)) {
            $criterion = [
            'id' => '{CRITERION-id}',
            'description' => '{CRITERION-description}',
            'sortorder' => '{CRITERION-sortorder}',
            'class' => '{CRITERION-class}',
            'weight' => false,
        ];
        } else {
            foreach (['sortorder', 'description', 'class', 'weight'] as $key) {
                // set missing array elements to empty strings to avoid warnings
                if (!array_key_exists($key, $criterion)) {
                    $criterion[$key] = '';
                }
            }
        }

        $data->row_id = '{NAME}-criteria-{CRITERION-id}';
        $data->weight = $criterion['weight'];

        $data->DISPLAY_EDIT_FULL = ($mode == gradingform_rubric_controller::DISPLAY_EDIT_FULL);

        if ($mode == gradingform_rubric_controller::DISPLAY_EDIT_FULL) {
            $data->DISPLAY_EDIT_FULL = true;
            foreach (array('moveup', 'delete', 'movedown', 'duplicate') as $key) {
                $buttonObj = new stdClass();
                $value = get_string('criterion' . $key, 'gradingform_rubric');
                $buttonObj->id = '{NAME}-criteria-{CRITERION-id}-' . $key;
                $buttonObj->class = $key;
                $buttonObj->name = '{NAME}[criteria][{CRITERION-id}][' . $key . ']';
                $buttonObj->value = $value;

                $data->actions[] = $buttonObj;
            }

            $sortorderButton->name = '{NAME}[criteria][{CRITERION-id}][sortorder]';
            $sortorderButton->value = $criterion['sortorder'];
            $data->sortorder = $sortorderButton;

            $description->name = '{NAME}[criteria][{CRITERION-id}][description]';
            $description->id = '{NAME}-criteria-{CRITERION-id}-description';
            $description->ariaLabel = get_string('criterion', 'gradingform_rubric', '');
            $description->cols = '10';
            $description->rows = '5';
            $description->content = s($criterion['description']);

            $criteriaWeightObj->name = '{NAME}[criteria][{CRITERION-id}][weight]';
            $criteriaWeightObj->id = '{NAME}-criteria-{CRITERION-id}-weight';
            $criteriaWeightObj->ariaLabel = 'Weight';
            $criteriaWeightObj->type = 'text';
            $criteriaWeightObj->value = $criterion['weight'] ? (int)$criterion['weight'] : 100;
            $criteriaWeightObj->size = 3;
            $criteriaWeightObj->wrap_id = '{NAME}-criteria-{CRITERION-id}-weightvalue';
            $criteriaWeightObj->wrap_class = 'weightvalue';
            $criteriaWeightObj->criteria_class = 'weight';

            if (isset($level['error_weight'])) {
                $criteriaWeightObj->criteria_class .= ' error';
            }
        } else {
            $data->DISPLAY_EDIT_FULL = false;
            $description->content = s($criterion['description']);
            $criteriaWeightObj->value = $criterion['weight'] ? (int)$criterion['weight'] : 100;
            if ($mode == gradingform_rubric_controller::DISPLAY_EDIT_FROZEN) {
                $data->DISPLAY_EDIT_FROZEN = gradingform_rubric_controller::DISPLAY_EDIT_FROZEN;

                $sortorderButton->name = '{NAME}[criteria][{CRITERION-id}][sortorder]';
                $sortorderButton->value = $criterion['sortorder'];

                $hiddenDescription->name = '{NAME}[criteria][{CRITERION-id}][description]';
                $hiddenDescription->content = $criterion['description'];
            }
        }

        $levels->class = 'levels';
        $levels->tableparams->id = '{NAME}-criteria-{CRITERION-id}-levels-table';
        $levels->tableparams->ariaLabel = get_string('levelsgroup', 'gradingform_rubric');

        $levels->rowparams->id = '{NAME}-criteria-{CRITERION-id}-levels';
        $levels->rowparams->str = $levelsstr;
        if ($mode != gradingform_rubric_controller::DISPLAY_EDIT_FULL) {
            $levels->rowparams->role = 'radiogroup';
        }

        if (isset($criterion['error_levels'])) {
            $levels->class .= ' error';
        }

        if ($mode == gradingform_rubric_controller::DISPLAY_EDIT_FULL) {
            $levels->addLevels->submit->name = '{NAME}[criteria][{CRITERION-id}][levels][addlevel]';
            $levels->addLevels->submit->id = '{NAME}-criteria-{CRITERION-id}-levels-addlevel';
            $levels->addLevels->submit->value = get_string('criterionaddlevel', 'gradingform_rubric');
        }

        $displayremark = ($options['enableremarks'] && ($mode != gradingform_rubric_controller::DISPLAY_VIEW || $options['showremarksstudent']));

        if ($displayremark) {
            $currentremark = '';
            if (isset($value['remark'])) {
                $currentremark = $value['remark'];
            }

            $remarkinfo->description = s($criterion['description']);
            $remarkinfo->remark = $currentremark;
            $remarklabeltext = get_string('criterionremark', 'gradingform_rubric', $remarkinfo);

            if ($mode == gradingform_rubric_controller::DISPLAY_EVAL) {
                $remarkinfo->DISPLAY_EVAL = true;
                $remarkinfo->name = '{NAME}[criteria][{CRITERION-id}][remark]';
                $remarkinfo->id = '{NAME}-criteria-{CRITERION-id}-remark';
                $remarkinfo->cols = '10';
                $remarkinfo->rows = '5';
                $remarkinfo->ariaLabel = $remarklabeltext;
                $remarkinfo->remark = s($currentremark);

            } else if ($mode == gradingform_rubric_controller::DISPLAY_EVAL_FROZEN) {
                $remarkinfo->DISPLAY_EVAL_FROZEN = true;
                $remarkinfo->name = '{NAME}[criteria][{CRITERION-id}][remark]';
                $remarkinfo->value = $currentremark;
            } else if ($mode == gradingform_rubric_controller::DISPLAY_REVIEW || $mode == gradingform_rubric_controller::DISPLAY_VIEW) {
                $remarkinfo->DISPLAY_VIEW = true;
                $remarkinfo->remark = $currentremark;
                $remarkinfo->class = 'remark';
                $remarkinfo->tabindex = '0';
                $remarkinfo->id = '{NAME}-criteria-{CRITERION-id}-remark';
                $remarkinfo->ariaLabel = $remarklabeltext;
            }
        }
        $data->weight = $criteriaWeightObj;
        $data->hiddenDescription = $hiddenDescription;
        $data->description = $description;
        $data->sortorder = $sortorderButton;
        $data->levels = $levels;
        $data->remarkinfo = $remarkinfo;

        try {
            $html = parent::render_from_template('gradingform_rubric/criterion_template', $data);
        } catch (Exception $exception) {
            $html = '';
        }

        $html = str_replace('{NAME}', $elementname, $html);
        $html = str_replace('{CRITERION-id}', $criterion['id'], $html);

        return $html;
    }

    /**
     * This function returns html code for displaying one level of one criterion. Depending on $mode
     * it may be the code to edit rubric, to preview the rubric, to evaluate somebody or to review the evaluation.
     *
     * This function may be called from display_rubric() to display the whole rubric, or it can be
     * called by itself to return a template used by JavaScript to add new empty level to the
     * criterion during the design of rubric.
     * In this case it will use macros like {NAME}, {CRITERION-id}, {LEVEL-id}, etc.
     *
     * When overriding this function it is very important to remember that all elements of html
     * form (in edit or evaluate mode) must have the name $elementname.
     *
     * Also JavaScript relies on the class names of elements and when developer changes them
     * script might stop working.
     *
     * @param int $mode rubric display mode see {@link gradingform_rubric_controller}
     * @param array $options display options for this rubric, defaults are: {@link gradingform_rubric_controller::get_default_options()}
     * @param string $elementname the name of the form element (in editor mode) or the prefix for div ids (in view mode)
     * @param string|int $criterionid either id of the nesting criterion or a macro for template
     * @param array|null $level level data, also in view mode it might also have property $level['checked'] whether this level is checked
     * @return string
     * @throws coding_exception
     */
    public function level_template($mode, $options, $elementname = '{NAME}', $criterionid = '{CRITERION-id}', $level = null) {
        $data = new stdClass();

        // TODO MDL-31235 definition format
        if (!isset($level['id'])) {
            $level = array('id' => '{LEVEL-id}', 'definition' => '{LEVEL-definition}', 'score' => '{LEVEL-score}', 'class' => '{LEVEL-class}', 'checked' => false);
        } else {
            foreach (array('score', 'definition', 'class', 'checked', 'index') as $key) {
                // set missing array elements to empty strings to avoid warnings
                if (!array_key_exists($key, $level)) {
                    $level[$key] = '';
                }
            }
        }

        // Get level index.
        $levelindex = isset($level['index']) ? $level['index'] : '{LEVEL-index}';

        // Template for one level within one criterion
        $tdattributes = array(
            'id' => '{NAME}-criteria-{CRITERION-id}-levels-{LEVEL-id}',
            'class' => 'level' . $level['class']
        );
        if (isset($level['tdwidth'])) {
            $tdattributes['width'] = round($level['tdwidth']).'%';
        }

        $levelWrapper = array('class' => 'level-wrapper');
        $data->levelWrapper = (object)$levelWrapper;

        if ($mode == gradingform_rubric_controller::DISPLAY_EDIT_FULL) {
            $definitionparams = array(
                'id' => '{NAME}-criteria-{CRITERION-id}-levels-{LEVEL-id}-definition',
                'name' => '{NAME}[criteria][{CRITERION-id}][levels][{LEVEL-id}][definition]',
                'ariaLabel' => get_string('leveldefinition', 'gradingform_rubric', $levelindex),
                'cols' => '10', 'rows' => '4',
                'value' => s($level['definition'])
            );
            $data->definition->params = (object)$definitionparams;

            $scoreparams = array(
                'type' => 'text',
                'id' => '{NAME}[criteria][{CRITERION-id}][levels][{LEVEL-id}][score]',
                'name' => '{NAME}[criteria][{CRITERION-id}][levels][{LEVEL-id}][score]',
                'ariaLabel' => get_string('scoreinputforlevel', 'gradingform_rubric', $levelindex),
                'size' => '3',
                'value' => $level['score'],
            );
            $data->score->params = (object)$scoreparams;
        } else {
            if ($mode == gradingform_rubric_controller::DISPLAY_EDIT_FROZEN) {
                $data->DISPLAY_EDIT_FROZEN = true;
                $frozenDefinition = array('type' => 'hidden', 'name' => '{NAME}[criteria][{CRITERION-id}][levels][{LEVEL-id}][definition]', 'value' => $level['definition']);
                $data->frozenDefinition = (object)$frozenDefinition;
                $frozenScore = array('type' => 'hidden', 'name' => '{NAME}[criteria][{CRITERION-id}][levels][{LEVEL-id}][score]', 'value' => $level['score']);
                $data->frozenScore = (object)$frozenScore;
            }
            $data->definition->params->value = s($level['definition']);
            $data->score->params->value = $level['score'];
        }
        if ($mode == gradingform_rubric_controller::DISPLAY_EVAL) {
            $data->DISPLAY_EVAL = true;
            $levelradioparams = array(
                'type' => 'radio',
                'id' => '{NAME}-criteria-{CRITERION-id}-levels-{LEVEL-id}-definition',
                'name' => '{NAME}[criteria][{CRITERION-id}][levelid]',
                'value' => $level['id']
            );
            if ($level['checked']) {
                $levelradioparams['checked'] = 'checked';
            }

            $data->eval->params = (object)$levelradioparams;
        }
        if ($mode == gradingform_rubric_controller::DISPLAY_EVAL_FROZEN && $level['checked']) {
            $data->DISPLAY_EVAL_FROZEN = true;
            $evalFrozenParams =  array(
                'type' => 'hidden',
                'name' => '{NAME}[criteria][{CRITERION-id}][levelid]',
                'value' => $level['id']
            );
            $data->evalFrozen->params = (object)$evalFrozenParams;
        }

        $scoreArgs = array('id' => '{NAME}-criteria-{CRITERION-id}-levels-{LEVEL-id}-score', 'class' => 'scorevalue');
        $data->score->paramSpan = (object)$scoreArgs;

        $definitionclass = 'definition';
        if (isset($level['error_definition'])) {
            $definitionclass .= ' error';
        }

        if ($mode != gradingform_rubric_controller::DISPLAY_EDIT_FULL &&
            $mode != gradingform_rubric_controller::DISPLAY_EDIT_FROZEN) {

            $tdattributes['tabindex'] = '0';
            $levelinfo = new stdClass();
            $levelinfo->definition = s($level['definition']);
            $levelinfo->score = $level['score'];
            $tdattributes['ariaLabel'] = get_string('level', 'gradingform_rubric', $levelinfo);

            if ($mode != gradingform_rubric_controller::DISPLAY_PREVIEW &&
                $mode != gradingform_rubric_controller::DISPLAY_PREVIEW_GRADED) {
                // Add role of radio button to level cell if not in edit and preview mode.
                $tdattributes['role'] = 'radio';
                if ($level['checked']) {
                    $tdattributes['ariaChecked'] = 'true';
                } else {
                    $tdattributes['ariaChecked'] = 'false';
                }
            }
        }

        $leveltemplateparams = array(
            'id' => '{NAME}-criteria-{CRITERION-id}-levels-{LEVEL-id}-definition-container'
        );

        $data->definition->class = $definitionclass;
        $data->definition->leveltemplateparams = (object)$leveltemplateparams;

        $displayscore = true;

        if (!$options['showscoreteacher'] && in_array($mode, array(gradingform_rubric_controller::DISPLAY_EVAL, gradingform_rubric_controller::DISPLAY_EVAL_FROZEN, gradingform_rubric_controller::DISPLAY_REVIEW))) {
            $displayscore = false;
        }
        if (!$options['showscorestudent'] && in_array($mode, array(gradingform_rubric_controller::DISPLAY_VIEW, gradingform_rubric_controller::DISPLAY_PREVIEW_GRADED))) {
            $displayscore = false;
        }

        $data->displayScore = $displayscore;
        if ($displayscore) {
            $scoreclass = 'score';
            if (isset($level['error_score'])) {
                $scoreclass .= ' error';
            }
            $data->displayScoreData->value = get_string('scorepostfix', 'gradingform_rubric', $level['score']);
            $data->displayScoreData->class = $scoreclass;
        }
        if ($mode == gradingform_rubric_controller::DISPLAY_EDIT_FULL) {
            $data->DISPLAY_EDIT_FULL = true;
            $value = get_string('leveldelete', 'gradingform_rubric', $levelindex);
            $buttonparams = array(
                'type' => 'submit',
                'name' => '{NAME}[criteria][{CRITERION-id}][levels][{LEVEL-id}][delete]',
                'id' => '{NAME}-criteria-{CRITERION-id}-levels-{LEVEL-id}-delete',
                'value' => $value
            );
            $data->deleteButton = (object)$buttonparams;
        }

        $data->tdAttributes = (object)$tdattributes;

        try {
            $html = parent::render_from_template('gradingform_rubric/level_template', $data);
        } catch (Exception $exception) {
            $html = '';
        }

        $html = str_replace('{NAME}', $elementname, $html);
        $html = str_replace('{CRITERION-id}', $criterionid, $html);
        $html = str_replace('{LEVEL-id}', $level['id'], $html);
        return $html;
    }

    /**
     * This function returns html code for displaying rubric template (content before and after
     * criteria list). Depending on $mode it may be the code to edit rubric, to preview the rubric,
     * to evaluate somebody or to review the evaluation.
     *
     * This function is called from display_rubric() to display the whole rubric.
     *
     * When overriding this function it is very important to remember that all elements of html
     * form (in edit or evaluate mode) must have the name $elementname.
     *
     * Also JavaScript relies on the class names of elements and when developer changes them
     * script might stop working.
     *
     * @param int $mode rubric display mode see {@link gradingform_rubric_controller}
     * @param array $options display options for this rubric, defaults are: {@link gradingform_rubric_controller::get_default_options()}
     * @param string $elementname the name of the form element (in editor mode) or the prefix for div ids (in view mode)
     * @param string $criteriastr evaluated templates for this rubric's criteria
     * @return string
     * @throws coding_exception
     */
    protected function rubric_template($mode, $options, $elementname, $criteriastr) {
        $data = new stdClass();
        $classsuffix = ''; // CSS suffix for class of the main div. Depends on the mode
        switch ($mode) {
            case gradingform_rubric_controller::DISPLAY_EDIT_FULL:
                $classsuffix = ' editor editable'; break;
            case gradingform_rubric_controller::DISPLAY_EDIT_FROZEN:
                $classsuffix = ' editor frozen';  break;
            case gradingform_rubric_controller::DISPLAY_PREVIEW:
            case gradingform_rubric_controller::DISPLAY_PREVIEW_GRADED:
                $classsuffix = ' editor preview';  break;
            case gradingform_rubric_controller::DISPLAY_EVAL:
                $classsuffix = ' evaluate editable'; break;
            case gradingform_rubric_controller::DISPLAY_EVAL_FROZEN:
                $classsuffix = ' evaluate frozen';  break;
            case gradingform_rubric_controller::DISPLAY_REVIEW:
                $classsuffix = ' review';  break;
            case gradingform_rubric_controller::DISPLAY_VIEW:
                $classsuffix = ' view';  break;
        }

        $data->wrap_id = 'rubric-{NAME}';
        $data->wrap_class = 'clearfix gradingform_rubric' . $classsuffix;

        $data->tableparams->class = 'criteria';
        $data->tableparams->id = '{NAME}-criteria';
        $data->tableparams->ariaLabel = get_string('rubric', 'gradingform_rubric');
        $data->criteriastr = $criteriastr;

        if ($mode == gradingform_rubric_controller::DISPLAY_EDIT_FULL) {
            $data->DISPLAY_EDIT_FULL = true;
            $value = get_string('addcriterion', 'gradingform_rubric');
            $data->inputparams->name = '{NAME}[criteria][addcriterion]';
            $data->inputparams->id = '{NAME}-criteria-addcriterion';
            $data->inputparams->value = $value;
        }
        $data->edit_options = $this->rubric_edit_options($mode, $options);

        try {
            $html = parent::render_from_template('gradingform_rubric/rubric_template', $data);
        } catch (Exception $exception) {
            $html = '';
        }

        return str_replace('{NAME}', $elementname, $html);
    }

    /**
     * Generates html template to view/edit the rubric options. Expression {NAME} is used in
     * template for the form element name
     *
     * @param int $mode rubric display mode see {@link gradingform_rubric_controller}
     * @param array $options display options for this rubric, defaults are: {@link gradingform_rubric_controller::get_default_options()}
     * @return string
     * @throws coding_exception
     */
    protected function rubric_edit_options($mode, $options) {
        if ($mode != gradingform_rubric_controller::DISPLAY_EDIT_FULL
                && $mode != gradingform_rubric_controller::DISPLAY_EDIT_FROZEN
                && $mode != gradingform_rubric_controller::DISPLAY_PREVIEW) {
            // Options are displayed only for people who can manage
            return;
        }

        $data = new stdClass();

        $data->optionsheading->title = get_string('rubricoptions', 'gradingform_rubric');
        $data->optionsheading->class = 'optionsheading';
        $data->optionsset->name = '{NAME}[options][optionsset]';
        $data->optionsset->value = 1;


        foreach ($options as $option => $value) {
            $optionObj = new stdClass();
            $optionObj->class = 'option '. $option;
            $optionObj->attrs->name = '{NAME}[options][' . $option . ']';
            $optionObj->attrs->type = 'hidden';
            $optionObj->attrs->id = '{NAME}-options-'.$option;

            switch ($option) {
                case 'sortlevelsasc':
                    // Display option as dropdown
                    $optionObj->is_sortlevelsasc = true;
                    $optionObj->sortlevelsasc->label->text = get_string($option, 'gradingform_rubric');
                    $optionObj->sortlevelsasc->label->for = $optionObj->attrs->id;

                    $value = (int)(!!$value);
                    if ($mode == gradingform_rubric_controller::DISPLAY_EDIT_FULL) {
                        $optionObj->sortlevelsasc->DISPLAY_EDIT_FULL = true;
                        $optionObj->sortlevelsasc->select->id = $optionObj->attrs->id;
                        $optionObj->sortlevelsasc->select->name = $optionObj->attrs->name;
                        $optionObj->sortlevelsasc->select->class = 'select custom-select menurubricoptionssortlevelsasc';
                        for ($i = 0; $i <= 1; $i++) {
                            $temoObj = new stdClass();
                            $temoObj->selected = ($value == $i);
                            $temoObj->value = $i;
                            $temoObj->dispaly = get_string($option . $i, 'gradingform_rubric');
                            $optionObj->sortlevelsasc->select->options[] = $temoObj;
                        }
                    } else {
                        $optionObj->sortlevelsasc->DISPLAY_EDIT_FULL = false;
                        $optionObj->sortlevelsasc->spanText = get_string($option.$value, 'gradingform_rubric');
                        if ($mode == gradingform_rubric_controller::DISPLAY_EDIT_FROZEN) {
                            $optionObj->sortlevelsasc->DISPLAY_EDIT_FROZEN = true;
                            $optionObj->sortlevelsasc->hiddenField = $optionObj->attrs;
                            $optionObj->sortlevelsasc->hiddenField->value = $value;
                        }
                    }
                    break;
                default:
                    $optionObj->default_option->enabled = true;
                    $optionObj->default_option->attrs = $optionObj->attrs;
                    if ($mode == gradingform_rubric_controller::DISPLAY_EDIT_FROZEN && $value) {
                        $optionObj->default_option->DISPLAY_EDIT_FROZEN = true;
                        // Id should be different then the actual input added later.
                        $optionObj->default_option->attrs->id = '_hidden';
                        $optionObj->default_option->attrs->value = $value;
                    }

                    // Display option as checkbox
                    $optionObj->default_option->attrs->type = 'checkbox';
                    $optionObj->default_option->attrs->value = 1;
                    if ($value) {
                        $optionObj->default_option->attrs->checked = 'checked';
                    }

                    if ($mode == gradingform_rubric_controller::DISPLAY_EDIT_FROZEN || $mode == gradingform_rubric_controller::DISPLAY_PREVIEW) {
                        $optionObj->default_option->attrs->disabled = 'disabled';
                        unset($optionObj->default_option->attrs->name);
                        // Id should be different then the actual input added later.
                        $optionObj->default_option->attrs->id .= '_disabled';
                    }
                    $optionObj->default_option->attrs->labelString = get_string($option, 'gradingform_rubric');
                    break;
            }

            if (get_string_manager()->string_exists($option.'_help', 'gradingform_rubric')) {
                $optionObj->iconHtml = $this->help_icon($option, 'gradingform_rubric');
            }

            $data->options[] = $optionObj;
        }

        try {
            $html = parent::render_from_template('gradingform_rubric/rubric_edit_options', $data);
        } catch (Exception $exception) {
            $html = '';
        }

        return $html;
    }

    /**
     * This function returns html code for displaying rubric. Depending on $mode it may be the code
     * to edit rubric, to preview the rubric, to evaluate somebody or to review the evaluation.
     *
     * It is very unlikely that this function needs to be overriden by theme. It does not produce
     * any html code, it just prepares data about rubric design and evaluation, adds the CSS
     * class to elements and calls the functions level_template, criterion_template and
     * rubric_template
     *
     * @param array $criteria data about the rubric design
     * @param array $options display options for this rubric, defaults are: {@link gradingform_rubric_controller::get_default_options()}
     * @param int $mode rubric display mode, see {@link gradingform_rubric_controller}
     * @param string $elementname the name of the form element (in editor mode) or the prefix for div ids (in view mode)
     * @param array $values evaluation result
     * @return string
     * @throws coding_exception
     */
    public function display_rubric($criteria, $options, $mode, $elementname = null, $values = null) {
        $criteriastr = '';
        $cnt = 0;
        foreach ($criteria as $id => $criterion) {
            $criterion['class'] = $this->get_css_class_suffix($cnt++, sizeof($criteria) -1);
            $criterion['id'] = $id;
            $levelsstr = '';
            $levelcnt = 0;
            if (isset($values['criteria'][$id])) {
                $criterionvalue = $values['criteria'][$id];
            } else {
                $criterionvalue = null;
            }
            $index = 1;
            foreach ($criterion['levels'] as $levelid => $level) {
                $level['id'] = $levelid;
                $level['class'] = $this->get_css_class_suffix($levelcnt++, sizeof($criterion['levels']) -1);
                $level['checked'] = (isset($criterionvalue['levelid']) && ((int)$criterionvalue['levelid'] === $levelid));
                if ($level['checked'] && ($mode == gradingform_rubric_controller::DISPLAY_EVAL_FROZEN || $mode == gradingform_rubric_controller::DISPLAY_REVIEW || $mode == gradingform_rubric_controller::DISPLAY_VIEW)) {
                    $level['class'] .= ' checked';
                    //in mode DISPLAY_EVAL the class 'checked' will be added by JS if it is enabled. If JS is not enabled, the 'checked' class will only confuse
                }
                if (isset($criterionvalue['savedlevelid']) && ((int)$criterionvalue['savedlevelid'] === $levelid)) {
                    $level['class'] .= ' currentchecked';
                }
                $level['tdwidth'] = 100/count($criterion['levels']);
                $level['index'] = $index;
                $levelsstr .= $this->level_template($mode, $options, $elementname, $id, $level);
                $index++;
            }
            $criteriastr .= $this->criterion_template($mode, $options, $elementname, $criterion, $levelsstr, $criterionvalue);
        }
        return $this->rubric_template($mode, $options, $elementname, $criteriastr);
    }

    /**
     * Help function to return CSS class names for element (first/last/even/odd) with leading space
     *
     * @param int $idx index of this element in the row/column
     * @param int $maxidx maximum index of the element in the row/column
     * @return string
     */
    protected function get_css_class_suffix($idx, $maxidx) {
        $class = '';
        if ($idx == 0) {
            $class .= ' first';
        }
        if ($idx == $maxidx) {
            $class .= ' last';
        }
        if ($idx%2) {
            $class .= ' odd';
        } else {
            $class .= ' even';
        }
        return $class;
    }

    /**
     * Displays for the student the list of instances or default content if no instances found
     *
     * @param array $instances array of objects of type gradingform_rubric_instance
     * @param string $defaultcontent default string that would be displayed without advanced grading
     * @param boolean $cangrade whether current user has capability to grade in this context
     * @return string
     * @throws coding_exception
     */
    public function display_instances($instances, $defaultcontent, $cangrade) {
        $data = new stdClass();
        $html = '';

        if (sizeof($instances)) {
            $data->wrap_class = 'advancedgrade';
            $idx = 0;

            foreach ($instances as $instance) {
                $data->instance = $this->display_instance($instance, $idx++, $cangrade);
            }

            try {
                $html = parent::render_from_template('gradingform_rubric/display_instances', $data);
            } catch (Exception $exception) {
                $html = '';
            }
        }

        return $html . $defaultcontent;
    }

    /**
     * Displays one grading instance
     *
     * @param gradingform_rubric_instance $instance
     * @param int $idx unique number of instance on page
     * @param bool $cangrade whether current user has capability to grade in this context
     * @return string
     * @throws coding_exception
     */
    public function display_instance(gradingform_rubric_instance $instance, $idx, $cangrade) {
        $criteria = $instance->get_controller()->get_definition()->rubric_criteria;
        $options = $instance->get_controller()->get_options();
        $values = $instance->get_rubric_filling();
        if ($cangrade) {
            $mode = gradingform_rubric_controller::DISPLAY_REVIEW;
            $showdescription = $options['showdescriptionteacher'];
        } else {
            $mode = gradingform_rubric_controller::DISPLAY_VIEW;
            $showdescription = $options['showdescriptionstudent'];
        }
        $output = '';
        if ($showdescription) {
            $output .= $this->box($instance->get_controller()->get_formatted_description(), 'gradingform_rubric-description');
        }
        $output .= $this->display_rubric($criteria, $options, $mode, 'rubric'.$idx, $values);
        return $output;
    }

    /**
     * Displays confirmation that students require re-grading
     *
     * @param string $elementname
     * @param int $changelevel
     * @param string $value
     * @return string
     * @throws coding_exception
     */
    public function display_regrade_confirmation($elementname, $changelevel, $value) {
        $data = new stdClass();

        if ($changelevel <= 2) {
            $data->changeLevelLess2 = true;
            $data->label->title = get_string('regrademessage1', 'gradingform_rubric');
            $data->label->for = 'menu' . $elementname . 'regrade';

            $data->select->name =  $elementname.'[regrade]';
            $data->select->value = $value;
            $data->select->options = [];

            for ($i = 0; $i <= 1; $i++) {
                $temoObj = new stdClass();
                $temoObj->selected = ($value == $i);
                $temoObj->value = $i;
                $temoObj->dispaly = get_string('regradeoption'.$i, 'gradingform_rubric');
                $data->select->options[] = $temoObj;
            }
        } else {
            $data->changeLevelLess2 = false;
            $data->regrade->text = get_string('regrademessage5', 'gradingform_rubric');
            $data->regrade->name = $elementname.'[regrade]';
        }

        try {
            $html = parent::render_from_template('gradingform_rubric/display_regrade_confirmation', $data);
        } catch (Exception $exception) {
            $html = '';
        }
        return $html;
    }

    /**
     * Generates and returns HTML code to display information box about how rubric score is converted to the grade
     *
     * @param array $scores
     * @return string
     * @throws coding_exception
     */
    public function display_rubric_mapping_explained($scores) {
        $html = '';
        if (!$scores) {
            return $html;
        }
        if ($scores['minscore'] <> 0) {
            $html .= $this->output->notification(get_string('zerolevelsabsent', 'gradingform_rubric'), 'error');
        }
        $html .= $this->output->notification(get_string('rubricmappingexplained', 'gradingform_rubric', (object)$scores), 'info');
        return $html;
    }
}
