<?php
$handle = fopen('simple.json', 'r');

$object = new class {};

function parseJsonString(&$object, $handle, $value = null)
{
    $isStartRecordProperty = false;
    $isStartRecordValue = false;
    $prepareRecordValue = false;

    $property = '';
    while (false !== ($char = fgetc($handle))) {
        if ($char === '}') {
            $object->{$property} = $value;
            return;
        }
        if ($isStartRecordProperty) {
            if ('"' === $char) {
                $isStartRecordProperty = false;
                $object->{$property} = null;
                continue;
            } else {
                $property .= $char;
            }
        }
        if ('"' === $char && !$isStartRecordProperty && !$prepareRecordValue) {
            $isStartRecordProperty = true;
        }

        if ($isStartRecordValue) {
            if (',' === $char) {
                $isStartRecordValue = false;
                $value = purify($value);
                $object->{$property} = $value;
                $property = '';
                $value = null;
                continue;
            } else {
                if ('{' === $char) {
                    parseJsonString($property, $handle,  new class {});
                }
                if ('[' === $char) {
                    parseJsonString($property, $handle, []);
                }
                if (!$value && $char === ' ') {
                    continue;
                }
                $value .= $char;
            }
        }
        if (':' === $char && !$isStartRecordProperty && !$isStartRecordValue) {
            $isStartRecordValue = true;
        }
    }
}


function purify(string $value): string
{
    if ($value === 'true') {
        $value = true;
    }
    if ($value === 'false') {
        $value = false;
    }
    if (preg_match('/[0-9]+/', $value)) {
        $value = (int)$value;
    }
    if (preg_replace('/^"/', $value) || preg_match(' ', $value)) {

    }
    if ($value[0] === '"' && mb_substr($value, -1) === '"') {
        $value = mb_substr($value, 1, mb_strlen($value));
    }
}

parseJsonString($object, $handle);

var_dump($object);

fclose($handle);