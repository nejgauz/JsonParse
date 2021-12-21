<?php

const NUMBERS = ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9"];
const KEY_WRITING_PROCESS = "key_writing";
const VALUE_WRITING_PROCESS = "value_writing";

$handle = fopen('simple.json', 'r+');
$object = new class {};

function parseJson(&$object, $handle)
{
    $previousSymbol = false;
    $key = '';
    $state = KEY_WRITING_PROCESS;
    while (false !== ($char = fgetc($handle))) {
        if (($char === "}" || $char === "]") && $previousSymbol !== "\\") {
            break;
        }

        if ($char === "{" && $previousSymbol !== "\\" && $previousSymbol !== false) {
            $object->{"$key"} = new class {};
            parseJson($object->{"$key"}, $handle);
            continue;
        }

        if ($state === KEY_WRITING_PROCESS) {

            if ($char === "\"") {
                $key = getStringValue($handle);
                $object->{$key} = null;
                $state = VALUE_WRITING_PROCESS;
                continue;
            }
        }

        if ($state === VALUE_WRITING_PROCESS) {

            switch ($char) {
                case "\"":
                    $value = getStringValue($handle);
                    $object->{$key} = $value;
                    $state = KEY_WRITING_PROCESS;
                    break;
                case "t":
                    $object->{$key} = true;
                    fseek($handle, 4, SEEK_CUR);
                    $state = KEY_WRITING_PROCESS;
                    break;
                case "f":
                    $object->{$key} = false;
                    fseek($handle, 4, SEEK_CUR);
                    $state = KEY_WRITING_PROCESS;
                    break;
            }

            if (in_array($char, NUMBERS)) {
                $number = getNumberValue($char, $handle);
                $object->{$key} = $number;
                $state = KEY_WRITING_PROCESS;
            }
        }

        $previousSymbol = $char;
    }
}

function getNumberValue($firstChar, &$handle): int
{
    $number = $firstChar;
    while (($char = fgetc($handle)) !== "," && $char !== false) {
        $number .= $char;
    }
    return intval($number);
}

function getStringValue(&$handle): string
{
    $string = "";
    $previousSymbol = "";
    while (($char = fgetc($handle)) !== false && !($char === "\"" && $previousSymbol !== "\\")) {
        $string .= $char;
        $previousSymbol = $char;
    }
    fseek($handle, 1, SEEK_CUR);
    return $string;
}

parseJson($object, $handle);

echo $object->address->street;

fclose($handle);