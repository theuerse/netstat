<?php
/*
Following is the LICENSE-note of the program that served as base for this one:
(https://github.com/RaymiiOrg/raymon)
#Copyright (c) 2012 Remy van Elst
#Permission is hereby granted, free of charge, to any person obtaining a copy
#of this software and associated documentation files (the "Software"), to deal
#in the Software without restriction, including without limitation the rights
#to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
#copies of the Software, and to permit persons to whom the Software is
#furnished to do so, subject to the following conditions:
#
#The above copyright notice and this permission notice shall be included in
#all copies or substantial portions of the Software.
#
#THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
#IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
#FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
#AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
#LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
#OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
#THE SOFTWARE.
*/

// <CONFIGURATION>
$hostlist=array(  // jsonFilename => sourceUrl
    'PI0.json' => 'http://192.168.0.10/stat.json',
    'PI1.json' => 'http://192.168.0.11/stat.json',
    'PI2.json' => 'http://192.168.0.12/stat.json',
    'PI3.json' => 'http://192.168.0.13/stat.json',
    'PI4.json' => 'http://192.168.0.14/stat.json',
    'PI5.json' => 'http://192.168.0.15/stat.json',
    'PI6.json' => 'http://192.168.0.16/stat.json',
    'PI7.json' => 'http://192.168.0.17/stat.json',
    'PI8.json' => 'http://192.168.0.18/stat.json',
    'PI9.json' => 'http://192.168.0.19/stat.json',
    'PI10.json' => 'http://192.168.0.20/stat.json',
    'PI11.json' => 'http://192.168.0.21/stat.json',
    'PI12.json' => 'http://192.168.0.22/stat.json',
    'PI13.json' => 'http://192.168.0.23/stat.json',
    'PI14.json' => 'http://192.168.0.24/stat.json',
    'PI15.json' => 'http://192.168.0.25/stat.json',
    'PI16.json' => 'http://192.168.0.26/stat.json',
    'PI17.json' => 'http://192.168.0.27/stat.json',
    'PI18.json' => 'http://192.168.0.28/stat.json',
    'PI19.json' => 'http://192.168.0.29/stat.json'
);

$pinglist = array(
    '192.168.0.10',
    '192.168.0.11',
    '192.168.0.12',
    '192.168.0.13',
    '192.168.0.14',
    '192.168.0.15',
    '192.168.0.16',
    '192.168.0.17',
    '192.168.0.18',
    '192.168.0.19',
    '192.168.0.20',
    '192.168.0.21',
    '192.168.0.22',
    '192.168.0.23',
    '192.168.0.24',
    '192.168.0.25',
    '192.168.0.26',
    '192.168.0.27',
    '192.168.0.28',
    '192.168.0.29'
);

## Set this to "secure" the history saving. This key has to be given as a parameter to save the history.
$historykey = "8A29691737D";

#Set this or your logs will fill up your disk. // TODO: REALLY?
date_default_timezone_set('Europe/Vienna');

// </CONFIGURATION>

$historyFiles = array(); // global array for storing the name of all files in the history-folder

// Collects the names of all files in the history-folder
function readHistoryFiles(){
  global $historyFiles;
  global $hostlist;

  $historyFiles = array();

  if ($handle = opendir('./history/')) {
      while (false !== ($entry = readdir($handle))) {
          $filenameParts = explode(".", $entry);
          $jsonFilename = $filenameParts[0] . '.' . $filenameParts[1];

          if(!isset($historyFiles[$jsonFilename])) $historyFiles[$jsonFilename] = array();
          array_push($historyFiles[$jsonFilename],$entry);
      }
      closedir($handle);
  } else {
      echo "Error: cannot open directory './history'.";
  }

  // sort entries for each $jsonFilename (in ascending order)
  foreach($historyFiles as $key => $value){
      asort($historyFiles[$key]);
  }
}

// Returns a dataset containing all (historic) statistic information of a given jsonFile-name
function getHistoryDatasets($jsonFilename) {
    global $historyFiles;
    $dataset = array();
    $i=0;

    foreach($historyFiles[$jsonFilename] as $filename){
      $jsonFilePath = "./history/" . $filename;
      if ($jsonFileContent = file_get_contents($jsonFilePath)) {
        if ($jsonObject = json_decode($jsonFileContent, true)) {
            $dataset[$i++] = conformJsonValues($jsonObject);
        } else {
            echo "Cannot decode json file $jsonfile.";
        }
      } else {
          echo "Cannot open json file $jsonfile.";
      }
    }
	  return $dataset;
}

