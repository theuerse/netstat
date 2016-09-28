var properties = ["current", "voltage", "cputemp", "hddtemp", "pmutemp", "cpu0freq",
"cpu1freq","txbytes","rxbytes","Free_RAM","Uptime","Users_logged_on","Load"];

var hosts = ["PI0","PI1","PI2","PI3","PI4","PI5","PI6","PI7","PI8","PI9","PI10","PI11",
"PI12","PI13","PI14","PI15","PI16","PI17","PI18","PI19"];

var jsonFiles = {};
var jsonData = {"x":[]};
var charts = {};

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

function setupSortableDivs(){
  $( "#sortable" ).sortable();
  //$( "#sortable" ).disableSelection();
}

function setupPropertySelection(){
  var citynames = new Bloodhound({
    datumTokenizer: Bloodhound.tokenizers.obj.whitespace('name'),
    queryTokenizer: Bloodhound.tokenizers.whitespace,
    prefetch: {
      url: 'propertynames.json',
      filter: function(list) {
        return $.map(list, function(cityname) {
          return { name: cityname }; });
        }
      }
    });
    citynames.initialize();

    $( "#property-selection input[type='text']" ).tagsinput({
      typeaheadjs: {
        name: 'propertynames',
        displayKey: 'name',
        valueKey: 'name',
        source: citynames.ttAdapter()
      }
    });

    $("#property-selection input[type='text']").on('beforeItemAdd', function(event) {
      var propertyName = event.item;
      if(properties.indexOf(propertyName) === -1){
        event.cancel = true; // prevent item from being added, when it is not in the properties-array
      }
    });

    $("#property-selection input[type='text']" ).eq(2).on('itemAdded', function(event) {
      var propertyName = event.item;
      if($("#p_" + propertyName).length){
        $("#p_" + propertyName).show();
        console.log(propertyName + " shown");
      }
      else{
        $("#sortable").append('<li id="p_' + propertyName +'" class="ui-state-default">' +
        '<div><p>' +  propertyName + '</p><div id="chart_' + propertyName +'"></div></div></li>');

        setupSortableDivs();

        $("#chk_" + propertyName).prop('checked', true);
        $("#chk_" + propertyName).button( "refresh" );

        setupChart(propertyName);

        console.log(propertyName + " added");
      }

    });

    $("#property-selection input[type='text']" ).eq(2).on('itemRemoved', function(event) {
      if($("#p_" + event.item).length){
        $("#p_" + event.item).hide();
        $("#chk_" + event.item).prop('checked', false);
        $("#chk_" + event.item).button( "refresh" );
        console.log(event.item + " removed");
      }
    });


    $("#propertyBtn").button().on( "click", function() {
      propertyDialog.dialog( "open" );
    });

  }

  function setupPropertyDialog(){
    properties.forEach(function(property) {
      $("#dialog-properties form fieldset").append('<label for="chk_' + property +'">' + property +'</label>');
      $("#dialog-properties form fieldset").append('<input class="chkbox" id="chk_' + property +'" type="checkbox"' +
      'value="' + property +'">');
    });

    $( "#dialog-properties .chkbox" ).checkboxradio();

    $("#dialog-properties .chkbox").bind('change', function(){
      if($(this).is(':checked')){
        $("#property-selection input[type='text']").eq(2).tagsinput('add', $(this).attr('value'),{preventPost: true});
        console.log("adding tag" + $(this).attr('value'));
      }else{

        $("#property-selection input[type='text']").eq(2).tagsinput('remove', $(this).attr('value'));
      }
    });

    propertyDialog = $( "#dialog-properties" ).dialog({
      autoOpen: false,
      resizable: true,
      modal: false,
      height: 'auto',
      width:'500px'
    });
  }

  function setupHostSelection(){
    var hostnames = new Bloodhound({
      datumTokenizer: Bloodhound.tokenizers.obj.whitespace('name'),
      queryTokenizer: Bloodhound.tokenizers.whitespace,
      prefetch: {
        url: 'hostnames.json',
        filter: function(list) {
          return $.map(list, function(hostname) {
            return { name: hostname }; });
          }
        }
      });
      hostnames.initialize();

      $( "#host-selection input[type='text']" ).tagsinput({
        typeaheadjs: {
          name: 'hostnames',
          displayKey: 'name',
          valueKey: 'name',
          source: hostnames.ttAdapter()
        }
      });

      $("#host-selection input[type='text']").on('beforeItemAdd', function(event) {
        if(hosts.indexOf(event.item) === -1){
          event.cancel = true; // prevent item from being added, when it is not in the hosts-array
        }
      });

      $("#host-selection input[type='text']" ).eq(2).on('itemAdded', function(event) {
        var hostname = event.item;
        $("#chk_" + hostname).prop('checked', true);
        $("#chk_" + hostname).button( "refresh" );
        if(! jsonFiles.hasOwnProperty(hostname)){
          $.getJSON("history/" + hostname + ".json").done(function(json){
            jsonFiles[hostname] = json;
            integrateJsonData(hostname, json);

            // update existing charts (wait for data)
            for(var propertyName in charts) {
              charts[propertyName].load({columns:[[hostname].concat(jsonData[hostname][propertyName])]});
            }
          })
          .fail(function( jqxhr, textStatus, error ) {
            var err = textStatus + ", " + error;
            console.log( "Request Failed: " + err );
          });
        }else{
          // immediately update existing charts (data is there)
          for(var propertyName in charts) {
              charts[propertyName].show([hostname],{withLegend: true});
          }
        }
        console.log(hostname + " added");
      });

      $("#host-selection input[type='text']" ).eq(2).on('itemRemoved', function(event) {
        var hostname = event.item;
        $("#chk_" + hostname).prop('checked', false);
        $("#chk_" + hostname).button( "refresh" );

        // update existing charts
        for(var propertyName in charts) {
          //charts[propertyName].unload({ids: [hostname]});
            charts[propertyName].hide([hostname],{withLegend: true});
        }
        console.log(hostname + " removed");
      });


      $("#hostBtn").button().on( "click", function() {
        hostDialog.dialog( "open" );
      });

    }

    function setupHostDialog(){
      hosts.forEach(function(host) {
        $("#dialog-host form fieldset").append('<label for="chk_' + host +'">' + host +'</label>');
        $("#dialog-host form fieldset").append('<input class="chkbox" id="chk_' + host +'" type="checkbox"' +
        'value="' + host +'">');
      });

      $( "#dialog-host .chkbox" ).checkboxradio();

      $("#dialog-host .chkbox").bind('change', function(){
        if($(this).is(':checked')){
          $("#host-selection input[type='text']").eq(2).tagsinput('add', $(this).attr('value'),{preventPost: true});
          console.log("adding tag" + $(this).attr('value'));
        }else{

          $("#host-selection input[type='text']").eq(2).tagsinput('remove', $(this).attr('value'));
        }
      });

      hostDialog = $( "#dialog-host" ).dialog({
        autoOpen: false,
        resizable: true,
        modal: false,
        height: 'auto',
        width:'500px'
      });
    }

    function setupChart(propertyName){
      console.log("updating chart");
      columns = [['x'].concat(jsonData.x)];

      $("#host-selection input[type='text']").eq(2).tagsinput('items').forEach(function(hostname){
        columns.push([hostname].concat(jsonData[hostname][propertyName]));
      });

      charts[propertyName] = c3.generate({
        bindto: '#chart_' + propertyName,
        data: {
          x: 'x',
          xFormat: '%a %b %d %H:%M:%S %Y',
          columns: columns
        },
        axis: {
          x: {
            type: 'timeseries',
            tick: {
              format: '%d.%m.%Y %H:%M:%S',
              values: [jsonData.x[0], jsonData.x[jsonData.x.length-1]]
            }
          }
        },
        grid: {
          x: {
            show: false
          },
          y: {
            show: true
          }
        }
      });
    }

    function integrateJsonData(hostname, json){
      if(Object.keys(jsonData).length == 1){
        // adding first PI
        json.history.forEach(function(histEntry){
          //TODO: reliably filter out zone, or find appropriate format-string!
          jsonData.x.push(histEntry.date.replace("CEST ",""));
        });
      }

      if(!jsonData.hasOwnProperty(hostname)){
        jsonData[hostname] = {};
      }

      properties.forEach(function(property) {
        var name = property.replace("_"," ");

        if(!jsonData.hasOwnProperty(name)){
          jsonData[hostname][name] = [];
        }

        json.history.forEach(function(histEntry){
          jsonData[hostname][name].push(histEntry[name]);
        });
      });
      console.log(hostname + " integrated");
    }

    function setupDefaultValues(hosts, properties){
      hosts.forEach(function(host){
        $("#host-selection input[type='text']").eq(2).tagsinput('add', host);
      });

      properties.forEach(function(property){
        $("#property-selection input[type='text']").eq(2).tagsinput('add', property);
      });
    }

    // TODO: normalize values

    $(document).ready(function() {
      setupSortableDivs();

      setupPropertyDialog();
      setupPropertySelection();

      setupHostDialog();
      setupHostSelection();

      setupDefaultValues(["PI0", "PI1", "PI2"],[/*"voltage", "current", "cputemp", "pmutemp", "hddtemp"*/]);
    });
