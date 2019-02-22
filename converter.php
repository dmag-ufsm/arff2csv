<?php


$files = scandir('original');

if (!file_exists("converted")) {
    mkdir("converted");
}

foreach ($files as $file) {

    if (in_array($file,['.','..'])) continue;

    $headers = [];
    $reachedData = false;
    $relation = "";
    $destination = false;

    $origin = fopen("original/$file",'r');


    $filename = (pathinfo("original/$file")['filename']);
    while (($buffer = fgets($origin)) !== false) {
        if (trim($buffer) === "" || substr(trim($buffer),0,1) === "%") continue;

        if (!$reachedData) {
            if (preg_match("/^@RELATION (')?(?'relation'[^']*?)(?(1)')$/i",
                trim($buffer),$matches)) {
                $destination = fopen("converted/$filename-{$matches['relation']}.csv",'w+');
            } else if (preg_match("/^@ATTRIBUTE\s+(')?(?'attribute'(?(1)[^']*|[^'\s]*))(?(1)')\s.*$/i",
                        trim($buffer),$matches)) {
                $headers[] = '"'.trim($matches['attribute']).'"';
            } else if (preg_match("/^@DATA$/i",trim($buffer))) {
                if (!is_resource($destination)) {
                    echo "ERROR processing $file: @DATA statement before @RELATION".PHP_EOL;
                    break;
                }
                if (count($headers) === 0) {
                    echo "ERROR processing $file: @DATA statement before any @ATTRIBUTE".PHP_EOL;
                    break;
                }
                fwrite($destination,join(',',$headers));
                $reachedData = true;
            }
        } else {
            if (strstr($buffer,',') === false) {
                $buffer = preg_replace('/\s+/',',',$buffer);
            }
            fwrite($destination,PHP_EOL.preg_replace(
                "/(['\"])?((?(1)[^'\"]*|[^'\",]+))(?(1)['\"])/","\"\\2\"",trim($buffer)
            ));
        }
    }
    if (is_resource($destination)){
        fclose($destination);
    }
    fclose($origin);
}