// Transforms the jsonObjects respective values in units more suitable
// to be displayed in the history
function conformJsonValues($jsonObject) {
  $jsonObject['cpu0freq'] = intval($jsonObject['cpu0freq'])/1000;
  $jsonObject['cpu1freq'] = intval($jsonObject['cpu1freq'])/1000;
  $jsonObject['cputemp'] = substr($jsonObject['cputemp'],0,4);
  $jsonObject['pmutemp'] = substr($jsonObject['pmutemp'],0,4);
  $jsonObject['voltage'] = intval($jsonObject['voltage'])/1000000;
  $jsonObject['current'] = intval($jsonObject['current'])/1000000;

  return $jsonObject;
}


function shortstat($jsonFilename,$hostname) {
    echo "<table class=\"striped\">";
    printStatTable($jsonFilename,$hostname);
    echo "</table>";
}

// Prints a percentage-bar visualizing a given $percentage
// bar via http://www.joshuawinn.com/quick-and-simple-css-percentage-bar-using-php/
function percentagebar($percentage) {

    $percentage = str_replace("%", "",$percentage);
    echo "<p>".$percentage . "%</p>";
    echo "<div class=\"percentbar\" style=\"width: 100px;\">";
    echo "<div style=\"width:".round($percentage)."px;\">";
    echo "</div></div>";
}

// Requests a remote file and saves it in the history-folder under a $newFilename
function downloadRemoteFile($sourceUrl,$newFilename){
    $local_file="";

    // wait for a maximum total time of 10[s]
    for($time_waited = 0; $time_waited <= 10000; $time_waited = $time_waited + 250){
      $local_file=file_get_contents($sourceUrl);
      if(!empty($local_file)){
       break;
      }
      usleep(250000); // sleep 250[ms]
    }

    if(!empty($local_file)){
      file_put_contents($newFilename, $local_file);
    }
}

// Add the file with given $jsonFilename to the history-folder
function addToHistory($jsonFilename) {
    $DATETIME=date('U');
    if(!is_dir("history")){
        mkdir("history") or die("Cannot create history folder. Create it manually and make sure the webserver can write to it.");
    }
    $local_file=file_get_contents($jsonFilename);

    // file_put_contents returns false in case of an exception (else the number of written Bytes)
    if (file_put_contents("history/${jsonFilename}.${DATETIME}", $local_file) === false) {
        exit("File $jsonFilename could not be saved in history. Please check directory permissions on directory \'history\'.");
    }
}

