# jquery-jeditable

[![npm](https://img.shields.io/npm/v/jquery-jeditable.svg)](https://www.npmjs.com/package/jquery-jeditable)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/0cb32ce695b743d68257021455330c66)](https://www.codacy.com/app/elabftw/jquery_jeditable)
[![GitHub license](https://img.shields.io/github/license/NicolasCARPi/jquery_jeditable.svg)](https://github.com/NicolasCARPi/jquery_jeditable/blob/master/LICENSE)

# DEPRECATION NOTICE

**This project is deprecated** and should not be used in a fresh project. Have a look at [Malle](https://github.com/deltablot/malle/) library instead. It is more or less the same, but in typescript and without a jQuery dependency.

## Alternative library

Use [Malle](https://github.com/deltablot/malle/).


## Description

Edit in place plugin for [jQuery](https://jquery.com/) (compatible with jQuery v3.4.0+).

Bind it to an element on the page, and when clicked the element will become a form that will be sent by asynchronous (ajax) POST request to an URL.

[![demo](https://i.imgur.com/lEmY8l0.gif)](https://jeditable.elabftw.net)

Works with text inputs, textarea, select, datepicker, and more… Comes with a ton of different options you can use to do exactly what you want!

## Live demo

See it in action: [LIVE DEMO](https://jeditable.elabftw.net/)

## Installation

~~~bash
npm install jquery-jeditable
~~~

## Usage

### Loading the library

Use a `<script>` tag loading the file `dist/jquery.jeditable.min.js` from your server, or use the [CDNJS link](https://cdnjs.com/libraries/jeditable.js).

### Most basic usage

There is only one mandatory parameter. URL where browser sends edited content.

~~~javascript
$(document).ready(function() {
     $('.edit').editable('save.php');
 });
~~~

Code above does several things:

Elements with class `edit` become editable. Editing starts with single mouse click. Form input element is text. Width and height of input element matches the original element. If user clicks outside form, changes are discarded. Same thing happens if user hits ESC. When user hits ENTER, browser submits text to save.php.

Not bad for oneliner, huh? Let's add some options.

### Adding options

~~~javascript
$(document).ready(function() {
    $('.edit').editable('save.php', {
        indicator : 'Saving…',
        event     : 'dbclick',
        cssclass  : 'custom-css',
        submit    : 'Save',
        tooltip   : 'Double click to edit…'
    });

    $('.edit_area').editable('save.php', {
        type      : 'textarea',
        cancel    : 'Cancel',
        submit    : 'OK',
        indicator : '<img src="img/spinner.svg" />',
        tooltip   : 'Click to edit…'
    });

    $('.edit_select').editable('save.php', {
        type           : 'select',
        indicator      : 'Saving ...',
        loadurl        : 'api/load.php',
        inputcssclass  : 'form-control',
        cssclass       : 'form'
    });
});
~~~

In the code above, the elements of class `edit` will become editable with a double click. It will show 'Saving…' when sending the data. The CSS class `custom-css` will be applied to the element. The button to submit will show the 'Save' text.

The elements of class `edit_area` will become editable with a textarea. An image will be displayed during the save.

The elements of class `edit_select` will become a selectable drop-down list.

Both elements will have a tooltip showing when mouse is hovering.

The [live demo](https://jeditable.elabftw.net) shows more example but with that you can already do plenty!

The complete list of options is available here: [FULL LIST OF OPTIONS](https://jeditable.elabftw.net/api/#jquery-jeditable)

### What is sent to the server?

When the user clicks the submit button a POST request is sent to the target URL like this:

~~~javascript
id=element_id&value=user_edited_content
~~~

Where `element_id` is the id of the element and `user_edited_content` is what the user entered in the input.

If you'd like to change the names of the parameters sent, you need to add two options:

~~~javascript
$('.edit').editable('save.php', {
    id   : 'bookId',
    name : 'bookTitle'
});
~~~

And the code above will result in this being sent to the server:

~~~javascript
bookId=element_id&bookTitle=user_edited_content
~~~

### Loading data

If you need to load a different content than the one displayed (element is from a Wiki or is in Markdown or Textile and you need to load the source), you can do so with the `loadurl` option.

~~~javascript
$('.edit_area').editable('save.php', {
    loadurl  : 'load.php',
    loadtype : 'POST',
    loadtext : 'Loading…',
    type    : 'textarea',
    submit  : 'OK'
});
~~~

By default it will do a GET request to `loadurl`, so if you want POST, add the `loadtype` option. And `loadtext` is to display something while the request is being made.

The PHP script `load.php` should return the source of the text (so markdown or wiki text but not html).

And save.php should return the html (because this is what is displayed to the user after submit).

Or you can pass the source in the `data` option (which accepts a string or a function).

I like writing sentences (and finishing them with text in parenthesis).

### Using selects

You can use selects by giving type parameter value of select. Select is built from JSON encoded array. This array can be given using either `data` parameter or fetched from external URL given in `loadurl` parameter. Array keys are values for `<option>` tag. Array values are text shown in pulldown.

JSON encoded array looks like this:

~~~json
{"E":"Letter E","F":"Letter F","G":"Letter G", "selected":"F"}
~~~

Note the last entry. It is special. With value of `selected` in array you can tell Jeditable which option should be selected by default. Lets make two simple examples. First we pass values for pulldown in data parameter:

~~~javascript
$('.editable-select').editable('save.php', {
    data   : '{"E":"Letter E","F":"Letter F","G":"Letter G", "selected":"F"}',
    type   : 'select',
    submit : 'OK'
});
~~~

What if you need to generate values for pulldown dynamically? Then you can fetch values from external URL. Let's assume we have the following PHP script:

~~~php
 <?php // json.php
 $array['E'] =  'Letter E';
 $array['F'] =  'Letter F';
 $array['G'] =  'Letter G';
 $array['selected'] =  'F';
 echo json_encode($array);
~~~

Then instead of `data` parameter we use `loadurl`:

~~~javascript
$('.editable-select').editable('save.php', {
    loadurl : 'json.php',
    type   : 'select',
    submit : 'OK'
});
~~~

### Styling elements

You can style input element with `cssclass` and `style` parameters. First one assumes to be the name of a class defined in your CSS. Second one can be any valid style declaration as string. Check the following examples:

~~~javascript
$('.editable').editable('save.php', {
    cssclass : 'someclass'
});

$('.editable').editable('save.php', {
    loadurl : 'json.php',
    type    : 'select',
    submit  : 'OK',
    style   : 'display: inline'
});
~~~


Both parameters can have special value of `inherit`. Setting class to `inherit` will make the form inherit the parent's class. Setting `style` to `inherit` will make form to have same style attribute as its parent.

Following example will make the word **oranges** editable with a pulldown menu. This pulldown inherits style from `<span>`. Thus it will be displayed inline.

~~~html
I love eating <span class="editable" style="display: inline">oranges</span>.
~~~

~~~javascript
$('.editable').editable('save.php', {
    loadurl : 'json.php',
    type    : 'select',
    submit  : 'OK',
    style   : 'inherit'
});
~~~

### Submitting to function instead of URL

Some people want to control absolutely everything. I want to keep you happy. You can get full control of Ajax request. Just submit to function instead of URL. Parameters passed are same as with callback.

~~~javascript
$('.editable').editable(function(value, settings) {
    console.log(this);
    console.log(value);
    console.log(settings);
    return(value);
}, {
    type    : 'textarea',
    submit  : 'OK',
});
~~~

Note that the function must return a string. Usually the edited content. This will be displayed on the page after editing.

### Other options

The demo contains other examples, [have a look!](https://jeditable.elabftw.net)

The complete list of options is available here: [FULL LIST OF OPTIONS](https://jeditable.elabftw.net/api/#jquery-jeditable)

## Support

Please open a [GitHub issue](https://github.com/NicolasCARPi/jquery_jeditable/issues/new) if you have a bug to report, a question to ask or if you just want to discuss something related to the project.
