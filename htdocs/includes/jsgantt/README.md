[![Build Status](https://travis-ci.com/jsGanttImproved/jsgantt-improved.svg?branch=master)](https://travis-ci.com/jsGanttImproved/jsgantt-improved)


A fully featured gantt chart component built entirely in Javascript, CSS and AJAX. It is lightweight and there is no need of external libraries or additional images. 


![Demo Image](/docs/demo.gif)


Start using with including the files `jsgantt.js` and `jsgantt.css` that are inside `docs/` folder.

Or install and use in JS 

`npm install jsgantt-improved`

Import in your JS `import {JSGantt} from 'jsgantt-improved';`

See the [FULL DOCUMENTATION](./Documentation.md) for more details in all features.

For **Angular** use the component [ng-gantt](https://github.com/jsGanttImproved/ng-gantt) 

For **React** use the component [react-jsgantt](https://github.com/jsGanttImproved/react-jsgantt) 


For **Vue** , see this example: https://stackblitz.com/edit/vue-jsgantt


For **.NET** , see this example: [.NET Documentation](./docs/DotNet.md)


## Example


You can view a Solo live example at:

* https://jsganttimproved.github.io/jsgantt-improved/docs/demo.html

Or use a live coding example at Codenpen:

* https://codepen.io/mariomol/pen/mQzBPV


## Easy to Use

```html
<link href="jsgantt.css" rel="stylesheet" type="text/css"/>
<script src="jsgantt.js" type="text/javascript"></script>

<div style="position:relative" class="gantt" id="GanttChartDIV"></div>

<script>

var g = new JSGantt.GanttChart(document.getElementById('GanttChartDIV'), 'day');

g.setOptions({
  vCaptionType: 'Complete',  // Set to Show Caption : None,Caption,Resource,Duration,Complete,     
  vQuarterColWidth: 36,
  vDateTaskDisplayFormat: 'day dd month yyyy', // Shown in tool tip box
  vDayMajorDateDisplayFormat: 'mon yyyy - Week ww',// Set format to dates in the "Major" header of the "Day" view
  vWeekMinorDateDisplayFormat: 'dd mon', // Set format to display dates in the "Minor" header of the "Week" view
  vLang: 'en',
  vShowTaskInfoLink: 1, // Show link in tool tip (0/1)
  vShowEndWeekDate: 0,  // Show/Hide the date for the last day of the week in header for daily
  vAdditionalHeaders: { // Add data columns to your table
      category: {
        title: 'Category'
      },
      sector: {
        title: 'Sector'
      }
    },
  vUseSingleCell: 10000, // Set the threshold cell per table row (Helps performance for large data.
  vFormatArr: ['Day', 'Week', 'Month', 'Quarter'], // Even with setUseSingleCell using Hour format on such a large chart can cause issues in some browsers,
  
});

// Load from a Json url
JSGantt.parseJSON('./fixes/data.json', g);

// Or Adding  Manually
g.AddTaskItemObject({
  pID: 1,
  pName: "Define Chart <strong>API</strong>",
  pStart: "2017-02-25",
  pEnd: "2017-03-17",
  pPlanStart: "2017-04-01",
  pPlanEnd: "2017-04-15 12:00",
  pClass: "ggroupblack",
  pLink: "",
  pMile: 0,
  pRes: "Brian",
  pComp: 0,
  pGroup: 0,
  pParent: 0,
  pOpen: 1,
  pDepend: "",
  pCaption: "",
  pCost: 1000,
  pNotes: "Some Notes text",
  category: "My Category",
  sector: "Finance"
});

g.Draw();

</script>
```

## Features

  * Tasks & Collapsible Task Groups
  * Dependencies and Highlight when hover a task
  * Edit data in gantt table with list of responsible
  * Task Completion
  * Table with Additional Columns
  * Task Styling or as HTML tags
  * Milestones
  * Resources
  * Costs
  * Plan Start and End Dates
  * Gantt with Planned vs Executed
  * Dynamic Loading of Tasks
  * Dynamic change of format: Hour, Day, Week, Month, Quarter
  * Load Gantt from JSON and XML
    * From external files (including experimental support for MS Project XML files)
    * From JavaScript Strings
  * Support for Internationalization 

## Documentation

See the [Documentation](./Documentation.md) wiki page or the included ``docs/index.html`` file for instructions on use.

Project based on https://code.google.com/p/jsgantt/.


## Want to Collaborate?

Its easy to get it set:

* Clone this repo
* Install lib dependencies: `npm i` 
* Install global dependencies: `npm i -g browserify nodemon onchange` 
* Run the demo, This will start a `localhost:8080` with a live  example:  `npm start`. 
* Use `npm run watch` or do your change in `src` and restart this command refresh the changes.

For testing use `npm run test` with e2e tests.

Or help us donating...

[![](https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=S7B43P63C5QEN)