// Print current information about a Pi in form of a Table
// based on the information in file $jsonFilename
function printStatTable($jsonFilename,$hostname) {

    if ($file = file_get_contents($jsonFilename)) {
        if ($jsonObject = json_decode($file, true)) {
          ?>

          <tr>
            <th>Uptime</th>
            <th>Services</th>
            <th>Load</th>
            <th>Users</th>
            <th>Cpu 0 Freq. (MHz)</th>
            <th>Cpu 1 Freq. (MHz)</th>
            <th>SoC Temp. (C)</th>
            <th>PMU Temp. (C)</th>
            <th>HDD Temp. (C)</th>
            <th>Voltage (V)</th>
            <th>Current (A)</th>
            <th>HDD (T/U/F)</th>
            <th>RAM (T/U/F)</th>
            <th>NET RX</th>
            <th>NET TX</th>
          </tr>
          <tr>
            <td>
              <?php
                  // print the uptime of the Pi
                  echo str_replace(",", "",$jsonObject['Uptime']);
              ?>
            </td>
            <td>
              <?php
                    // print the state (running/not running) of some selected applications on the pi
                    foreach ($jsonObject['Services'] as $service => $status) {
                        if($status == "running") {
                            echo '<span class="up">' . $service . '</span> up. <br /> ';
                        } elseif ($status == "not running") {
                            echo '<span class="down">' . $service . '</span> <b>down.</b> <br /> ';
                        }
                    }
              ?>
            </td>
              <?php
                  // print information about the current CPU-load of the Pi
                  echo "<td>".round(floatval(str_replace(",", "",$jsonObject['Load'])),3)."</td>";

                  // print the number of logged in users on the Pi
                  echo "<td>".$jsonObject['Users logged on']."</td>";

                  // change (units of) values of the json-Object to be more suitable for displaying them
                  $jsonObject = conformJsonValues($jsonObject);

                  // print additional status information
                  echo "<td>".$jsonObject['cpu0freq']."</td>";
                  echo "<td>".$jsonObject['cpu1freq']."</td>";
                  echo "<td>".$jsonObject['cputemp']."</td>";
                  echo "<td>".$jsonObject['pmutemp']."</td>";
                  echo "<td>".$jsonObject['hddtemp']."</td>";
                  echo "<td>".$jsonObject['voltage']."</td>";
                  echo "<td>".$jsonObject['current']."</td>";
              ?>
            <td>
              <?php
                // print a bar representing the available space on the Pi's disk
                percentagebar($jsonObject['Disk']['percentage']);
                echo "<br />";
                // print disk-information in greater detail
                echo "T: " . $jsonObject['Disk']['total'] . " <br /> ";
                echo "U: " . $jsonObject['Disk']['used'] . " <br /> ";
                echo "F: " . $jsonObject['Disk']['free'];
              ?>
            </td>
            <td>
              <?php
                // info-section about the Pi's RAM
                $used_ram = $jsonObject['Total RAM'] - $jsonObject['Free RAM'];
                $value = $used_ram;
                $max = $jsonObject['Total RAM'];
                $scale = 1.0;

                if (!empty($max)) {
                  $percent = ($value * 100) / $max;
                } else {
                  $percent = 0;
                }
                if ($percent > 100) {
                  $percent = 100;
                }
                // print a bar representing the available primary memory of the Pi
                percentagebar(round($percent * $scale));
                echo "<br />";
                // print memory-information in greater detail
                echo "T: " . $jsonObject['Total RAM'] . " MB <br /> ";
                echo "U: " . $used_ram . " MB <br /> ";
                echo "F: " . $jsonObject['Free RAM'] . " MB";
              ?>
            </td>
            <td>
              <?php
                // print incoming traffic (RX)
                $rxmb=round((($jsonObject['rxbytes'] / 1024) / 1024));
                if ($rxmb < 1024) {
                    echo $rxmb . " MB";
                } elseif ($rxmb < 1024000) {
                    $rxmb = round(($rxmb / 1024),2);
                    echo $rxmb . " GB";
                } elseif ($rxmb > 1024000) {
                    $rxmb = round((($rxmb / 1024) / 1024),2);
                    echo $rxmb . " TB";
                }
              ?>
            </td>
            <td>
              <?php
                // print outgoing traffic (TX)
                $txmb=round((($jsonObject['txbytes'] / 1024) / 1024));
                if ($txmb < 1024) {
                    echo $txmb . " MB";
                } elseif ($txmb < 1024000) {
                    $txmb = round(($txmb / 1024),2);
                    echo $txmb . " GB";
                } elseif ($txmb > 1024000) {
                    $txmb = round((($txmb / 1024) / 1024),2);
                    echo $txmb . " TB";
                }
              ?>
            </td>
          </tr>
        <?php
        } else {
            echo "Error decoding JSON stat file for host $host, $json_a";
        }
    } else  {
      echo "Error while getting stats for host $host from file $bestand";
    }
}

// Pings a given $host and prints the result
function ping($host, $port, $timeout) {
  $tB = microtime(true);
  $fP = fSockOpen($host, $port, $errno, $errstr, $timeout);
  if (!$fP) {  return '<span class="down">' . $host . ' DOWN from here. </span>'; }
  $tA = microtime(true);
  return '<span class="up">' . $host . ' ' . number_format((($tA - $tB) * 1000),2).' ms UP</span>';
}

