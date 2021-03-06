


//
// Utility-functions
//
function getHostStatusInformation(){
  $(".hoststatus").each(function(index){
    var parent = $(this);
    // PI# = last octet of IP-address - 10 (192.168.0.12 -> PI2)
    var id = "PI" + (parseInt($(this).attr('id').split('.')[3]) - 10);
    hosts.push(id);

    // get current status-files
    $.getJSON(id + ".json").done(function(json){drawStatusOverview(parent, json);})
    .fail(function( jqxhr, textStatus, error ) {
      var err = textStatus + ", " + error;
      console.log( "Request Failed: " + err );
    });
  });
}

// filling in the blanks in the given HTML-structure
function drawStatusOverview(parent,json){
  // header-item: replace text "pending" with date of JSON-creation
  var header = parent.children(".header").first();
  header.text(header.text().replace("pending", json.date));
  header.text(header.text() + " " + json.Hostname);

  // Services
  var html = '<span class="caption">Services: </span><ul>';

  $.each( json.Services, function( key, value ) {
    if(value === "running")
      html += '<li class="up">' + key + '</li>';
    else
      html += '<li class="down">' + key + '</li>';
  });
  parent.find('div.services').first().html(html +'</ul>');

  // Load, Uptime, Users (misc)
  html = '<ul>' +
    '<li><span class="caption">CPU-Load: </span>' +
    '<span class="value">' + Math.round(json.Load * 100) + '%</span>' +
    '<meter value="' + json.Load + '">' + Math.round(json.Load) +'%</meter></li>' +
    '<li></br></li>' +
    '<li><span class="caption">Uptime: </span>' +
    '<span class="value">' + json.Uptime + '</span></li>' +
    '<li><span class="caption">Users: </span>' +
    '<span class="value">' + json["Users logged on"] + '</span></li>' +
    '</ul>';
  parent.find('div.misc').first().html(html);

  // CPU-Frequencies (cpufreq)
  html = '<span class="caption">Frequency: </span><ul>' +
      '<li></br></li>' +
      '<li>' +
        '<span class="caption">Cpu 0: </span>' +
        '<span class="value">' + json.cpu0freq/1000 + 'MHz</span>' +
      '</li>'+
      '<li>' +
        '<span class="caption">Cpu 1: </span>' +
        '<span class="value">' + json.cpu1freq/1000 + 'MHz</span>' +
      '</li>' +
    '</ul>';
  parent.find('div.cpufreq').first().html(html);

  // Temperatures (temperatures)
  html = '<span class="caption">Temperature: </span><ul>' +
      '<li></br></li>' +
      '<li>' +
        '<span class="caption">SoC: </span>' +
        '<span class="value">' + json.cputemp + '</span>' +
      '</li>'+
      '<li>' +
        '<span class="caption">PMU: </span>' +
        '<span class="value">' + json.pmutemp + '</span>' +
      '</li>' +
      '<li>' +
        '<span class="caption">HDD: </span>' +
        '<span class="value">' + json.hddtemp + '</span>' +
      '</li>' +
    '</ul>';
  parent.find('div.temperatures').first().html(html);

  // Voltage and Current (power)
  html = '<span class="caption">Power: </span><ul>' +
      '<li></br></li>' +
      '<li>' +
        '<span class="caption">Voltage: </span>' +
        '<span class="value">' + json.voltage/1000000 + 'V</span>' +
      '</li>'+
      '<li>' +
        '<span class="caption">Current: </span>' +
        '<span class="value">' + json.current/1000000 + 'A</span>' +
      '</li>' +
    '</ul>';
  parent.find('div.power').first().html(html);

  // SDD (ssd)
  html = '<span class="caption">SDD: </span><ul>' +
      '<meter value="' + json.Disk.used.replace("G","") + '" min="0" max="' +
        json.Disk.total.replace("G","") + '"></meter>' +
      '<li>' +
        '<span class="caption">Total: </span>' +
        '<span class="value">' + json.Disk.total + 'B</span>' +
      '</li>'+
      '<li>' +
        '<span class="caption">Used: </span>' +
        '<span class="value">' + json.Disk.used + 'B</span>' +
      '</li>' +
      '<li>' +
        '<span class="caption">Free: </span>' +
        '<span class="value">' + json.Disk.free + 'B</span>' +
      '</li>' +
    '</ul>';
  parent.find('div.ssd').first().html(html);

  // RAM (ram)
  html = '<span class="caption">RAM: </span><ul>' +
        '<meter value="' + (json["Total RAM"] - json["Free RAM"]) + '" min="0" max="' +
          json["Total RAM"] + '"></meter>' +
      '<li>' +
        '<span class="caption">Total: </span>' +
        '<span class="value">' + json["Total RAM"] + 'MB</span>' +
      '</li>'+
      '<li>' +
        '<span class="caption">Used: </span>' +
        '<span class="value">' + (json["Total RAM"] - json["Free RAM"]) + 'MB</span>' +
      '</li>' +
      '<li>' +
        '<span class="caption">Free: </span>' +
        '<span class="value">' + json["Free RAM"] + 'MB</span>' +
      '</li>' +
    '</ul>';
  parent.find('div.ram').first().html(html);

  // Networktraffic (traffic)
  html = '<span class="caption">Network-Traffic: </span><ul>' +
      '<li></br></li>' +
      '<li>' +
        '<span class="caption">RX: </span>' +
        '<span class="value">' + addUnitOfTraffic(json.rxbytes) + '</span>' +
      '</li>'+
      '<li>' +
        '<span class="caption">TX: </span>' +
        '<span class="value">' + addUnitOfTraffic(json.txbytes) + '</span>' +
      '</li>' +
    '</ul>';
  parent.find('div.traffic').first().html(html);
}

