#!/usr/bin/env php
<?php

/**
 * convert Llama LocationTrail file to JSON
 *
 * usage: php llama-locationtrail-reader <inputfile>
 * output: JSON array with cell changes
 *
 * documentation of Llama LocationTrail binary format:
 * http://kebabapps.blogspot.com/2012/04/llamaloc-format.html
 *
 * @author Kolesár András <kolesar@openstreetmap.hu>
 *
 */

$filename = $argv[1];
$data = file_get_contents($filename);
$basename = basename($filename);
$len = strlen($data);
$pos = 0;
$events = [];
$day = sprintf('%s-%s-%s',
    substr($basename, 0, 4),
    substr($basename, 4, 2),
    substr($basename, 6, 2)
);
$timestamp = strtotime($day);

while ($pos < $len) {
    $datatype = ord($data[$pos++]);
    $milliseconds = unpack('Ntime', substr($data, $pos, 4))['time']; $pos += 4;
    $datetime = date('Y-m-d H:i:s', (int) ($timestamp + $milliseconds/1000));
    $millis = $milliseconds-floor($milliseconds/1000)*1000;
    $event = [
        'time' => sprintf('%s.%03d', $datetime, $millis),
    ];

    switch ($datatype) {
        case 0:
            $event['type'] = 'start';
            break;

        case 1:
            $event['type'] = 'stop';
            break;

        case 2:
            $payload = unpack('Ncell/nmcc/nmnc', substr($data, $pos, 8)); $pos += 8;
            $event['type'] = 'cell';
            $event['cell'] = $payload['cell'];
            $event['mcc'] = $payload['mcc'];
            $event['mnc'] = $payload['mnc'];
            break;

        default:
            throw new Exception(sprintf('unknown record type %d at position %d', $datatype, $pos-5));
    }

    $events[] = $event;
}

echo json_encode($events, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), "\n";
