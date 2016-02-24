<?php

// ------------------------------------------------
// Definitions
  define('LC_USERAGENT',  false);
  define('LC_REM_QUERY',  false);
  define('LC_RUN_OTHER',  false);

// ------------------------------------------------
// Get Links from URL
  function IsSameHost($A, $B) {
    $infoA = $infoB = Array();
    if( preg_match('/\//',$A) ) $infoA = parse_url($A); else $infoA['host'] = $A;
    if( preg_match('/\//',$B) ) $infoB = parse_url($B); else $infoB['host'] = $B;
    $splitA = split('\.', $infoA['host']); if(strtolower($splitA[0])=='www') array_shift($splitA); $infoA['host']=implode('.',$splitA);
    $splitB = split('\.', $infoB['host']); if(strtolower($splitB[0])=='www') array_shift($splitB); $infoB['host']=implode('.',$splitB);
    return strtolower($infoA['host']) == strtolower($infoB['host']);
  }

// ------------------------------------------------
// Get Links from URL
  function GetPageLinks($url, $validMeta) {
    $text = implode("", file($url));
    $text = eregi_replace("<!--([^-]|-[^-]|--[^>])*-->","", $text);
    $text = preg_replace('#<script>(.*?)</script>#is', '', $text);
    $text = preg_replace('#<script type="text/javascript">(.*?)</script>#is', '', $text);

    $baseUrl  = null;
    $baseInfo = Array();
    preg_match('/\<BASE(.*?)\>/i',$text,$matches);
    if( count($matches) ){
      eregi("[:space:]*(href|src)[:space:]*=[:space:]*([^ >]+)", $text, $regs);
      $tmpUrl = $regs[2];
      $tmpUrl = preg_replace('/^.*?[\"\']+/', '', $tmpUrl);
      if( preg_match('/^(.*?)[\"\']/', $tmpUrl, $matches) )
        $tmpUrl = $matches[1];
      $tmpUrl = preg_replace("/(\s.+)/" , "" , $tmpUrl);
      $baseInfo = parse_url($tmpUrl);
      if( $baseInfo['host'] ){
        $baseUrl = $tmpUrl;
        if( !eregi("/$",$baseUrl) )
          $baseUrl .= '/';
      }
    }

    $urlList = Array();
    while( eregi("[:space:]*(href|src)[:space:]*=[:space:]*([^ >]+)", $text, $regs) ){
      $tmpUrl = $regs[2];
      $tmpUrl = preg_replace('/^.*?[\"\']+/', '', $tmpUrl);
      if( preg_match('/^(.*?)[\"\']/', $tmpUrl, $matches) )
        $tmpUrl = $matches[1];
      $tmpUrl = preg_replace("/(\s.+)/" , "" , $tmpUrl);
      if( LC_REM_QUERY === true )
        $tmpUrl = ereg_replace("\?.*$", "", $tmpUrl);
      else
        $tmpUrl = ereg_replace("#.*$", "", $tmpUrl);
      $text = substr($text, strpos($text, $regs[1]) + strlen($regs[1]));
      if( !is_null($baseUrl) && !eregi("^/",$tmpUrl) && !eregi("^(http|https|mailto|news|javascript|ftp|tel|callto):", $tmpUrl) )
        $urlList[] = $baseUrl.$tmpUrl;
      else
        $urlList[] = $tmpUrl;
    }

    $retList = Array();
    $urlList = sortArray($urlList);
    for($i=0; $i<count($urlList); $i++) {
      $temp = "";
      if(!eregi("^(mailto|news|javascript|ftp|tel|callto)+:(//)?", $urlList[$i])) {
        if(!eregi("^(http|https)://", $urlList[$i]))
          $temp = buildUrl($url, $urlList[$i]);
        else
          $temp = $urlList[$i];
      } elseif( LC_RUN_OTHER === true )
        $temp = $urlList[$i];
      if ($temp && $temp != $url)
        $retList[] = $temp;
    }

    $retList = array_filter( $retList, function($key) use (&$validMeta){
      $url = parse_url( $key );
      $ext = preg_replace('/^.*\.([A-Za-z0-9]+)($|\?.*$)/', '$1', $url['path']);
      if( $url['path'] == $ext ){
        return true;
      }
      $ext = strtolower($ext);

      // css
        if( in_array($ext, array('css')) && !in_array('css', $validMeta) ){
          return false;
        }

      // script
        if( in_array($ext, array('js')) && !in_array('script', $validMeta) ){
          return false;
        }

      // image
        if( in_array($ext, array('ico','bmp','gif','jpg','png','psd','pspimage','thm','tif','yuv','svg')) && !in_array('image', $validMeta) ){
          return false;
        }

      // media
        if( in_array($ext, array(
              '3g2','3gp','asf','avi','drc','f4a','f4b','f4v','flv','gif','gifv','m2v','m4p','m4v','mkv','mng','mov','mp2',
              'mp4','mpe','mpeg','mpg','mpv','mxf','nsv','ogg','ogv','qt','rm','rmvb','roq','svi','vob','vob','webm','wmv','yuv',
              'aif','iff','m3u','m4a','mid','mp3','mpa','ra','wav','wma','ttf'
              )) && !in_array('media', $validMeta) ){
          return false;
        }

      // default
        return true;

      /*
      foreach( $validMeta AS $validMetaKey ){
        switch( $validMetaKey ){
          case 'css':
            if( in_array($ext, array('css')) ){
              return true;
            }
            break;
          case 'script':
            if( in_array($ext, array('js')) ){
              return true;
            }
            break;
          case 'image':
            if( in_array($ext, array('ico','bmp','gif','jpg','png','psd','pspimage','thm','tif','yuv','svg')) ){
              return true;
            }
            break;
          case 'media':
            if( in_array($ext, array(
              '3g2','3gp','asf','avi','drc','f4a','f4b','f4v','flv','gif','gifv','m2v','m4p','m4v','mkv','mng','mov','mp2',
              'mp4','mpe','mpeg','mpg','mpv','mxf','nsv','ogg','ogv','qt','rm','rmvb','roq','svi','vob','vob','webm','wmv','yuv',
              'aif','iff','m3u','m4a','mid','mp3','mpa','ra','wav','wma','ttf'
              )) ){
              return true;
            }
            break;
        }
      }
      return false;
      */

    });

    $retList = array_values($retList);
    if (count($retList) != 0)
      return $retList;

    return false;
  }

