var targets = ["current", "voltage", "cputemp", "hddtemp", "pmutemp", "cpu0freq",
  "cpu1freq","txbytes","rxbytes","Free RAM","Uptime","Users logged on","Load"];

var graphInformation = {}; // caches graph info before drawing
var charts = {}; // holds references to drawn charts

// define global options for all history-graphs
var graphOptions = {
  // avoid partial cutoff of leading digits of y-axis
  scaleLabel : function(object) {return " " + object.value;},

  animation: false, // disabling animation to improve performance and be more consistent with show/hide

  scaleShowGridLines : true, //Boolean - Whether grid lines are shown across the chart

  scaleGridLineColor : 'rgba(0,0,0,.05)', //String - Colour of the grid lines

  scaleGridLineWidth : 1, //Number - Width of the grid lines

  scaleBeginAtZero: true, // Set the start value

  scaleShowHorizontalLines: true, //Boolean - Whether to show horizontal lines (except X axis)

  scaleShowVerticalLines: true, //Boolean - Whether to show vertical lines (except Y axis)

  bezierCurve : false, //Boolean - Whether the line is curved between points

  bezierCurveTension : 0.4, //Number - Tension of the bezier curve between points

  pointDot : true, //Boolean - Whether to show a dot for each point

  pointDotRadius : 4,

  pointDotStrokeWidth : 1, //Number - Pixel width of point dot stroke

  //Number - amount extra to add to the radius to cater for hit detection outside the drawn point
  pointHitDetectionRadius : 5,

  datasetStroke : true, //Boolean - Whether to show a stroke for datasets

  datasetStrokeWidth : 2, //Number - Pixel width of dataset stroke

  datasetFill : true, //Boolean - Whether to fill the dataset with a colour

  tooltipTemplate: "<%if (label){%><%=label%> => <%}%><%= value %>"
};


$(document).ready(function() {
  $( "#sortable" ).sortable();
  $( "#sortable" ).disableSelection();
  console.log("setup sortable");
  //alert("hello world");
  /*var showText="Show";
  var hideText="Hide";
  $(".toggle").prev().append(' (<a href="#" class="toggleLink">'+showText+'</a>)');
  $('.toggle').hide();
  $('a.toggleLink').click(function() {
    $(this).parent().next('.toggle').toggle('slow');
    if ($(this).html()==showText) {
      $(this).html(hideText); // change text of link from 'Show' to 'Hide'

      // draw chart in canvas (once)
      $(this).parent().next('.toggle').children('canvas').each(function(index,value){
        if(graphInformation[value.id] !== undefined){
          // show item for creation
          $('#'+value.id).show();

          // draw graph in/on pre-existing canvas
          var context = document.getElementById(value.id).getContext('2d');
          charts[value.id] = new Chart(context).Line(graphInformation[value.id],graphOptions);

          // get rid of cached information => chart is created only once
          delete graphInformation[value.id];

          // hide graph if necessary
          var attributeClass = $('#'+value.id).attr('class');
          if(!$('#'+attributeClass+'Checkbox').is(':checked')){
            $('#'+value.id).hide();
          }
        }
      });
    }
    else {
      $(this).html(showText); // change text of link from 'Hide' to 'Show'
    }
    return false;
  });

  // set up eventhandlers for attribute-checkboxes
  /*var attributes = ['voltageAtr','currentAtr','cputempAtr','pmutempAtr','hddtempAtr'];
  attributes.forEach(function(attribute) {
    $('#'+attribute+'Checkbox').change(function(checkbox){
      if(checkbox.currentTarget.checked){
        $('.'+checkbox.currentTarget.value).show();
      }else {
        $('.'+checkbox.currentTarget.value).hide();
      }
    });
  });*/
});
