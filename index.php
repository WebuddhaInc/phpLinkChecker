<?php

// ------------------------------------------------
// Inspect
  function inspect(){
    echo '<pre>' . print_r( func_get_args(), true ) . '</pre>';
  }

// ------------------------------------------------
// Configuration
  define('LC_INT_LIMIT',  500);
  define('LC_EXT_LIMIT',  100);

// ------------------------------------------------
// Set Max Execution Time
  set_time_limit(0);
  if( ini_get('max_execution_time') > 0 )
    echo '<h1 style="color:red;">Failed to Set Max Execution Time to Unlimited</h1>';

// ------------------------------------------------
// Total Time Elapsed
  $stamp_start = microtime(true);

// ------------------------------------------------
// Functions
  require('link_checker.functions.inc');
  require('link_checker.errcodes.inc');

// ------------------------------------------------
// Load Variables
  $baseName = basename($_SERVER['PHP_SELF']);
  $url      = trim($_POST['url']);
  $urlInfo  = Array();
  $urlHost  = null;
  $uri      = null;

// ------------------------------------------------
// Clean / Validate URL
  if ($url && !eregi("^(http|https)://", $url))
    $url = "http://$url";
  if ($url && (eregi("^(http|https)://[0-9a-z.-@:]+", $url) || !eregi("^(http|https)://.*/.*[|><]", $url))) {
    $urlInfo = parse_url($url);
    if(!$urlInfo[port])
      switch($urlInfo[scheme]){
        case 'https':
          $urlInfo[port] = "443";
          break;
        default:
        case 'http':
          $urlInfo[port] = "80";
          break;
      }
    if(!$urlInfo[path])
      $urlInfo[path] = "/";
    if($urlInfo[query])
      $urlInfo[path] .= "?$urlInfo[query]";
    if($urlInfo[host]){
      $urlInfo['host'] = strtolower($urlInfo['host']);
      if( preg_match('/^[\w\-\d]+\.[\w\-\d]+$/',$urlInfo['host']) )
        $urlInfo['host'] = 'www.'.$urlInfo['host'];
      $urlHost = $urlInfo[host];
      $uri = $urlInfo[scheme]
           . '://'
           . $extra
           . $urlInfo[host]
           . $urlInfo[path]
           ;
      $steps = array();
      $stamp = microtime(true);
      while(count($steps) < 5){
        $output[] = 'Scanning for Valid Target: '.$uri.'<br>';
        $res = findTarget($uri);
        if( $res == $uri ) break;
        $uri = $res; $steps[] = $uri;
      }
      if( count($steps)==5 && $uri == array_pop($steps) ){
        $output[] = 'Failed to find valid target...';
        $uri = null;
      } else
        $output[] = 'Found valid target: '.$uri.'<br/>';
      $loadtime = microtime(true) - $stamp;
    }
  }

// ------------------------------------------------
// Log File
  $logActive  = true;
  $logBase    = 'log/'.$urlHost.'-'.date('Ymd-h');

// ------------------------------------------------
// Page Wrapper
  echo '<div class="mainblockcent">';

// ------------------------------------------------
// Render Form
?>
  <link href="inc/default.css" rel="stylesheet" type="text/css" />
  <script src="inc/mootools/mootools.js" type="text/javascript"></script>
  <script src="inc/default.js" type="text/javascript"></script>
  <fieldset>
    <legend>Webuddha Link Validation</legend>
    <form action="<?= $baseName ?>" name="submitform" method="POST">
      <label for="url">Enter URL:</label>
        <input name="url" id="url" size="40" value="<?= ($uri ? $uri : 'ie: http://www.website.com/') ?>" onclick="if(/^ie:/.test(this.value))this.value='';" />
      <input type="submit" valuve="  Process  " />
    </form>
  </fieldset>
<?php