// Read all historic information of a certain
// attribute ($dtype) of a certain Pi ($pi_index)
// and store it in a cache, until it is used to actually
// draw the graph, also adds a HTML-canvas as base for the graph
function genGraphInformation($pi_index, $dtype, $datasets)
{
  $labels = "";
  $i = 0;
  $data ="";

  for($i=0; $i<count($datasets);$i++){
      $dp = $datasets[$i];
      $mydate = date_parse($dp['date']);

      if($i == count($datasets)-1) $labels = $labels."'".pad($mydate['day']).".".pad($mydate['month'])." ".pad($mydate['hour']).":".pad($mydate['minute'])."'";
      else $labels = $labels."'".pad($mydate['day']).".".pad($mydate['month'])." ".pad($mydate['hour']).":".pad($mydate['minute'])."',";

      if($i == count($datasets)-1) $data = $data."".$dp["".$dtype];
      else $data = $data."".floatval($dp["".$dtype]).",";
    }

    // choose correct SI-unit for the current value-history being displayed ($dtype)
    $SI ="N/A";

    if(strcmp($dtype,'voltage')==0) $SI = "V";
    if(strcmp($dtype,'current')==0) $SI = "A";
    if(strcmp($dtype,'cputemp')==0) $SI = "C";
    if(strcmp($dtype,'pmutemp')==0) $SI = "C";
    if(strcmp($dtype,'hddtemp')==0) $SI = "C";
    if(strcmp($dtype,'Load')==0) $SI = "%";
    if(strcmp($dtype,'cpu0freq')==0) $SI = "MHz";
    if(strcmp($dtype,'cpu1freq')==0) $SI = "MHz";

    // insert a labeled canvas as container for the chart
    echo "<p class='".$dtype."Atr'><strong>".$dtype." [".$SI."]:</strong></p><canvas id='$dtype-$pi_index' class='". $dtype ."Atr' width='1000' height='400'></canvas>
    <script>
      // specify the actual data for the individual Pi
      graphInformation['$dtype-$pi_index'] = {
        labels: [$labels],
        datasets: [
          {
            label: '$dtype',
            fillColor: 'rgba(151,187,205,0.2)',
            strokeColor: 'rgba(151,187,205,1)',
            pointColor: 'rgba(151,187,205,1)',
            pointStrokeColor: '#fff',
            pointHighlightFill: '#fff',
            pointHighlightStroke: 'rgba(151,187,205,1)',
            data: [$data]
          }
        ]
      };
    </script>";
}

// returns a zero-padded version of a given $string
// just a wrapper for str_pad()
function pad($string){
  return str_pad($string,2,'0',STR_PAD_LEFT);
}



//
// Begin of the PHP-Scripts 'execution'
//

// Check if the site has been called with the right params to trigger a history-save
// where history-save only means that the json-files in the same directory
// as this script get added to the history-folder
if ($_GET["action"] == "save" && $_GET["key"] == "$historykey") {
    // for every json-file of a Pi we care about
    foreach ($hostlist as $jsonFilename => $sourceUrl) {
        downloadRemoteFile($sourceUrl, $jsonFilename); // get the current json-file
        addToHistory($jsonFilename); // add the downloaded file to the history
        echo "History for: ". $jsonFilename . " saved. <br />\n" ;
    }
    exit("History done.<br /> \n"); // end the script at this point
}


