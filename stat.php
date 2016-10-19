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

//
// Configuration-Options
//
$maxNumberOfHistoryEntries = 5;

$hostlist=array(  // jsonFilename => sourceUrl
  'PI0.json' => '127.0.0.1',
  'PI1.json' => '127.0.0.1',
  'PI2.json' => '127.0.0.1',
  'PI3.json' => '127.0.0.1',
  'PI4.json' => '127.0.0.1',
  'PI6.json' => '127.0.0.1',
  'PI7.json' => '127.0.0.1',
  'PI8.json' => '127.0.0.1',
  'PI9.json' => '127.0.0.1',
  'PI10.json' => '127.0.0.1',
  'PI5.json' => '127.0.0.1',
  'PI11.json' => '127.0.0.1',
  'PI12.json' => '127.0.0.1',
  'PI13.json' => '127.0.0.1',
  'PI14.json' => '127.0.0.1',
  'PI15.json' => '127.0.0.1',
  'PI16.json' => '127.0.0.1',
  'PI17.json' => '127.0.0.1',
  'PI18.json' => '127.0.0.1',
  'PI19.json' => '127.0.0.1',
  'PI20.json' => '127.0.0.2',
  'PI21.json' => '127.0.0.1'
);

// path to a JSON-file on respective hosts
// here, the JSON-file is called stat.json and resides in the document-root
// of the webservers on the repective hosts
$jsonFilePath = 'stat.json';

// hostIP => rtt[ms]
$pingResults = array();
$avgPingTime = 0;

## Set this to "secure" the history saving. This key has to be given as a parameter to save the history.
$historykey = "8A29691737D";

#Set this or your logs will fill up your disk. // TODO: REALLY?
date_default_timezone_set('Europe/Vienna');

$historyFiles = array(); // global array for storing the name of all files in the history-folder



//
// Utility-functions, used in "main-program"
//

// e.g. PI18.json' => 'http://192.168.0.28/stat.json'
function getJsonUrl($filename){
  global $hostlist;
  global $jsonFilePath;
  return "http://" . $hostlist[$filename] . "/$jsonFilePath";
}

// Requests a remote file and saves it in the history-folder under a $newFilename
// Saves a "zeroed" file, if a host is unreachable, if the file could not be
// downloaded or if the file came back empty
function downloadRemoteFile($hostIP,$newFilename){
  global $pingResults;
  global $hostlist;
  $local_file="";
  $ctx = stream_context_create(array('http' => array('timeout' => 1)));

  // try to download file from host if the host is reachable
  if($pingResults[$hostlist[$newFilename]] > 0){
    $sourceUrl = getJsonUrl($newFilename);
    $local_file=file_get_contents($sourceUrl, 0, $ctx);
  }

  if(empty($local_file)){ // represent missing JSON-data with "empty"-file
    $local_file = file_get_contents("emptyTemplate.json");
    $now = date("D M d H:i:s T Y");
    $local_file = str_replace("#DATE#", $now, $local_file);
  }

  file_put_contents($newFilename, $local_file);
  return $local_file;
}

// Add the file with given $jsonFilename to the history-folder
function addToHistory($jsonFilename, $json) {
  $new_entry = json_decode($json,true);

  if(!is_dir("history")){
    mkdir("history") or die("Cannot create history folder. Create it manually and make sure the webserver can write to it.");
  }

  $history_file=file_get_contents("history/$jsonFilename");
  if(empty($history_file)){
    $history_file=json_encode(array('history' => [$new_entry]));
  }else{
    $history = json_decode($history_file,true);
    array_push($history["history"], $new_entry);
    $history["history"] = array_slice($history["history"], -$maxNumberOfHistoryEntries);
    $history_file=json_encode($history);
  }

  // file_put_contents returns false in case of an exception (else the number of written Bytes)
  if (file_put_contents("history/${jsonFilename}", $history_file) === false) {
    exit("File $jsonFilename could not be saved in history. Please check directory permissions on directory \'history\'.");
  }
}