// ------------------------------------------------
// Process Valid URL
  if( !is_null($uri) ) {

    // Start Scrolling
      echo "<script> winScrollTimer = setTimeout('doWinScroll();',1000); </script>\n\n";

    // Open Log
      if( $logActive ){
        $log_fh = fopen($logBase.'-log.csv','a+');
        if( !$log_fh )
          die('Failed to open '.$logBase.'-log.csv');
      }

    // Pull Inital List
    $urlList = GetPageLinks($uri);
    if(is_array($urlList)) {

      // Process Links
        $rowNumber  = 0;
        $extQueue   = Array();
        $extHistory = Array();
        $intMode    = true;
        $intQueue   = Array();
        $intHistory = Array();

      // Split Links
        $intQueue[ $uri ] = array();
        $extQueue[ $uri ] = array();
        for($i=0;$i<count($urlList);$i++){
          if( !strlen(trim($urlList[$i])) )
            continue;
          $queInfo = parse_url($urlList[$i]);
          $queInfo['host'] = strtolower($queInfo['host']);
          if( preg_match('/^[\w\-\d]+\.[\w\-\d]+$/',$queInfo['host']) )
            $queInfo['host'] = 'www.'.$queInfo['host'];
          if( $urlHost == $queInfo['host'] )
            $intQueue[ $uri ][] = $urlList[$i];
          else
            $extQueue[ $uri ][] = $urlList[$i];
        }

      // Header
        echo "<h3>Processing ".count($intQueue)." Internal and ".count($extQueue)." External Links ~ ".round($loadtime,3)."s</h3>";

      // Print Log Header
        if( $logActive )
          fputcsv( $log_fh, array(
            '#',
            '@',
            'Status',
            'Result',
            'Content-Type',
            'URL',
            'Links',
            'Time'
            ), ',', '"' );

      // Processing Table
        echo "<table summary=\"Results\" class=\"wide\">\n";
        echo "<tr>";
          echo "<th>#</th>";
          echo "<th>I</th>";
          echo "<th>E</th>";
          echo "<th>Status</th>";
          echo "<th>Result</th>";
          echo "<th>Content-Type</th>";
          echo "<th>URL</th>";
          echo "<th>Links</th>";
          echo "<th>Time</th>";
        echo "</tr>";

      while( count($intQueue) || count($extQueue) ){

        // Microtime
          $stamp = microtime(true);

        // Switch Mode if Limit Reached
          if( $intMode ){
            if( !count($intQueue) || ($rowNumber >= LC_INT_LIMIT) ){
              // Close Table
                echo "</table>\n\n";
              // Notice
                if( $rowNumber >= LC_INT_LIMIT )
                  echo "<p>Reached Maximum Internal Link Limit</p>";
              // Start External Table
                echo "<h3>Processing ".count($extQueue)." External Links found</h3>";
                print '<pre>';print $urlHost;print '</pre>';
                echo "<table summary=\"Results\" class=\"wide\">\n";
                echo "<tr>";
                  echo "<th>#</th>";
                  echo "<th>I</th>";
                  echo "<th>E</th>";
                  echo "<th>Status</th>";
                  echo "<th>Result</th>";
                  echo "<th>Content-Type</th>";
                  echo "<th>URL</th>";
                  echo "<th>Links</th>";
                  echo "<th>Time</th>";
                echo "</tr>";
              // Mode, Reset, Restart
                $intMode   = false;
                $rowNumber = 0;
                continue;
            }
          }
          elseif( !count($extQueue) || ($rowNumber >= LC_EXT_LIMIT) ){
            break;
          }

        // Link
          $queSource = null;
          if( $intMode ){
            do {
              reset($intQueue);
              $queSource = key($intQueue);
              $queLink = trim(array_shift($intQueue[$queSource]));
              if( empty($intQueue[$queSource]) ){
                unset( $intQueue[$queSource] );
              }
            } while( (empty($queLink) || in_array($queLink, $intHistory)) && !empty($intQueue) && $runaway++ < 10 );
          }
          else {
            do {
              reset($extQueue);
              $queSource = key($extQueue);
              $queLink = trim(array_shift($extQueue[$queSource]));
              if( empty($extQueue[$queSource]) ){
                unset( $extQueue[$queSource] );
              }
            } while( (empty($queLink) || in_array($queLink, $extHistory)) && !empty($extQueue) && $runaway++ < 10 );
          }
          if( !strlen($queLink) )
            continue;

        // Link Extract
          $queInfo = parse_url($queLink);
          $queInfo['host'] = strtolower($queInfo['host']);
          if( preg_match('/^[\w\-\d]+\.[\w\-\d]+$/',$queInfo['host']) )
            $queInfo['host'] = 'www.'.$queInfo['host'];

        // Check External
          if( $intMode && ($urlHost != $queInfo['host']) ){
            $extQueue[] = $queLink;
            continue;
          }

        // Check / Process
          $check    = checkUrl($queLink);
          $code     = $check['code'];
          if( $check['contentType'] )
            $contentType = ereg_replace(";.*$", "", $check['contentType']);
          else
            $contentType = 'Unknown';

        // Determine Type
          if( $urlHost == $queInfo['host'] )
            $urlPrint = '<a href="'.$queLink.'" target="_blank">'.rawurldecode($queInfo['path'].(isset($queInfo['query'])?'?'.$queInfo['query']:'')).'</a>';
          else
            $urlPrint = '<a href="'.$queLink.'" target="_blank">'.rawurldecode($queLink).'</a>';

        // Extract Links
          if( $intMode && eregi("^text/html", $contentType) && ($code==200) ){
            if( $urlHost == $queInfo['host'] ){
              $resLinks = GetPageLinks($queLink);
              if(is_array($resLinks)){
                foreach($resLinks AS $val){
                  $valInfo = parse_url($val);
                  $valInfo['host'] = strtolower($valInfo['host']);
                  if( preg_match('/^[\w\-\d]+\.[\w\-\d]+$/',$valInfo['host']) )
                    $valInfo['host'] = 'www.'.$valInfo['host'];
                  if( $urlHost == $valInfo['host'] ){
                    if( !isset($intQueue[$queLink]) ){
                      $intQueue[$queLink] = array();
                    }
                    if( ($val != $queLink) && !inExistingQueue($val, $intQueue, $intHistory) ){
                      $intQueue[$queLink][] = $val;
                    }
                  }
                  else {
                    if( !isset($extQueue[$queLink]) ){
                      $extQueue[$queLink] = array();
                    }
                    if( ($val != $queLink) && !inExistingQueue($val, $extQueue, $extHistory) ){
                      $extQueue[$queLink][] = $val;
                    }
                  }
                }
              } else
                $resLinks = null;
            }
          } else
            $resLinks = null;

        // Microtime
          $loadtime = microtime(true) - $stamp;

        // Count Remaining
          $intQueueTotal = 0;
          $extQueueTotal = 0;
          array_walk_recursive($intQueue, function($key) use (&$intQueueTotal){ $intQueueTotal++; });
          array_walk_recursive($extQueue, function($key) use (&$extQueueTotal){ $extQueueTotal++; });

        // Print to Log
          if( $logActive )
            fputcsv( $log_fh, array(
              $rowNumber,
              ( $intMode ? $intQueueTotal : $extQueueTotal ),
              $code,
              (array_key_exists($code,$errCode) ? $errCode[$code] : 'Unknown'),
              $contentType,
              $queLink,
              (is_null($resLinks)?'':count($resLinks)),
              round($loadtime,3)
              ), ',', '"' );

        // Print Row Result
          echo '
            <tr class="res_'.($code==200?'ok':'alt').' code_'.$code.'">
            <td>'.(++$rowNumber).'</td>
            <td>'.$intQueueTotal.'</td>
            <td>'.$extQueueTotal.'</td>
            <td>'.$code.'</td>
            <td>'.(array_key_exists($code,$errCode) ? $errCode[$code] : 'Unknown').'</td>
            <td>'.$contentType.'</td>
            <td>
              '.$urlPrint.'
              <br>
              <small>found on <a href="'.$queSource.'" target="_blank">'.rawurldecode($queSource).'</a></small>
            </td>
            <td>'.(is_null($resLinks)?'&nbsp;':count($resLinks)).'</td>
            <td>'.round($loadtime,3).'s</td>
          </tr>';
          flush();

        // Increment Log
          $statCode[$code]++;
          $statContentType[$contentType]++;
          if( $intMode ){
            $intHistory[] = $queLink;
          }
          else {
            $extHistory[] = $queLink;
          }

      }

      // Processing Table
        echo "</table>\n";

      // Count Remaining
        $intQueueTotal = 0;
        $extQueueTotal = 0;
        array_walk_recursive($intQueue, function($key) use (&$intQueueTotal){ $intQueueTotal++; });
        array_walk_recursive($extQueue, function($key) use (&$extQueueTotal){ $extQueueTotal++; });

      // Notice
        if( $rowNumber >= LC_EXT_LIMIT )
          echo "<p>Reached Maximum External Link Limit</p>";

      // Close Log File
        if( $logActive )
          fclose($log_fh);

      // Dump Remaining Internal Queue
        if( count($intQueue) ){
          if( $logActive ){
            $dump_fh = fopen($logBase.'-intqueue.txt','a+');
            if( !$dump_fh )
              die('Failed to open '.$logBase.'-intqueue.txt');
            foreach(array_keys($intQueue) AS $key)
              for($i=0;$i<count($intQueue[$key]);$i++)
                fputs( $dump_fh, $key.' - '.$intQueue[$key][$i]."\n" );
            fclose( $dump_fh );
          }
        }

      // Dump Remaining External Queue
        if( count($extQueue) ){
          if( $logActive ){
            $dump_fh = fopen($logBase.'-extqueue.txt','a+');
            if( !$dump_fh )
              die('Failed to open '.$logBase.'-extqueue.txt');
            foreach(array_keys($extQueue) AS $key)
              for($i=0;$i<count($extQueue[$key]);$i++)
                fputs( $dump_fh, $key.' - '.$extQueue[$key][$i]."\n" );
            fclose( $dump_fh );
          }
        }

      // Dump Remaining Queue
        if( $logActive ){
          $report_fh = fopen($logBase.'-report.html','a+');
          if( !$report_fh )
            die('Failed to open '.$logBase.'-report.html');
        }

      // Total Time Elapsed
        ob_start();
        echo "<p><b>Total Time Elapsed:</b> ".round(microtime(true)-$stamp_start,3)."s</p>";
        $html = ob_get_clean();
        if( $logActive )
          fputs( $report_fh, $html."\n" );
        echo $html;

      // Print Result Summary
        ob_start();
        echo "<p><b>Total Links Found:</b></p>";
        echo "<table summary=\"Result Totals\" class=\"thin\">";
        echo "<tr><td>Total Links:</td><td>".(count($intHistory) + $intQueueTotal + $extQueueTotal)."</td></tr>\n";
        echo "<tr><td>Local Links:</td><td>".(count($intHistory) + $intQueueTotal)."</td></tr>\n";
        echo "<tr><td>Local Processed:</td><td>".(count($intHistory))."</td></tr>\n";
        echo "<tr><td>Local Skipped:</td><td>".($intQueueTotal)."</td></tr>\n";
        echo "<tr><td>External Links:</td><td>".(count($extHistory) + $extQueueTotal)."</td></tr>\n";
        echo "<tr><td>External Processed:</td><td>".(count($extHistory))."</td></tr>\n";
        echo "<tr><td>External Skipped:</td><td>".($extQueueTotal)."</td></tr>\n";
        echo "</table>";
        $html = ob_get_clean();
        if( $logActive )
          fputs( $report_fh, $html."\n" );
        echo $html;

      // Print Result Summary
        $totalChecked = count($intHistory)+count($extHistory);
        ob_start();
        if(count($statCode) >= 1) {
          while(list($key, $value) = each($statCode)) {
            $percent = ereg_replace('(\.)?0+$', '', number_format(($value*100/$totalChecked),2,".",""));
            $space = "";
            for($i=0; $i<$percent/3; $i++)
              $space .= "&nbsp;";
            $print_statsCode .= "<tr><td>$errCode[$key]</td><td>$value</td><td>&nbsp;$percent%&nbsp;</td></tr>\n";
          }
          echo "<p><b>Response Codes:</b></p>";
          echo "<table summary=\"Response Codes\" class=\"thin\">";
          echo "<tr><th>Status&nbsp;</th><th>Number&nbsp;</th><th>Percent&nbsp;</th></tr>";
          echo $print_statsCode;
          echo "</table>";
        }
        $html = ob_get_clean();
        if( $logActive )
          fputs( $report_fh, $html."\n" );
        echo $html;

      // Print Content-Type Summary
        ob_start();
        if(count($statContentType) >= 1) {
          while(list($key, $value) = each($statContentType)) {
            $percent = ereg_replace('(\.)?0+$', '', number_format(($value*100/$totalChecked),2,".",""));
            $space = "";
            for($i=0; $i<$percent/3; $i++)
              $space .= "&nbsp;";
            $print_statsContent .= "<tr><td>$key</td><td>$value</td><td>&nbsp;$percent%&nbsp;</td></tr>\n";
          }
          echo "<p><b>Content-Type:</b></p>";
          echo "<table summary=\"Content-Type\" class=\"thin\">";
          echo "<tr><th>Content-Type&nbsp;</th><th>Number&nbsp;</th><th>Percent</th></tr>";
          echo $print_statsContent;
          echo "</table>";
        }
        $html = ob_get_clean();
        if( $logActive )
          fputs( $report_fh, $html."\n" );
        echo $html;

      // Close Report File
        if( $logActive )
          fclose( $report_fh );

      // Close Report File
        echo "<script>clearTimeout(winScrollTimer);</script>\n\n";

    } else
      echo "<p><b>I didn't find any links.</b></p>";

  } else
    echo "<p><b>Please Enter a Link to Check.</b></p>";

// ------------------------------------------------
// Print Error
  if ($url && !$uri)
    echo "<p><b>Invalid adress.</b></p>";

// ------------------------------------------------
// Close Wrapper
  echo '</div>';

// ---- END ---------------------------------------

  function inExistingQueue( $link, &$active, &$history ){
    foreach( array_keys($active) AS $key ){
      if( in_array($link, $active[$key]) ){
        return true;
      }
    }
    if( in_array($link, $history) ){
      return true;
    }
    return false;
  }