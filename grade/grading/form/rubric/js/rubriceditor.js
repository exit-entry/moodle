M.gradingform_rubriceditor = {'templates': {}, 'eventhandler': null, 'name': null, 'Y': null};

/*
 * This function is called for each rubriceditor on page.
 */
M.gradingform_rubriceditor.init = function(Y, options) {
    M.gradingform_rubriceditor.name = options.name;
    M.gradingform_rubriceditor.Y = Y;
    M.gradingform_rubriceditor.templates[options.name] = {
        'criterion': options.criteriontemplate,
        'level': options.leveltemplate
    };
    M.gradingform_rubriceditor.disablealleditors();
    Y.on('click', M.gradingform_rubriceditor.clickanywhere, 'body', null);
    Y.one('body').on('touchstart', M.gradingform_rubriceditor.clickanywhere);
    Y.one('body').on('touchend', M.gradingform_rubriceditor.clickanywhere);

    M.gradingform_rubriceditor.addhandlers();
};

/*
 * Adds handlers for clicking submit button. This function must be called each time JS adds new elements to html
 */
M.gradingform_rubriceditor.addhandlers = function() {
    const Y = M.gradingform_rubriceditor.Y,
        name = M.gradingform_rubriceditor.name;

    if (M.gradingform_rubriceditor.eventhandler) {
        M.gradingform_rubriceditor.eventhandler.detach();
    }
    M.gradingform_rubriceditor.eventhandler = Y.on(
        'click',
        M.gradingform_rubriceditor.buttonclick, '#rubric-' + name + ' input[type=submit]',
        null
    );
    const saverubricbtn = Y.one('#id_saverubric');
    if (saverubricbtn) {
        saverubricbtn.on('click', M.gradingform_rubriceditor.formsubmit);
    }
    const saverubricdraftbtn = Y.one('#id_saverubricdraft');
    if (saverubricdraftbtn) {
        saverubricdraftbtn.on('click', M.gradingform_rubriceditor.formsubmit);
    }
};

/*
 * Switches all input text elements to non-edit mode
 */
M.gradingform_rubriceditor.disablealleditors = function() {
    const Y = M.gradingform_rubriceditor.Y;
    const name = M.gradingform_rubriceditor.name;
    Y.all('#rubric-' + name + ' .level').each(function(node) {
        M.gradingform_rubriceditor.editmode(node, false);
    });
    Y.all('#rubric-' + name + ' .description').each(function(node) {
        M.gradingform_rubriceditor.editmode(node, false);
    });
};

/*
 * Form submit handler.
 *
 * Check all weights and scores before saving.
 */