// 'Normal' - call of the script, display the two statistics-tabs
?>
<!DOCTYPE html>
<html>
<head>
    <title>Stats</title>
    <meta charset="utf-8"/>

    <link rel="stylesheet" type="text/css" href="inc/css/kickstart.css" media="all" />      <!-- KICKSTART -->
    <link rel="stylesheet" type="text/css" href="inc/css/style.css" media="all" />          <!-- CUSTOM STYLES -->

    <style type="text/css">
        .up {color: green;}
        .down {color: red;}
        .percentbar { background:#CCCCCC; border:1px solid #666666; height:10px; }
        .percentbar div { background: #28B8C0; height: 10px; }
        #tabc1.tab-content.clearfix table.striped tbody tr.last td p {color: black; text-align: center; margin-bottom: 0px;}
        #tabc1.tab-content.clearfix table.striped tbody tr.last td {font-size: 14.4px; line-height: 130%;}
        #tabc1.tab-content.clearfix table.striped tbody tr.first th {font-size: 14.4px; line-height: 130%;}
    </style>


    <script type="text/javascript" src="inc/js/jquery.min.js"></script>                     <!-- JQUERY -->
    <script type="text/javascript" src="inc/js/prettify.js"></script>                       <!-- PRETTIFY -->
    <script type="text/javascript" src="inc/js/kickstart.js"></script>                      <!-- KICKSTART -->
    <script type="text/javascript" src="Chart.js"></script>                                 <!-- Chart.JS -->

    <script type="text/javascript">
      var graphInformation = {}; // caches graph info before drawing
      var charts = {}; // holds references to drawn charts

      // define global options for all history-graphs
      var graphOptions = {
          // avoid partial cutoff of leading digits of y-axis
          scaleLabel : function(object) {return " " + object.value;},

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
          var showText="Show";
          var hideText="Hide";
          $(".toggle").prev().append(' (<a href="#" class="toggleLink">'+showText+'</a>)');
          $('.toggle').hide();
          $('a.toggleLink').click(function() {
              $(this).parent().next('.toggle').toggle('slow');
              if ($(this).html()==showText) {
                 $(this).html(hideText); // change text of link from 'Show' to 'Hide'

                 // draw chart in canvas (once)
                 $(this).parent().next('.toggle').children('canvas').each(function(index,value){
                   console.log(value.id+"\n");
                   if(graphInformation[value.id] !== undefined){
                     // show item for creation
                     $('#'+value.id).show();

		     // draw graph in/on pre-existing canvas
                     var context = document.getElementById(value.id).getContext('2d');
                     charts[value.id] = new Chart(context).Line(graphInformation[value.id],graphOptions);

                     // get rid of cached information => chart is created only once
                     delete graphInformation[value.id];

		     // hide graph if necessary
                     console.log(value.id);
		     var attributeClass = $('#'+value.id).attr('class');
                     if(!$('#'+attributeClass+'Checkbox').is(':checked')){
			console.log('hiding ' + value.id + '\n');
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
	 var attributes = ['voltageAtr','currentAtr','cputempAtr','pmutempAtr','hddtempAtr'];
         attributes.forEach(function(attribute) {
	   $('#'+attribute+'Checkbox').change(function(checkbox){
	     if(checkbox.currentTarget.checked){
               $('.'+checkbox.currentTarget.value).show();
             }else {
               $('.'+checkbox.currentTarget.value).hide();
             }
           });
	 });
      });
    </script>
</head>

<body>
    <a id="top-of-page"></a><div id="wrap" class="clearfix">
    <div class="col_12">
        <ul class="tabs left">
            <li><a href="#tabc1">Overview</a></li>
            <li><a href="#tabc2">History</a></li>
        </ul>
        <div id="tabc1" class="tab-content">
            <?php
              echo "<i>Ping monitor:</i>";
              foreach ($pinglist as $key => $value) {
                echo ping("$value",80,1) . ", ";
              }
            ?>
            <h4>Server Status</h4>
            <?php
              foreach ($hostlist as $jsonFilename => $sourceUrl) {
                $host=parse_url($sourceUrl,PHP_URL_HOST);
                echo "<h5>Host: ${host}</h5>";

                downloadRemoteFile($sourceUrl,$jsonFilename); // get the current json-file
                $hostname=parse_url($host,PHP_URL_HOST);
                shortstat($jsonFilename,$hostname);
                echo "<hr class=\"alt1\" />";
              }
            ?>
        </div>
        <div id="tabc2" class="tab-content">
            <?php
                readHistoryFiles(); // read all available history files
                $pi_index = 0;

		// Draw checkboxes to show/hide pi-attributes
		echo '<input id="voltageAtrCheckbox" type="checkbox" value="voltageAtr" checked="checked">voltage &nbsp;';
		echo '<input id="currentAtrCheckbox" type="checkbox" value="currentAtr" checked="checked">current &nbsp;';
		echo '<input id="cputempAtrCheckbox" type="checkbox" value="cputempAtr" checked="checked">cputemp &nbsp;';
		echo '<input id="pmutempAtrCheckbox" type="checkbox" value="pmutempAtr" checked="checked">pmutemp &nbsp;';
      		echo '<input id="hddtempAtrCheckbox" type="checkbox" value="hddtempAtr" checked="checked">hddtemp';

                // Draw a graph displaying the change of some attributes over time
                // for EVERY single PI
                foreach ($hostlist as $jsonFilename => $sourceUrl) {
                  $host=parse_url($sourceUrl,PHP_URL_HOST);
                  echo "<p>History for host ${host}</p>\n";
                  echo "<div class=\"toggle\">";

                  // garther all available information (history) on the Pis via its $jsonFilename
                  $datasets = getHistoryDatasets($jsonFilename);

                  // process the necessary information, add a HTML-canvas for each Information-type
                  genGraphInformation($pi_index, "voltage", $datasets);

                  genGraphInformation($pi_index, "current", $datasets);

                  genGraphInformation($pi_index, "cputemp", $datasets);

                  genGraphInformation($pi_index, "pmutemp", $datasets);

                  genGraphInformation($pi_index, "hddtemp", $datasets);
                  $pi_index++;
                  echo "</div>";
                }
            ?>
        </div>
    </div>
</body>
</html>
