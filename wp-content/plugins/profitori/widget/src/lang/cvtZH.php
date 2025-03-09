<?php

convert();

function convert() {
  $orig = file_to_lines("ZH.dummy.json");
  $new = file_to_lines("ZH.manual.json");
  $len = sizeof($orig);
  if ( $len !== sizeof($new) ) {
    echo "Number of rows doesn't match\n";
    echo $len . "\n";
    echo sizeof($new) . "\n";
    return;
  }
  $res = array();
  for ( $i = 1; $i < ($len - 1); $i++ ) {
    $origKey = line_to_first_string($orig[$i]);
    $newKey = line_to_first_string($new[$i]);
    $res[$origKey] = $newKey;
  }
  $json = json_encode($res, JSON_PRETTY_PRINT);
  file_put_contents("ZH.new.json", $json);
  echo "Done\n";
}

function file_to_lines($aFile) {
  $contents = file_get_contents($aFile);
  $res = explode("\n", $contents);
  return $res;
}

function line_to_first_string($aLine) {
  $pos = strpos($aLine, '"');
  $pos2 = strpos($aLine, '"', $pos + 1);
  $res = substr($aLine, $pos + 1, $pos2 - $pos - 1);
  return $res;
}

function mylog($aObj) {
  echo print_r($aObj, TRUE) . "\n";
}

?>