M.gradingform_rubriceditor.formsubmit = function(e) {
    M.gradingform_rubriceditor.disablealleditors();
    let name = M.gradingform_rubriceditor.name,
        Y = M.gradingform_rubriceditor.Y,
        weightsum = 0,
        scorezeroexists = false,
        scoretenexists = false,
        scorestartsfromzero = true,
        scoreendswithten = true,
        scorlesszero = false,
        scorgreaterten = false,
        wrongordering = false,
        wronglevelsCount = false,
        levelscount = [];

    Y.all('#rubric-' + name + ' .criterion').each(function(node) {
        // Check weight sum - it should be 100.
        weightsum += parseInt(node.one('.weight .hiddenelement').get('value'));

        // Check scores for each criteria.
        let scores = [];
        node.all('.level .score .hiddenelement').each(function(scorenode) {
            const score = parseFloat(scorenode.get('value'));
            scores.push(score);
            if (score < 0) {
                scorlesszero = true;
            }
            if (score > 10) {
                scorgreaterten = true;
            }
            if (score === 0) {
                scorezeroexists = true;
            }
            if (score === 10) {
                scoretenexists = true;
            }
        });
        levelscount.push(scores.length);
        if (scores[0] !== 0) {
            scorestartsfromzero = false;
        }
        if (scores[scores.length - 1] !== 10) {
            scoreendswithten = false;
        }
        let minscore = scores[0];
        for (let i = 1; i < scores.length; i++) {
            if (scores[i] <= minscore) {
                wrongordering = true;
            }
            minscore = scores[i];
        }
    });

    for (let i = 1; i < levelscount.length; i++) {
        if (levelscount[i] !== levelscount[i - 1]) {
            wronglevelsCount = true;
        }
    }

    // Show information that some rule is incorrect.
    Y.all('.wrong-values').remove();
    const formtitle = Y.one('.fitemtitle'),
        wrongrules = [];

    if (weightsum !== 100) {
        wrongrules.push(M.util.get_string('ruleweightsum100', 'gradingform_rubric'));
    }
    if (scorlesszero) {
        wrongrules.push(M.util.get_string('rulescorecannotbelessthan0', 'gradingform_rubric'));
    }
    if (scorgreaterten) {
        wrongrules.push(M.util.get_string('rulescorecannotbegreaterthan10', 'gradingform_rubric'));
    }
    if (!scorezeroexists) {
        wrongrules.push(M.util.get_string('ruleallcriteriascore0points', 'gradingform_rubric'));
    }
    if (!scoretenexists) {
        wrongrules.push(M.util.get_string('ruleallcriteriascore10points', 'gradingform_rubric'));
    }
    if (wrongordering) {
        wrongrules.push(M.util.get_string('rulescoreascendingordering', 'gradingform_rubric'));
    }
    if (!scorestartsfromzero) {
        wrongrules.push(M.util.get_string('rulescorestartswith0points', 'gradingform_rubric'));
    }
    if (!scoreendswithten) {
        wrongrules.push(M.util.get_string('rulescoreendsswith10points', 'gradingform_rubric'));
    }
    if (wronglevelsCount) {
        wrongrules.push(M.util.get_string('rulesamelevelcount', 'gradingform_rubric'));
    }

    // Stop saving form until all rules will be correct.
    if (wrongrules.length > 0) {
        e.preventDefault();
        M.gradingform_rubriceditor.addwrongrulemessage(formtitle, wrongrules);
        Y.one('#fitem_id_description_editor').scrollIntoView({behavior: "smooth", block: "end"});
        return false;
    }

    return true;
};

/*
 * Add notification that some rule is incorect during form submit.
 */
M.gradingform_rubriceditor.addwrongrulemessage = function(node, messages) {
    for (let i = 0; i < messages.length; i++) {
        node.prepend('<div class="wrong-values alert-danger">' + messages[i] + ' </div>');
    }
};
/*
 * Function invoked on each click on the page. If level and/or criterion description is clicked
 * it switches this element to edit mode. If rubric button is clicked it does nothing so the 'buttonclick'
 * function is invoked
 */
M.gradingform_rubriceditor.clickanywhere = function(e) {
    if (e.type === 'touchstart') {
        return;
    }
    let el = e.target;

    // If clicked on button - disablecurrenteditor, continue
    if (el.get('tagName') === 'INPUT' && el.get('type') === 'submit') {
        return;
    }
    // Else if clicked on level and this level is not enabled - enable it
    // or if clicked on description and this description is not enabled - enable it
    let focustb = false;
    while (el && !(el.hasClass('level') || el.hasClass('description'))) {
        if (el.hasClass('score')) {
            focustb = true;
        }
        el = el.get('parentNode');
    }
    if (el) {
        if (el.one('textarea').hasClass('hiddenelement')) {
            M.gradingform_rubriceditor.disablealleditors();
            M.gradingform_rubriceditor.editmode(el, true, focustb);
        }
        return;
    }
    // Else disablecurrenteditor
    M.gradingform_rubriceditor.disablealleditors();
};

/*
 * Toggle edit mode for description, level or weight field.
 *
 * ta - textarea element
 * tb - score input
 * tw - weight input
 */