// returns the roundtriptime in [ms] if reachable or else -1
function ping($hostIP,$port,$timeout){
  $tB = microtime(true);
  $fP = fSockOpen($hostIP, $port, $errno, $errstr, $timeout);
  if (!$fP) return -1;
  $tA = microtime(true);
  return (($tA - $tB) * 1000);
}

// ping all hosts in hostlist, timeout=100[ms]
// store individual ping results and a average rtt in global datastructures
function pingHosts(){
  global $hostlist;
  global $pingResults;
  $avgPingTime = 0;
  $successfullPings = 0;

  foreach ($hostlist as $jsonFilename => $hostIP) {
    $pingResults[$hostIP] = ping($hostIP,80,0.1);
    $rtt = $pingResults[$hostIP];
    if(rtt > -1){
      $avgPingTime += $rtt;
      $successfullPings += 1;
    }
  }
  if($successfullPings > 0)
    $avgPingTime = $avgPingTime / $successfullPings;
}

// Pings a given $host and prints the result
function getPingResultHtml($hostIP, $port, $timeout) {
  global $pingResults;
  $rtt = $pingResults[$hostIP];
  if ($rtt < 0) {  return '<div class="grid-item down">' . $hostIP . ' DOWN </div>'; }
  else return '<div class="grid-item up">' . $hostIP . ' UP </div>';
}



// Collects the names of all files in the history-folder
/*function readHistoryFiles(){
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
    echo "<p>Error: cannot open directory './history'.</p>";
  }

  // sort entries for each $jsonFilename (in ascending order)
  foreach($historyFiles as $key => $value){
    asort($historyFiles[$key]);
  }
}*/

// returns a zero-padded version of a given $string
// just a wrapper for str_pad()
/*function pad($string){
  return str_pad($string,2,'0',STR_PAD_LEFT);
}*/

// Returns a dataset containing all (historic) statistic information of a given jsonFile-name
/*
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
}*/

// Transforms the jsonObjects respective values in units more suitable
// to be displayed in the history
/*
function conformJsonValues($jsonObject) {
  $jsonObject['cpu0freq'] = intval($jsonObject['cpu0freq'])/1000;
  $jsonObject['cpu1freq'] = intval($jsonObject['cpu1freq'])/1000;
  $jsonObject['cputemp'] = substr($jsonObject['cputemp'],0,4);
  $jsonObject['pmutemp'] = substr($jsonObject['pmutemp'],0,4);
  $jsonObject['voltage'] = intval($jsonObject['voltage'])/1000000;
  $jsonObject['current'] = intval($jsonObject['current'])/1000000;

  return $jsonObject;
}*/

// Prints a percentage-bar visualizing a given $percentage
// bar via http://www.joshuawinn.com/quick-and-simple-css-percentage-bar-using-php/
/*function percentagebar($percentage) {

  $percentage = str_replace("%", "",$percentage);
  echo "<p>".$percentage . "%</p>";
  echo "<div class=\"percentbar\" style=\"width: 100px;\">";
  echo "<div style=\"width:".round($percentage)."px;\">";
  echo "</div></div>";
}*/


// Print current information about a Pi in form of a Table
// based on the information in file $jsonFilename
/*function printStatTable($jsonObject) {
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
}*/



// Read all historic information of a certain
// attribute ($dtype) of a certain Pi ($pi_index)
// and store it in a cache, until it is used to actually
// draw the graph, also adds a HTML-canvas as base for the graph
/*function genGraphInformation($pi_index, $dtype, $datasets)
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
}*/




//
// Begin of the PHP-Scripts 'execution'
//

pingHosts(); // test if hosts reachable

// Check if the site has been called with the right params to trigger a history-save
// where history-save only means that the json-files in the same directory
// as this script get added to the history-folder
if ($_GET["action"] == "save" && $_GET["key"] == "$historykey") {
  // for every json-file of a Pi we care about
  global $hostlist;
  foreach ($hostlist as $jsonFilename => $hostIP) {
    $json = downloadRemoteFile($hostIP, $jsonFilename); // get the current json-file
    addToHistory($jsonFilename, $json); // add the downloaded file to the history
    echo "History for: ". $jsonFilename . " saved. <br />\n" ;
  }
  exit("History done.<br /> \n"); // end the script at this point
}

