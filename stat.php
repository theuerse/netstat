<?php
/*
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

$hostlist=array(
//                'example1.org.json' => 'http://example1.org/stat.json',
//                'example2.nl.json' => 'http://example2.nl/stat.json',
//                'special1.network.json' => 'http://special1.network.eu:8080/stat.json',
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
//                  'github.com',
//                  'google.nl',
//                  'tweakers.net',
//                  'jupiterbroadcasting.com',
//                  'lowendtalk.com',
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

#the below values set the threshold before a value gets shown in bold on the page.
# Max updates available
$maxupdates = "10";
# Max users concurrently logged in
$maxusers = "3";
# Max load.
$maxload = "2";
# Max disk usage (in percent)
$maxdisk = "75";
# Max RAM usage (in percent)
$maxram = "75";

#Set this or your logs will fill up your disk.
date_default_timezone_set('Europe/Vienna');

## END OF CONFIG



function historystat($bestand,$host) {
    $files=array();
    $dataset = array();
    if ($handle = opendir('./history/')) {
        while (false !== ($entry = readdir($handle))) {
            $files[]=$entry;
        }
        closedir($handle);
    } else {
        echo "Error: cannot open direcotry './history' for file $bestand from host $host.";
    }
    rsort($files);
    $i=0;
    foreach ($files as $key => $value) {
        $filename = explode(".", $value);
        $amount = count($filename);
        $timestamp = end($filename);
        $amount1 = intval($amount)-1;
        $bestandnaam = str_replace(",",".",implode(",",array_slice($filename, 0, $amount1)));
        if($bestandnaam == $bestand) {
            $jsonfile = "./history/" . $value;
            if ($jsonopen = file_get_contents($jsonfile)) {
               if (json_decode($jsonopen, true)) {
                $filedate = date("d.m.Y - H:i:s", $timestamp);
                //echo "<tr><th>Date:</td><th colspan = \"8\">$filedate</td></tr>\n";
                $dataset[$i++] = linestat_history($jsonfile,$host);
            } else {
                echo "Cannot decode json file $jsonfile.";
            }
        } else {
           //echo "Cannot open json file $jsonfile.";
       }
   }
}
	return $dataset;
}

function shortstat($bestand,$host) {

    echo "<table class=\"striped\">";
    linestat($bestand,$host);
    echo "</table><br />";
}

function percentagebar($percentage) {

    $percentage = str_replace("%", "",$percentage); 
    echo "<center>".$percentage . "%</center>";
    echo "<div class=\"percentbar\" style=\"width: 100px;\">";
    echo "<div style=\"width:".round($percentage)."px;\">";
    echo "</div></div>";
}

function savefile($bestand,$naam){

    $curdir=getcwd();
    if(!is_dir("{$curdir}/history")){
        mkdir("${curdir}/history") or die("Cannot create history folder. Create it manually and make sure the webserver can write to it.");
    } else {
        $local_file=file_get_contents($bestand);
        $saved_local_file=file_put_contents("${curdir}/$naam", $local_file);        
    }

}

function savehistory($naam) {
    $curdir=getcwd();
    $DATETIME=date('U');
    if(!is_dir("{$curdir}/history")){
        mkdir("${curdir}/history") or die("Cannot create history folder. Create it manually and make sure the webserver can write to it.");
    }
    $local_file=file_get_contents($naam); 
    
    if (!file_put_contents("history/${naam}.${DATETIME}", $local_file)) {
        die("File $naam could not be saved in history. Please check directory permissions on directory \'history\'.");
    }

    

}

function linestat_history($bestand, $host) {
 global $maxusers;
    global $maxupdates;
    global $maxload;
    global $maxdisk;
    global $maxram;

    if ($file = file_get_contents($bestand)) {
        if ($json_a = json_decode($file, true)) {
            $closed=0;
            $havestat = 0;
            if(is_array($json_a)) {
                
               
                $json_a['cpu0freq'] = intval($json_a['cpu0freq'])/1000;
                $json_a['cpu1freq'] = intval($json_a['cpu1freq'])/1000;
                $json_a['cputemp'] = substr($json_a['cputemp'],0,4);
                $json_a['pmutemp'] = substr($json_a['pmutemp'],0,4);
                $json_a['voltage'] = intval($json_a['voltage'])/1000000;
                $json_a['current'] = intval($json_a['current'])/1000000;
                

		return $json_a;
}
} else {
    echo "Error decoding JSON stat file for host $host, $json_a";
}
} else  {
    echo "Error while getting stats for host $host from file $bestand";
}
}

function linestat($bestand,$host) {
    global $maxusers;
    global $maxupdates;
    global $maxload;
    global $maxdisk;
    global $maxram;

    if ($file = file_get_contents($bestand)) {
        if ($json_a = json_decode($file, true)) {
            $closed=0;
            $havestat = 0;
            if(is_array($json_a)) {
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

                    <td><?php echo str_replace(",", "",$json_a['Uptime']); ?></td>
                    <td><?php
                    foreach ($json_a['Services'] as $service => $status) {
                        if($status == "running") {
                            echo '<font color="green">' . $service . '</font> up. <br /> ';
                        } elseif ($status == "not running") {
                            echo '<font color="red">' . $service . '</font> <b>down.</b> <br /> ';
                        }
                    }
                    ?>
                </td>
                <?php
                if (floatval(str_replace(",", "",$json_a['Load'])) > $maxload) {
                    echo "<td><strong>".round(floatval(str_replace(",", "",$json_a['Load'])),3)."</strong></td>";
                } else {
                    echo "<td>".round(floatval(str_replace(",", "",$json_a['Load'])),3)."</td>";
                }
                
                if (intval($json_a['Users logged on']) > $maxusers) {
                    echo "<td><strong>".$json_a['Users logged on']."</strong></td>";
                } else {
                   echo "<td>".$json_a['Users logged on']."</td>"; 
                }

		echo "<td>".(intval($json_a['cpu0freq'])/1000)."</td>";
		echo "<td>".(intval($json_a['cpu1freq'])/1000)."</td>";
		echo "<td>".substr($json_a['cputemp'],0,4)."</td>";
		echo "<td>".substr($json_a['pmutemp'],0,4)."</td>";
		echo "<td>".$json_a['hddtemp']."</td>";
		echo "<td>".(intval($json_a['voltage'])/1000000)."</td>";	
		echo "<td>".(intval($json_a['current'])/1000000)."</td>";
		$json_a['cpu0freq'] = intval($json_a['cpu0freq'])/1000;
		$json_a['cpu1freq'] = intval($json_a['cpu1freq'])/1000;
		$json_a['cputemp'] = substr($json_a['cputemp'],0,4);
		$json_a['pmutemp'] = substr($json_a['pmutemp'],0,4);
		$json_a['voltage'] = intval($json_a['voltage'])/1000000;
		$json_a['current'] = intval($json_a['current'])/1000000;
                ?>
  
                <td><?php
                percentagebar($json_a['Disk']['percentage']);
                echo "<br />";
                echo "T: " . $json_a['Disk']['total'] . " <br /> "; 

                if (intval(str_replace("%", "",$json_a['Disk']['percentage'])) > $maxdisk ) {
                    echo "<strong>U: " . $json_a['Disk']['used'] . "</strong> <br /> ";
                    echo "<strong>F: " . $json_a['Disk']['free'] . "</strong>";
                } else {
                    echo "U: " . $json_a['Disk']['used'] . " <br /> ";
                    echo "F: " . $json_a['Disk']['free'];
                }
                ?>
            </td>    
            <td><?php
            $used_ram = $json_a['Total RAM'] - $json_a['Free RAM'];
            $value = $used_ram;
            $max = $json_a['Total RAM'];
            $scale = 1.0;
            if (!empty($max)) {
                $percent = ($value * 100) / $max;
            } else {
                $percent = 0;
            }
            if ($percent > 100) {
                $percent = 100;
            }
            percentagebar(round($percent * $scale));
            echo "<br />";
            echo "T: " . $json_a['Total RAM'] . " MB <br /> ";
            if (intval(str_replace("%", "",round($percent * $scale))) > $maxram ) {
                echo "<strong>U: " . $used_ram . " MB </strong><br /> ";
                echo "<strong>F: " . $json_a['Free RAM'] . " MB</strong>";
            } else {
                echo "U: " . $used_ram . " MB <br /> ";
                echo "F: " . $json_a['Free RAM'] . " MB";
            }
            ?>
        </td>
        <td><?php
        $rxmb=round((($json_a['rxbytes'] / 1024) / 1024));
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
    <td><?php
    $txmb=round((($json_a['txbytes'] / 1024) / 1024));
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
return $json_a;
}
} else {
    echo "Error decoding JSON stat file for host $host, $json_a";
}
} else  {
    echo "Error while getting stats for host $host from file $bestand";
}
}

function ping($host, $port, $timeout) { 
  $tB = microtime(true); 
  $fP = fSockOpen($host, $port, $errno, $errstr, $timeout); 
  if (!$fP) {  return '<font color="red">' . $host . ' DOWN from here. </font>'; } 
  $tA = microtime(true); 
  return '<font color="green">' . $host . ' ' . number_format((($tA - $tB) * 1000),2).' ms UP</font>';
}

function dosomething($bestand,$host,$actie){
    if(!empty($bestand) && !empty($host) && !empty($actie)) {
        # this function should be called per item on a foreach loop.
        switch ($actie) {
            case 'shortstat':
            savefile($host,$bestand);
            $parsed_host=parse_url($host,PHP_URL_HOST);
            shortstat($bestand,$parsed_host);
            break;
            case 'historystat':
            echo "<table>";
            savefile($host,$bestand);
            $parsed_host=parse_url($host,PHP_URL_HOST);
            $datasets = historystat($bestand,$parsed_host);
            echo "</table>";
	    return $datasets;
            break;
        }
    }   
}

if ($_GET["action"] == "save" && $_GET["key"] == "$historykey") {
    foreach ($hostlist as $key => $value) {
        savefile($value, $key);
	savehistory($key);
        echo "History for: ".$key . " saved. <br />\n" ;
    }
    die("History done.<br /> \n");

}


function genGraph($pi_index, $dtype, $datasets)
{
                    $labels = "";
                    $i = 0;
                    $data ="";
                    $labls ="";
                    $rdatasets = array_reverse($datasets);
                    for($i=0; $i<count($rdatasets);$i++)
                    {
                        $dp = $rdatasets[$i];
                        $mydate = date_parse($dp['date']);
                        if($i == count($datasets)-1) $labels = $labels."'".$mydate['day']."-".$mydate['month']."-".$mydate['hour'].":".$mydate['minute']."'";
                        else
                                $labels = $labels."'".$mydate['day']."-".$mydate['month']."-".$mydate['hour'].":".$mydate['minute']."',";
                        if($i == count($datasets)-1) $data = $data."".$dp["".$dtype];
                        else
                                $data = $data."".floatval($dp["".$dtype]).",";
                    }
		    $SI ="N/A";
		    
		    if(strcmp($dtype,'voltage')==0) $SI = "V";
		    if(strcmp($dtype,'current')==0) $SI = "A";
                    if(strcmp($dtype,'cputemp')==0) $SI = "C";
	            if(strcmp($dtype,'pmutemp')==0) $SI = "C";
                    if(strcmp($dtype,'hddtemp')==0) $SI = "C";
		    if(strcmp($dtype,'Load')==0) $SI = "%";
		    if(strcmp($dtype,'cpu0freq')==0) $SI = "MHz";
		    if(strcmp($dtype,'cpu1freq')==0) $SI = "MHz";

                    echo "<strong>".$dtype." [".$SI."]</strong>:<br><canvas id='$dtype-$pi_index' width='1000' height='400'></canvas><script>

var options_".$dtype."_".$pi_index." = {

    ///Boolean - Whether grid lines are shown across the chart
    scaleShowGridLines : true,

    //String - Colour of the grid lines
    scaleGridLineColor : 'rgba(0,0,0,.05)',

    //Number - Width of the grid lines
    scaleGridLineWidth : 1,

    // Set the start value
    scaleBeginAtZero: true,

    //Boolean - Whether to show horizontal lines (except X axis)
    scaleShowHorizontalLines: true,

    //Boolean - Whether to show vertical lines (except Y axis)
    scaleShowVerticalLines: true,

    //Boolean - Whether the line is curved between points
    bezierCurve : false,

    //Number - Tension of the bezier curve between points
    bezierCurveTension : 0.4,

    //Boolean - Whether to show a dot for each point
    pointDot : true,

       
    pointDotRadius : 4,

    //Number - Pixel width of point dot stroke
    pointDotStrokeWidth : 1,

    //Number - amount extra to add to the radius to cater for hit detection outside the drawn point
    pointHitDetectionRadius : 20,

    //Boolean - Whether to show a stroke for datasets
    datasetStroke : true,

    //Number - Pixel width of dataset stroke
    datasetStrokeWidth : 2,

    //Boolean - Whether to fill the dataset with a colour
    datasetFill : true,

    //String - A legend template
    legendTemplate : '<ul class=\'<%=name.toLowerCase()%>-legend\'><% for (var i=0; i<datasets.length; i++){%><li><span style=\'background-color:<%=datasets[i].strokeColor%>\'></span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>'

};

var data_".$dtype."_".$pi_index." = {
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
        }    ]
};
var $dtype".$pi_index."_chart=new Chart(document.getElementById('$dtype-$pi_index').getContext('2d')).Line(data_".$dtype."_".$pi_index.", options_".$dtype."_".$pi_index."); 
</script>";

                                                       


}

?>
<html>
<head>
    <title>Stats</title>
    <!-- bar via http://www.joshuawinn.com/quick-and-simple-css-percentage-bar-using-php/ -->
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.min.js"></script>
    <!--[if lt IE 9]><script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
    <script type="text/javascript" src="inc/js/prettify.js"></script>                                   <!-- PRETTIFY -->
    <script type="text/javascript" src="inc/js/kickstart.js"></script>                                  <!-- KICKSTART -->
    <script type="text/javascript" src="Chart.js"></script>						<!-- Charts -->
    <link rel="stylesheet" type="text/css" href="inc/css/kickstart.css" media="all" />                  <!-- KICKSTART -->
    <link rel="stylesheet" type="text/css" href="inc/css/style.css" media="all" />                      <!-- CUSTOM STYLES -->
    <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1"> 
    <script type="text/javascript">
    <!--
    $(document).ready(function() {
        var showText="Show";
        var hideText="Hide";
        $(".toggle").prev().append(' (<a href="#" class="toggleLink">'+showText+'</a>)');
        $('.toggle').hide();
        $('a.toggleLink').click(function() {
            if ($(this).html()==showText) {
                $(this).html(hideText);
            }
            else {
                $(this).html(showText);
            }
            $(this).parent().next('.toggle').toggle('slow');
            return false;
        });
    });
        //-->
        </script>
        <style type="text/css">
        .percentbar { background:#CCCCCC; border:1px solid #666666; height:10px; }
        .percentbar div { background: #28B8C0; height: 10px; }
        </style>
    </head>
    <body><a id="top-of-page"></a><div id="wrap" class="clearfix">
        <div class="col_12">
            <ul class="tabs left">
                <li><a href="#tabc1">Overview</a></li>
                <li><a href="#tabc2">History</a></li>
            </ul>
            <div id="tabc1" class="tab-content">
                <?php 
                echo "<i>Ping monitor:</i>";
                foreach ($pinglist as $key => $value) {
                    echo ping("$value",80,5) . ", ";
                }
                ?>
                <h4>Server Status</h4>
                <?php
                foreach ($hostlist as $key => $value) {
                    $host=parse_url($value,PHP_URL_HOST);
                    echo "<h5>Host: ${host}</h6>";
                    dosomething($key,$value,"shortstat");
                    echo "<hr class=\'alt1\' />";
                }
                ?>
            </div>
            <div id="tabc2" class="tab-content"> 
                <?php
		$pi_index = 0;
                foreach ($hostlist as $key => $value) {
                    $host=parse_url($value,PHP_URL_HOST);
                    echo "<p>History for host ${host}</p>\n";
                    echo "<div class=\"toggle\">";
                    $datasets = dosomething($key,$value,"historystat");
		    genGraph($pi_index, "voltage", $datasets);
		    echo "<br>";
		    genGraph($pi_index, "current", $datasets);
                    echo "<br>";
                    genGraph($pi_index, "cputemp", $datasets);
                    echo "<br>";
                    genGraph($pi_index, "pmutemp", $datasets);
                    echo "<br>";
                    genGraph($pi_index, "hddtemp", $datasets);
//                    echo "<br>";
//                    genGraph($pi_index, "cpu0freq", $datasets);
//                    echo "<br>";
//                    genGraph($pi_index, "cpu1freq", $datasets);
                    $pi_index++;

                    echo "</div>";
                }
                ?>
            </div>
        </div>
    </body>
    </html>
