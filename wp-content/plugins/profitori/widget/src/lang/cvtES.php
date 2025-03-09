<?php

/*
Inicio,"    ""Home"": ""Casa"","
Introducción,"    ""Blurb"": ""Propaganda"","
*/

convert();

function convert() {
  $new = file_to_lines("ES.csv");
  $res = array();
  $len = sizeof($new);
  for ( $i = 0; $i < ($len - 1); $i++ ) {
    $key = line_to_key($new[$i]);
    $value = line_to_value($new[$i]);
    $res[$key] = $value;
  }
mylog($res);
  $json = json_encode($res, JSON_PRETTY_PRINT);
mylog($json);
  file_put_contents("ES.new.json", $json);
  echo "Done\n";
}

function file_to_lines($aFile) {
  $contents = file_get_contents($aFile);
  $res = explode("\n", $contents);
  return $res;
}

function line_to_value($aLine) {
  $pos = strpos($aLine, ',"');
  $res = substr($aLine, 0, $pos);
  $res = utf8_encode($res);
  return $res;
}

function line_to_key($aLine) {
  $pos = strpos($aLine, '""');
  $pos2 = strpos($aLine, '""', $pos + 1);
  $res = substr($aLine, $pos + 2, $pos2 - $pos - 2);
  return $res;
}

function mylog($aObj) {
  echo print_r($aObj, TRUE) . "\n";
}

?>