// returns the correct SI-unit for a given number of Bytes
function addUnitOfTraffic(bytes){
  var traffic = Math.round(((bytes / 1024) / 1024));
  if (traffic < 1024) {
    return traffic + " MB";
  } else if (traffic < 1024000) {
    traffic = Math.round((traffic / 1024),2);
    return traffic + " GB";
  } else if (traffic > 1024000) {
    traffic = Math.round(((traffic / 1024) / 1024),2);
    return traffic + " TB";
  }
}

// init-textfields by sending enter (fixes spacious width-preset)
function initTagsinputTextfields(){
  var e = jQuery.Event("keypress");
  e.which = 13; // 13 .. enter-key
  e.keyCode = 13;
  $(".tt-input").trigger(e);
}


//
// main entry point of js
//
$(document).ready(function() {
  // hide javaScriptAlert - div, proof that js works
  $(javaScriptAlert).hide();

  getHostStatusInformation();

  // setup progressbar
  progressbar = $( "#progressbar" );
  progressLabel = $( ".progress-label" );

  progressbar.progressbar({
    value: false,
    create: function(event, ui) {
      $(this).find('.ui-widget-header').css({'background-color':'#007FFF'});
    },
    complete: function() {
      progressLabel.text( "Complete!" );

      defaultValues.properties.forEach(function(property){
        $("#property-selection input[type='text']").eq(2).tagsinput('add', property);
      });

      setTimeout(function(){
        // update existing charts to ensure, that the charts are within bounds
        for(var propertyName in charts) {
          charts[propertyName].axis.range({min: {x: dateRange[0]}});
        }
        progressbar.hide();
      }, 2000);
    }
  });
  progressbar.hide();

  $( "#tabs" ).tabs(
    {
      // remember last selected Tab
      active: localStorage.getItem("currentTabIndex"),
      activate: function(event, ui) {
        localStorage.setItem("currentTabIndex", ui.newPanel[0].dataset.tabIndex);
        if(ui.newPanel[0].dataset.tabIndex == 1){
          // tabindex == 1 ->historyTab active
          if(progressbar.progressbar("value") < 100){
            getHostHistoryInformation();
          }

        }
      }
    }
  );

  // setup sortable divs
  $( "#sortable" ).sortable();

  setupHostDialog();
  setupHostSelection();

  setupPropertyDialog();
  setupPropertySelection();

  initTagsinputTextfields();

  if(localStorage.getItem("currentTabIndex") == 1){
    getHostHistoryInformation();
  }
});
