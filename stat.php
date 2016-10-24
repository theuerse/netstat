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
  'PI0.json' => '127.0.0.10',
  'PI1.json' => '127.0.0.11',
  'PI2.json' => '127.0.0.12',
  'PI3.json' => '127.0.0.13',
  'PI4.json' => '127.0.0.14',

  'PI6.json' => '127.0.0.15',
  'PI7.json' => '127.0.0.16',
  'PI8.json' => '127.0.0.17',
  'PI9.json' => '127.0.0.18',
  'PI10.json' => '127.0.0.19',
  'PI5.json' => '127.0.0.20',
  'PI11.json' => '127.0.0.21',
  'PI12.json' => '127.0.0.22',
  'PI13.json' => '127.0.0.23',
  'PI14.json' => '127.0.0.24',
  'PI15.json' => '127.0.0.25',
  'PI16.json' => '127.0.0.26',
  'PI17.json' => '127.0.0.27',
  'PI18.json' => '127.0.0.28',
  'PI19.json' => '127.0.0.29'
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

// Requests a remote file and saves under a $newFilename
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
  global $maxNumberOfHistoryEntries;
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
}else { // normal call
  foreach ($hostlist as $jsonFilename => $hostIP) {
  //  downloadRemoteFile($hostIP, $jsonFilename); // get the current json-file
  }
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
  <script type="text/javascript" src="inc/js/masonry.pkgd.min.js"></script>
  <script type="text/javascript" src="history.js"></script>
  <script type="text/javascript" src="stat.js"></script>
</head>

<body>
  <div id="javaScriptAlert"class="alert alert-danger" role="alert">
  This Page needs JavaScript in order to work, please enable it.
  </div>
  <div id="progressbar"><div class="progress-label">Loading history</div></div>
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
        echo '<div class="header">Ping monitor (avg ' . $avgPingTime . 'ms)</div>';
        echo "<div>\n\t\t";
        foreach ($hostlist as $key => $hostIP) {
          echo getPingResultHtml("$hostIP",80,1);
          echo "\n\t\t";
        }
        echo "</div></div>\n\t\t";

        // draw individual host-status-stubs
        foreach($hostlist as $key => $hostIP){
            echo '<div id="host' . $hostIP . '" class="grid hoststatus">';
            echo '<div class="grid-item header">Host: ' . $hostIP . ' (pending)</div>';
              echo '<div class="row">';

                echo '<div class="col-md-6">';
                  echo '<div class="row">';
                    echo '<div class="col-md-3 grid-item services"></div>';
                    echo '<div class="col-md-3 grid-item misc"></div>';
                    echo '<div class="col-md-3 grid-item cpufreq"></div>';
                    echo '<div class="col-md-3 grid-item temperatures"></div>';
                  echo '</div>';
                echo '</div>';

                echo '<div class="col-md-6">';
                  echo '<div class="row">';
                    echo '<div class="col-md-3 grid-item power"></div>';
                    echo '<div class="col-md-3 grid-item ssd"></div>';
                    echo '<div class="col-md-3 grid-item ram"></div>';
                    echo '<div class="col-md-3 grid-item traffic"></div>';
                  echo '</div>';
                echo '</div>';

              echo '</div>';
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