//
// 'Normal' - call of the script, display the overview and history
//
?>
<!DOCTYPE html>
<html>
<head>
  <title>Stats</title>
  <meta charset="utf-8"/>
  <!-- Favicons -->
  <link rel="icon" type="image/png" href="favicon-32x32.png" sizes="32x32" />
  <link rel="icon" type="image/png" href="favicon-16x16.png" sizes="16x16" />
  <!-- Stylesheets -->
  <link rel="stylesheet" type="text/css" href="inc/css/c3.css">
  <link rel="stylesheet" type="text/css" href="inc/font-awesome/css/font-awesome.min.css">
  <link rel="stylesheet" type="text/css" href="inc/css/jquery-ui.css" media="all" />
  <link rel="stylesheet" type="text/css" href="inc/css/bootstrap.min.css">
  <link rel="stylesheet" type="text/css" href="inc/css/bootstrap-theme.min.css">
  <link rel="stylesheet" type="text/css" href="inc/css/bootstrap-tagsinput.css">
  <link rel="stylesheet" type="text/css" href="inc/css/style.css" media="all" />
  <!-- JavaScript -->
  <script type="text/javascript" src="inc/js/d3.js"></script>
  <script type="text/javascript" src="inc/js/c3.js"></script>
  <script type="text/javascript" src="inc/js/jquery.js"></script>
  <script type="text/javascript" src="inc/js/jquery-ui.js"></script>
  <script type="text/javascript" src="inc/js/typeahead.js"></script>
  <script type="text/javascript" src="inc/js/bootstrap-tagsinput.js"></script>
  <script type="text/javascript" src="history.js"></script>
  <script type="text/javascript" src="stat.js"></script>
</head>

<body>
  <a id="top-of-page"></a>
  <div id="wrap" class="clearfix">
    <div id="tabs">
      <ul>
        <li><a href="#tab-status">Overview</a></li>
        <li><a href="#tab-history">History</a></li>
      </ul>
      <!-- Status-overview -->
      <div id="tab-status" class="tab-content" data-tab-index="0">
        <?php
        // draw ping-results
        echo '<div id="pingMonitor" class="grid">';
        echo '<div class="grid-item header">Ping monitor (avg ' . $avgPingTime . 'ms)</div>';
        echo "\n\t\t";
        foreach ($hostlist as $key => $hostIP) {
          echo getPingResultHtml("$hostIP",80,1);
          echo "\n\t\t";
        }
        echo "</div>\n\t\t";

        // draw individual host-status-stubs
        foreach($hostlist as $key => $hostIP){
            echo '<div id="host' . $hostIP . '" class="grid hoststatus">';
            echo '<div class="grid-item header">Host: ' . $hostIP . ' (pending)</div>';
            echo "</div>\n\t\t";
        }
        ?>
      </div>
      <!-- History -->
      <div id="tab-history" class="tab-content" data-tab-index="1">
        <div id="property-selection">
          <table>
            <tr>
              <td><button id="propertyBtn" class="tagsBtn"><i class="fa fa-cog" aria-hidden="true"></i></button></td>
              <td><input type="text" data-role="tagsinput"/></td>
            </tr>
          </table>
        </div>
        <div id="host-selection">
          <table>
            <tr>
              <td><button id="hostBtn" class="tagsBtn"><i class="fa fa-cog" aria-hidden="true"></i></button></td>
              <td><input type="text" data-role="tagsinput"/></td>
            </tr>
          </table>
        </div>
        <div id="date-range-selection">
          <label for="datefrom">From</label>
          <input type="text" id="datefrom" name="from">
          <label for="dateto">to</label>
          <input type="text" id="dateto" name="to">
        </div>
        <ul id="sortable">
          <!-- property-history-graphs are to be added here (programmatically) -->
        </ul>
        <!-- basic HTML-structure of displayed dialogs -->
        <div id="dialog-properties" title="Displayed properties:">
          <form>
            <fieldset></fieldset>
          </form>
        </div>
        <div id="dialog-host" title="Displayed hosts:">
          <form>
            <fieldset></fieldset>
          </form>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