M.gradingform_rubriceditor.editmode = function(el, editmode, focustb) {
    let ta = el.one('textarea');

    if (!editmode && ta.hasClass('hiddenelement')) {
        return;
    }

    if (editmode && !ta.hasClass('hiddenelement')) {
        return;
    }

    let pseudotablink = '<input type="text" size="1" class="pseudotablink"/>',
        taplain = ta.get('parentNode').one('.plainvalue'),
        tbplain = null,
        twplain = null,
        tb = el.one('.score input[type=text]'),
        tw = el.one('.weight input[type=text]');

    // Add 'plainvalue' next to textarea for description/definition and next to input text field for score (if applicable)
    if (!taplain) {
        ta.get('parentNode').append(
            '<div class="plainvalue">' +
                pseudotablink +
                '<span class="textvalue">&nbsp;</span>' +
            '</div>'
        );

        taplain = ta.get('parentNode').one('.plainvalue');
        taplain.one('.pseudotablink').on('focus', M.gradingform_rubriceditor.clickanywhere);

        if (tb) {
            tb.get('parentNode').append(
                '<span class="plainvalue">' +
                    pseudotablink +
                    '<span class="textvalue">&nbsp;</span>' +
                '</span>'
            );
            tbplain = tb.get('parentNode').one('.plainvalue');
            tbplain.one('.pseudotablink').on('focus', M.gradingform_rubriceditor.clickanywhere);
        }

        if (tw) {
            tw.get('parentNode').append(
                '<span class="plainvalue">' +
                    pseudotablink +
                    '<span class="textvalue">&nbsp;</span>' +
                '</span>'
            );
            twplain = tw.get('parentNode').one('.plainvalue');
            twplain.one('.pseudotablink').on('focus', M.gradingform_rubriceditor.clickanywhere);
        }
    }

    if (tb && !tbplain) {
        tbplain = tb.get('parentNode').one('.plainvalue');
    }

    if (tw && !twplain) {
        twplain = tw.get('parentNode').one('.plainvalue');
    }

    if (!editmode) {
        // If we need to hide the input fields, copy their contents to plainvalue(s). If description/definition
        // is empty, display the default text ('Click to edit ...') and add/remove 'empty' CSS class to element
        let value = ta.get('value');

        if (value.length) {
            taplain.removeClass('empty');
        } else {
            value = (el.hasClass('level'))
                ? M.util.get_string('levelempty', 'gradingform_rubric')
                : M.util.get_string('criterionempty', 'gradingform_rubric');

            taplain.addClass('empty');
        }
        taplain.one('.textvalue').set('innerHTML', Y.Escape.html(value));

        if (tb) {
            tbplain.one('.textvalue').set('innerHTML', Y.Escape.html(tb.get('value')));
        }

        if (tw) {
            twplain.one('.textvalue').set('innerHTML', Y.Escape.html(tw.get('value')));
        }
        // Hide/display textarea, textbox and plaintexts
        taplain.removeClass('hiddenelement');
        ta.addClass('hiddenelement');
        if (tb) {
            tbplain.removeClass('hiddenelement');
            tb.addClass('hiddenelement');
        }
        if (tw) {
            twplain.removeClass('hiddenelement');
            tw.addClass('hiddenelement');
        }
    } else {
        // If we need to show the input fields, set the width/height for textarea so it fills the cell
        try {
            let width = parseFloat(ta.get('parentNode').getComputedStyle('width')),
                height;

            if (el.hasClass('level')) {
                height = parseFloat(el.getComputedStyle('height')) - parseFloat(el.one('.score').getComputedStyle('height'));
            } else {
                height = parseFloat(ta.get('parentNode').getComputedStyle('height'));
            }
            ta.setStyle('width', Math.max(width - 16, 50) + 'px');
            ta.setStyle('height', Math.max(height, 20) + 'px');
        } catch (err) {
            // This browser do not support 'computedStyle', leave the default size of the textbox
        }
        // Hide/display textarea, textbox and plaintexts
        taplain.addClass('hiddenelement');
        ta.removeClass('hiddenelement');
        if (tb) {
            tbplain.addClass('hiddenelement');
            tb.removeClass('hiddenelement');
        }
        if (tw) {
            twplain.addClass('hiddenelement');
            tw.removeClass('hiddenelement');
        }
    }
    // Focus the proper input field in edit mode
    if (editmode) {
        if (!(tb && focustb)) {
            ta.focus();
        } else {
            tb.focus();
        }
    }
    if (editmode) {
        if (!(tw && focustb)) {
            ta.focus();
        } else {
            tw.focus();
        }
    }
};