// ------------------------------------------------
// Build Clean URL
  function buildUrl($base,$path) {
    // echo $base.' -> '.$path .'<br>';
    if( preg_match('/^([a-z]\:\//[a-z0-9\.\-]+\/).+/',$base) )
      $base = ereg_replace("(.*/)[^/]*","\\1", $base);
    else if( preg_match('/^([a-z]\:\//[a-z0-9\.\-]+\/)/',$base) )
      $base = preg_replace('/^([a-z]\:\//[a-z0-9\.\-]+)\//','$1',$base);
    $path = ereg_replace("^(\.){1}/", "", $path);
    // echo $base.' -> '.$path .'<br>';
    if (ereg("^/", $path)) {
      $base = ereg_replace("^(http://([^/]+))/{1}(.*)", "\\1", $base);
    }
    if( strpos($path, '//') === 0 ){
      return preg_replace('/^(\w+\:).*$/','$1',$base).$path;
    }
    // echo $base.$path .'<br>';
    return $base.$path;
  }

// ------------------------------------------------
// Sort Array
  function sortArray($arr) {
    if (count($arr) == 0)
      return $arr;
    reset($arr);
    while (list($key,$value) = each($arr))
      $newarr[$value] = $key;
    reset($newarr);
    while (list($key,$value) = each($newarr))
      $sortedarr[] = $key;
    return $sortedarr;
  }

// ------------------------------------------------
// First
  function findTarget($url) {
    $urlArray = parse_url($url);
    if (!$urlArray[port])
      switch($urlArray[scheme]){
        case 'https':
          $urlArray[port] = "443";
          break;
        default:
        case 'http':
          $urlArray[port] = "80";
          break;
      }
    if (!$urlArray[path])
      $urlArray[path] = "/";
    if ($urlArray[query])
      $urlArray[path] .= "?$urlArray[query]";
    $sock = fsockopen($urlArray[host], $urlArray[port]);
    if($sock){
      $dump .= "GET ".$urlArray[path].' '.strtoupper($urlArray[scheme])."/1.1\r\n";
      $dump .= "User-Agent: ".( LC_USERAGENT === false ? $_SERVER['HTTP_USER_AGENT'] : LC_USERAGENT )."\r\n";
      $dump .= "Host: $urlArray[host]\r\n";
      $dump .= "Connection: close\r\n";
      $dump .= "Connection: close\r\n\r\n";
      fputs($sock, $dump);
      while($str = fgets($sock, 1024))
        $headers[] = $str;
      // print '<pre>'; print($str); print($dump); die();
      fclose($sock);
      flush();
      for($i=0; $i<count($headers); $i++) {
        if (eregi("^HTTP/[0-9]+\.[0-9]+ 200", $headers[$i]))
          $location = $url;
        if (eregi("^Location: ", $headers[$i]))
          $location = eregi_replace("^Location:( )?", "", $headers[$i]);
      }
    }
    $location = trim($location);
    return $location;
  }

