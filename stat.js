


//
// Utility-functions
//
function getHostsInformation(){
  $(".hoststatus").each(function(index){
    var parent = $(this);
    // PI# = last octet of IP-address - 10 (192.168.0.12 -> PI2)
    var id = "PI" + (parseInt($(this).attr('id').split('.')[3]) - 10);
    hosts.push(id);
    //$("#host-selection input[type='text']").eq(2).tagsinput('add', id);

    // get current status-files
    $.getJSON(id + ".json").done(function(json){drawStatusOverview(parent, json);})
    .fail(function( jqxhr, textStatus, error ) {
      var err = textStatus + ", " + error;
      console.log( "Request Failed: " + err );
    });
  });
  console.log(hosts);
}

// filling in the blanks in the given HTML-structure
function drawStatusOverview(parent,json){
  // header-item: replace text "pending" with date of JSON-creation
  var header = parent.children(".header").first();
  header.text(header.text().replace("pending", json.date));

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
  html = '<span class="caption">CPU-Frequency: </span><ul>' +
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
  html = '<span class="caption">Temperatures: </span><ul>' +
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
        '<span class="value">' + json.hddtemp + '.0°C</span>' +
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


//
// main entry point of js
//
$(document).ready(function() {
  $( "#tabs" ).tabs(
    {
      // remember last selected Tab
      active: localStorage.getItem("currentTabIndex"),
      activate: function(event, ui) {
        localStorage.setItem("currentTabIndex", ui.newPanel[0].dataset.tabIndex);
      }
    }
  );

  getHostsInformation();

  setupSortableDivs();

  setupHostDialog();
  setupHostSelection();

  setupPropertyDialog();
  setupPropertySelection();

  setupDefaultValues();
  });