// Handler for clicking on submit buttons within rubriceditor element. Adds/deletes/rearranges criteria and/or levels on client side
M.gradingform_rubriceditor.buttonclick = function(e, confirmed) {
    let Y = M.gradingform_rubriceditor.Y;
    let name = M.gradingform_rubriceditor.name;
    if (e.target.get('type') !== 'submit') {
        return;
    }
    M.gradingform_rubriceditor.disablealleditors();
    let chunks = e.target.get('id').split('-'),
        action = chunks[chunks.length - 1];

    if (chunks[0] !== name || chunks[1] !== 'criteria') {
        return;
    }
    let elements_str;
    if (chunks.length > 4 || action === 'addlevel') {
        elements_str = '#rubric-' + name + ' #' + name + '-criteria-' + chunks[2] + '-levels .level';
    } else {
        elements_str = '#rubric-' + name + ' .criterion';
    }

    // Prepare the id of the next inserted level or criterion
    let newlevid = 0;
    let newid = 0;
    if (action === 'addcriterion' || action === 'addlevel' || action === 'duplicate') {
        newid = M.gradingform_rubriceditor.calculatenewid('#rubric-' + name + ' .criterion');
        newlevid = M.gradingform_rubriceditor.calculatenewid('#rubric-' + name + ' .level');
    }
    let dialog_options = {
        'scope': this,
        'callbackargs': [e, true],
        'callback': M.gradingform_rubriceditor.buttonclick
    };
    if (chunks.length === 3 && action === 'addcriterion') {
        // ADD NEW CRITERION
        let levelsscores = [0],
            levidx = 1,
            parentel = Y.one('#' + name + '-criteria'),
            levelsstr = '';

        if (parentel.one('>tbody')) {
            parentel = parentel.one('>tbody');
        }
        if (parentel.all('.criterion').size()) {
            let lastcriterion = parentel.all('.criterion').item(parentel.all('.criterion').size() - 1).all('.level');
            for (levidx = 0; levidx < lastcriterion.size(); levidx++) {
                levelsscores[levidx] = lastcriterion.item(levidx).one('.score input[type=text]').get('value');
            }
        }
        for (levidx; levidx < 3; levidx++) {
            levelsscores[levidx] = parseFloat(levelsscores[levidx - 1]) + 1;
        }
        for (levidx = 0; levidx < levelsscores.length; levidx++) {
            levelsstr += M.gradingform_rubriceditor.templates[name].level
                .replace(/\{LEVEL-id\}/g, 'NEWID' + (newlevid + levidx))
                .replace(/\{LEVEL-score\}/g, levelsscores[levidx])
                .replace(/\{LEVEL-index\}/g, levidx + 1);
        }

        let newcriterion = M.gradingform_rubriceditor.templates[name]['criterion'].replace(/\{LEVELS\}/, levelsstr);
        parentel.append(newcriterion.replace(/\{CRITERION-id\}/g, 'NEWID' + newid).replace(/\{.+?\}/g, ''));
        M.gradingform_rubriceditor.assignclasses('#rubric-' + name + ' #' + name + '-criteria-NEWID' + newid + '-levels .level');
        M.gradingform_rubriceditor.addhandlers();
        M.gradingform_rubriceditor.disablealleditors();
        M.gradingform_rubriceditor.assignclasses(elements_str);
        let newweights = [],
            newweightsum = 0,
            criteriacount = parentel.all('.criterion').size();

        for (let i = 1; i <= criteriacount; i++) {
            let intweight = parseInt(100 / criteriacount);
            newweightsum += intweight;
            newweights.push(intweight);
        }
        if (newweightsum !== 100) {
            newweights[0] = newweights[0] + 100 - (criteriacount * newweights[0]);
        }
        Y.all('#' + name + '-criteria .criterion .weight .hiddenelement').each(function(node, index) {
            node.set('value', newweights[index]);
            node.get('parentNode').one('.textvalue').set('innerHTML', newweights[index]);
        });
        // M.gradingform_rubriceditor.editmode(
        //     Y.one('#rubric-' + name + ' #' + name + '-criteria-NEWID' + newid + '-description-cell'),
        //     true
        // );
    } else if (chunks.length === 5 && action === 'addlevel') {
        // ADD NEW LEVEL
        let newscore = 0,
            parent = Y.one('#' + name + '-criteria-' + chunks[2] + '-levels'),
            levelIndex = 1;

        parent.all('.level').each(function (node) {
            newscore = Math.max(newscore, parseFloat(node.one('.score input[type=text]').get('value')) + 1);
            levelIndex++;
        });

        let newlevel = M.gradingform_rubriceditor.templates[name]['level']
            .replace(/\{CRITERION-id\}/g, chunks[2])
            .replace(/\{LEVEL-id\}/g, 'NEWID' + newlevid)
            .replace(/\{LEVEL-score\}/g, newscore)
            .replace(/\{LEVEL-index\}/g, levelIndex)
            .replace(/\{.+?\}/g, '');

        parent.append(newlevel);
        M.gradingform_rubriceditor.addhandlers();
        M.gradingform_rubriceditor.disablealleditors();
        M.gradingform_rubriceditor.assignclasses(elements_str);
        M.gradingform_rubriceditor.editmode(parent.all('.level').item(parent.all('.level').size() - 1), true);
    } else if (chunks.length === 4 && action === 'moveup') {
        // MOVE CRITERION UP
        let el = Y.one('#' + name + '-criteria-' + chunks[2]);
        if (el.previous()) {
            el.get('parentNode').insertBefore(el, el.previous());
        }
        M.gradingform_rubriceditor.assignclasses(elements_str);
    } else if (chunks.length === 4 && action === 'movedown') {
        // MOVE CRITERION DOWN
        let el = Y.one('#' + name + '-criteria-' + chunks[2]);
        if (el.next()) {
            el.get('parentNode').insertBefore(el.next(), el);
        }
        M.gradingform_rubriceditor.assignclasses(elements_str);
    } else if (chunks.length == 4 && action == 'delete') {
        // DELETE CRITERION
        if (confirmed) {
            Y.one('#' + name + '-criteria-' + chunks[2]).remove();
            M.gradingform_rubriceditor.assignclasses(elements_str);
        } else {
            dialog_options['message'] = M.util.get_string('confirmdeletecriterion', 'gradingform_rubric');
            M.util.show_confirm_dialog(e, dialog_options);
        }
    } else if (chunks.length === 4 && action === 'duplicate') {
        // Duplicate criterion.
        let levelsdef = [],
            levelsscores = [0],
            levidx = null,
            parentel = Y.one('#' + name + '-criteria');

        if (parentel.one('>tbody')) {
            parentel = parentel.one('>tbody');
        }

        let source = Y.one('#' + name + '-criteria-' + chunks[2]);
        if (source.all('.level')) {
            let lastcriterion = source.all('.level');
            for (levidx = 0; levidx < lastcriterion.size(); levidx++) {
                levelsdef[levidx] = lastcriterion.item(levidx).one('.definition .textvalue').get('innerHTML');
            }
            for (levidx = 0; levidx < lastcriterion.size(); levidx++) {
                levelsscores[levidx] = lastcriterion.item(levidx).one('.score input[type=text]').get('value');
            }
        }

        for (levidx; levidx < 3; levidx++) {
            levelsscores[levidx] = parseFloat(levelsscores[levidx - 1]) + 1;
        }
        let levelsstr = '';
        for (levidx = 0; levidx < levelsscores.length; levidx++) {
            levelsstr += M.gradingform_rubriceditor.templates[name].level
                .replace(/\{LEVEL-id\}/g, 'NEWID' + (newlevid + levidx))
                .replace(/\{LEVEL-score\}/g, levelsscores[levidx])
                .replace(/\{LEVEL-definition\}/g, levelsdef[levidx]);
        }
        let description = source.one('.description .textvalue');
        let newcriterion = M.gradingform_rubriceditor.templates[name].criterion
            .replace(/\{LEVELS\}/, levelsstr)
            .replace(/\{CRITERION-description\}/, description.get('innerHTML'));

        parentel.append(newcriterion.replace(/\{CRITERION-id\}/g, 'NEWID' + newid).replace(/\{.+?\}/g, ''));
        M.gradingform_rubriceditor.assignclasses('#rubric-' + name + ' #' + name + '-criteria-NEWID' + newid + '-levels .level');
        M.gradingform_rubriceditor.addhandlers();
        M.gradingform_rubriceditor.disablealleditors();
        M.gradingform_rubriceditor.assignclasses(elements_str);
        // M.gradingform_rubriceditor.editmode(
            // Y.one('#rubric-' + name + ' #' + name + '-criteria-NEWID' + newid + '-description-cell'),
            // true
        // );
    } else if (chunks.length === 6 && action === 'delete') {
        // DELETE LEVEL
        if (confirmed) {
            Y.one('#' + name + '-criteria-' + chunks[2] + '-' + chunks[3] + '-' + chunks[4]).remove();
            M.gradingform_rubriceditor.assignclasses(elements_str);
        } else {
            dialog_options['message'] = M.util.get_string('confirmdeletelevel', 'gradingform_rubric');
            M.util.show_confirm_dialog(e, dialog_options);
        }
    } else {
        // Unknown action
        return;
    }

    e.preventDefault();
};