// ------------------------------------------------
//
  function checkUrl($url) {
    $urlInfo = Array();
    $urlInfo['stamp'] = microtime(true);
    if (!eregi("^(http|https)://", $url)) {
      if (eregi("^mailto:", $url)) {
        $url = trim(eregi_replace("^mailto:(.+)", "\\1", $url));
        list($brugernavn, $host) = split("@", $url);
        $dnsCheck = checkdnsrr($host,"MX");
        if ($dnsCheck)
          $urlInfo['code'] = "OK";
        else
          $urlInfo['code'] = "ERROR";
      } else
        $urlInfo['code'] = "N/A";
    } else {
      $urlArray = parse_url($url);
      if (!$urlArray[port])
        $urlArray[port] = "80";
      if (!$urlArray[path])
        $urlArray[path] = "/";
      $sock = fsockopen($urlArray[host], $urlArray[port], $errnum, $errstr, 10);
      if (!$sock)
        $urlInfo['code'] = "Død";
      else {
        $dump .= "HEAD $urlArray[path] HTTP/1.1\r\n";
        $dump .= "User-Agent: ".( LC_USERAGENT === false ? $_SERVER['HTTP_USER_AGENT'] : LC_USERAGENT )."\r\n";
        $dump .= "Host: $urlArray[host]\r\n";
        $dump .= "Connection: close\r\n";
        $dump .= "Connection: close\r\n\r\n";
        fputs($sock, $dump);
        while($str = fgets($sock, 1024)) {
          if (eregi("^http/[0-9]+.[0-9]+ ([0-9]{3}) [a-z ]*", $str))
            $urlInfo['code'] = trim(eregi_replace("^http/[0-9]+.[0-9]+ ([0-9]{3}) [a-z ]*", "\\1", $str));
          if (eregi("^Last-Modified: ", $str))
            $urlInfo['lastModified'] = gmdate('Y-m-d H:i:s', strtotime(trim(eregi_replace("^Last-Modified: ", "", $str))));
          if (eregi("^Content-Type: ", $str))
            $urlInfo['contentType'] = trim(eregi_replace("^Content-Type: ", "", $str));
        }
        fclose($sock);
        flush();
      }
    }
    $urlInfo['loadtime'] = microtime(true) - $urlInfo['stamp'];
    return $urlInfo;
  }

// ------------------------------------------------
// Error Handler
  function _lc_onError($num,$msg,$file,$line,$context){
    $fatal = Array(E_ERROR,E_USER_ERROR,E_ALL);
    if( in_array($num, $fatal) ){
      echo "<h3 class=error>An Error has Occurred...</h3>\n\n";
      echo "<ul>";
        echo "<li><b>$num:</b> $msg</li>\n";
        echo "<li><b>File:</b> $file</li>\n";
        echo "<li><b>Line:</b> $line</li>\n";
      echo "</ul>\n";
      echo "<pre>";
        print_r($context);
        echo "</pre></li>\n";
      exit( 1 );
    }
  }
  set_error_handler("_lc_onError");

  // 2     E_WARNING             Non-fatal run-time errors. Execution of the script is not halted
  // 8     E_NOTICE              Run-time notices. The script found something that might be an error, but could also happen when running a script normally
  // 256   E_USER_ERROR          Fatal user-generated error. This is like an E_ERROR set by the programmer using the PHP function trigger_error()
  // 512   E_USER_WARNING        Non-fatal user-generated warning. This is like an E_WARNING set by the programmer using the PHP function trigger_error()
  // 1024  E_USER_NOTICE         User-generated notice. This is like an E_NOTICE set by the programmer using the PHP function trigger_error()
  // 4096  E_RECOVERABLE_ERROR   Catchable fatal error. This is like an E_ERROR but can be caught by a user defined handle (see also set_error_handler())
  // 8191  E_ALL                 All errors and warnings, except level E_STRICT (E_STRICT will be part of E_ALL as of PHP 6.0)