<?php

translate();

function translate() {
  $toLanguage = "ES";
  $jsonStr = file_get_contents("$toLanguage.json");
  $obj = json_decode($jsonStr, TRUE);
  $start = false;
  foreach ($obj as $englishPhrase => $translatedPhrase) {
    if ( $translatedPhrase !== "" ) continue;
    //if ( ! $start ) continue;
    $translatedPhrase = translate_phrase($englishPhrase, $toLanguage);
    $obj[$englishPhrase] = $translatedPhrase;
    $jsonStr = json_encode($obj, JSON_PRETTY_PRINT);
    file_put_contents("$toLanguage.new.json", $jsonStr);
    sleep(25);
  }
}

function translate_phrase($aPhrase, $aToLanguage) {
  $phrase = urlencode($aPhrase);
  $res = file_get_contents(
    "https://translate.googleapis.com/translate_a/single?client=gtx&sl=EN" .
      "&tl=$aToLanguage&dt=t&q=$phrase"
  );
  $res = substr($res, 4);
  $pos = strpos($res, "\"");
  $res = substr($res, 0, $pos);
  return $res;
}

?>