/*
 * Properly set classes (first/last/odd/even), level width and/or criterion sortorder for elements Y.all(elements_str)
 */
M.gradingform_rubriceditor.assignclasses = function(elements_str) {
    let elements = M.gradingform_rubriceditor.Y.all(elements_str);
    for (let i = 0; i < elements.size(); i++) {
        elements.item(i)
            .removeClass('first')
            .removeClass('last')
            .removeClass('even')
            .removeClass('odd')
            .addClass(((i % 2) ? 'odd' : 'even') + ((i === 0) ? ' first' : '') + ((i === elements.size() - 1) ? ' last' : ''));
        elements.item(i).all('input[type=hidden]').each(function(node) {
            if (node.get('name').match(/sortorder/)) {
                node.set('value', i);
            }
        });
        if (elements.item(i).hasClass('level')) {
            elements.item(i).set('width', Math.round(100 / elements.size()) + '%');
        }
    }
};

/*
 * Returns unique id for the next added element, it should not be equal to any of Y.all(elements_str) ids
 */
M.gradingform_rubriceditor.calculatenewid = function(elements_str) {
    let newid = 1;
    M.gradingform_rubriceditor.Y.all(elements_str).each(function(node) {
        let idchunks = node.get('id').split('-'),
            id = idchunks.pop();

        if (id.match(/^NEWID(\d+)$/)) {
            newid = Math.max(newid, parseInt(id.substring(5)) + 1);
        }
    });

    return newid;
};
