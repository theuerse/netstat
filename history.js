var properties = ["current", "voltage", "cputemp", "hddtemp", "pmutemp", "cpu0freq",
"cpu1freq","txbytes","rxbytes","Free_RAM","Uptime","Load"];

var hosts = [];

var units = {"current": "[A]", "voltage": "[V]", "cputemp": "[°C]", "hddtemp" : "[°C]",
"pmutemp": "[°C]", "cpu0freq": "[MHz]","cpu1freq": "[MHz]","txbytes": "[MB]",
"rxbytes": "[MB]","Free_RAM": "[MB]","Uptime":"[D]","Load":"[%]" };

var defaultValues = {
  properties: ["voltage", "current", "cputemp", "pmutemp", "hddtemp"]
};

var jsonData = {"x":[]};
var charts = {};
var dateRange = [];

var graphInformation = {}; // caches graph info before drawing
var charts = {}; // holds references to drawn charts
var progressbar;



function setupPropertySelection(){

  var propertyList = [];
  properties.forEach(function(property){
    propertyList.push({name: property});
  });

  var propertyNames = new Bloodhound({
      datumTokenizer: Bloodhound.tokenizers.obj.whitespace('name'),
      queryTokenizer: Bloodhound.tokenizers.whitespace,
      local: propertyList
    });
  propertyNames.initialize();

  $( "#property-selection input[type='text']" ).tagsinput({
    typeaheadjs: {
      name: 'propertynames',
      displayKey: 'name',
      valueKey: 'name',
      source: propertyNames.ttAdapter()
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
    }
    else{
      $("#sortable").append('<li id="p_' + propertyName +'" class="ui-state-default">' +
      '<div class="paperLikeShadow"><p class="propertyTitle">' +  propertyName +" "+ units[propertyName] + '</p><div id="chart_' + propertyName +'"></div></div></li>');

      // setup sortable divs
      $( "#sortable" ).sortable();

      $("#chk_" + propertyName).prop('checked', true);
      $("#chk_" + propertyName).button( "refresh" );

      setupChart(propertyName);
    }
    $.notify({title: "<strong>" + propertyName + "</strong>", message: ": Graph added"},
      {
        placement: {from: "bottom", align: "right"},
        newest_on_top: true,
        animate: {
          enter: 'animated fadeInDown',
          exit: 'animated fadeOutUp'
        },
        type: 'success'
      });
  });

  $("#property-selection input[type='text']").eq(2).on('itemRemoved', function(event) {
    if($("#p_" + event.item).length){
      $("#p_" + event.item).hide();
      $("#chk_" + event.item).prop('checked', false);
      $("#chk_" + event.item).button( "refresh" );

      $.notify({title: "<strong>" + event.item + "</strong>", message: ": Graph removed"},
        {
          placement: {from: "bottom", align: "right"},
          newest_on_top: true,
          animate: {
            enter: 'animated fadeInDown',
            exit: 'animated fadeOutUp'
          },
          type: 'danger'
        });
    }
  });

  $("#property-selection div.bootstrap-tagsinput").first().append(
    '<button id="propertyBtn" class="tagsBtn"><i class="fa fa-cog" aria-hidden="true"></i></button>');

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
  var hostList = [];
  hosts.forEach(function(property){
    hostList.push({name: property});
  });

  var hostNames = new Bloodhound({
      datumTokenizer: Bloodhound.tokenizers.obj.whitespace('name'),
      queryTokenizer: Bloodhound.tokenizers.whitespace,
      local: hostList
    });
  hostNames.initialize();

  $( "#host-selection input[type='text']" ).tagsinput({
    typeaheadjs: {
      name: 'hostNames',
      displayKey: 'name',
      valueKey: 'name',
      source: hostNames.ttAdapter()
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
    if(! jsonData.hasOwnProperty(hostname)){
      $.getJSON("history/gzip/" + hostname + "_hist.json").done(function(json){
        integrateJsonData(hostname, json);
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
  });

  $("#host-selection input[type='text']" ).eq(2).on('itemRemoved', function(event) {
    var hostname = event.item;
    $("#chk_" + hostname).prop('checked', false);
    $("#chk_" + hostname).button( "refresh" );

    // update existing charts
    for(var propertyName in charts) {
      charts[propertyName].hide([hostname],{withLegend: true});
    }
  });

  $("#host-selection div.bootstrap-tagsinput").first().append(
    '<button id="hostBtn" class="tagsBtn"><i class="fa fa-cog" aria-hidden="true"></i></button>');

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
  columns = [['x'].concat(jsonData.x)];
  $("#host-selection input[type='text']").eq(2).tagsinput('items').forEach(function(hostname){
    if(jsonData.hasOwnProperty(hostname)){
      columns.push([hostname].concat(jsonData[hostname][propertyName.replace(/\_/g, ' ')]));
    }
  });

  charts[propertyName] = c3.generate({
    bindto: '#chart_' + propertyName,
    data: {
      x: 'x',
      xFormat: '%a %b %d %H:%M:%S %Y',
      columns: columns
    },
    point: {
      show: false
    },
    transition: {
      duration: 0
    },
    interaction: {
      enabled: false
    },
    axis: {
      x: {
        type: 'timeseries',
        min: dateRange[0],
        max: dateRange[1],
        tick: {
          format: '%d.%m.%Y %H:%M:%S',
        }
      },
      y: {
        tick: {
          format: d3.format(",.2f")
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
    },
    padding: {
      bottom: 30
    }
  });
}

function integrateJsonData(hostname, json){
  if(Object.keys(jsonData).length == 1){
    // adding first PI
    json.history.forEach(function(histEntry){
      // Mon Oct 31 01:30:01 CET 2016 , time-zone is second-last
      var dateParts = histEntry.date.split(" ");
      dateParts.splice(4,1);
      jsonData.x.push(dateParts.join(" "));
    });

    $("#datefrom").datepicker({
      minDate: new Date(jsonData.x[0]),
      maxDate:  new Date(jsonData.x[jsonData.x.length-1]),
      onSelect: function(date) {
        dateRange[0] = new Date(date);

        // if the user selected the first day on record, do not start chart
        // with 00:00, start instead with time of first entry (e.g. 09:00) (no leading "empty-space")
        var firstdate = new Date(jsonData.x[0]);
        if(dateRange[0].toDateString() == firstdate.toDateString()){
          dateRange[0] = firstdate;
        }

        // update existing charts
        for(var propertyName in charts) {
          charts[propertyName].axis.range({min: {x: dateRange[0]}});
        }
      }
    });
    $("#datefrom").datepicker('setDate', new Date(jsonData.x[0]));

    $("#dateto").datepicker({
      value: new Date(jsonData.x[jsonData.x.length-1]),
      minDate: new Date(jsonData.x[0]),
      maxDate:  new Date(jsonData.x[jsonData.x.length-1]),
      onSelect: function(date) {
        dateRange[1] = new Date(date);

        // if the user selected the last day on record, do not end chart
        // with 00:00, end instead with time of last entry (e.g. 09:00) (no trailing "empty-space")
        var lastdate = new Date(jsonData.x[jsonData.x.length-1]);
        if(dateRange[1].toDateString() == lastdate.toDateString()){
          dateRange[1] = lastdate;
        }

        // update existing charts
        for(var propertyName in charts) {
          charts[propertyName].axis.range({max:{x: dateRange[1]}});
        }
      }
    });
    $("#dateto").datepicker('setDate', new Date(jsonData.x[jsonData.x.length-1]));

  }

  var data = {};
  properties.forEach(function(property){
    data[property.split("_").join(" ")] = [];
  });

  json.history.forEach(function(histEntry){
    data.voltage.push(histEntry.voltage / 1000000);
    data.current.push(histEntry.current / 1000000);
    data.cpu0freq.push(histEntry.cpu0freq / 1000);
    data.cpu1freq.push(histEntry.cpu1freq / 1000);
    data.cputemp.push(histEntry.cputemp.replace("°C",""));
    data.pmutemp.push(histEntry.pmutemp.replace("°C",""));
    data.txbytes.push(histEntry.txbytes / 1000000);
    data.rxbytes.push(histEntry.rxbytes / 1000000);

    data.hddtemp.push(histEntry.hddtemp);
    data["Free RAM"].push(histEntry["Free RAM"]);

    var parts = histEntry.Uptime.split(" "); //parts[0]...value, parts[1]...unit
    data.Uptime.push((parts[1] == "days") ? parts[0] : 0);

    data.Load.push(histEntry.Load);
  });

  jsonData[hostname] = data;
  // hosts.length+1 ... compensate x-property present in jsonData
  progressbar.progressbar( "value", (Object.keys(jsonData).length / (hosts.length+1))*100);
}

function getHostHistoryInformation(){
  // start displaying progress indicator
  progressbar.show();

  // for host in hosts, request history_file
  hosts.forEach(function(host){
    $("#host-selection input[type='text']").eq(2).tagsinput('add', host);
  });

  // progress is updated when a file has been downloaded and integrated
  // progress 100% -> hide progressbar and display default-charts
}
