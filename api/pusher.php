<?php

#API access key from Google API's Console
define('API_ACCESS_KEY', 'AIzaSyB3_KAKUDx6WfxHya5Zbv2qq094ngqxI_E');

function sendPusher ($registrationId, $message, $repository) {

    #prep the bundle
    $msg = array(
        'body' 	=> "{$message}",
        'title'	=> "Repository: {$repository}",
        'icon'	=> 'https://png.icons8.com/search',/*Default Icon*/
        // 'sound' => 'mySound', /*Default sound*/
        'vibrate'	=> 1,
        'sound'		=> 1,
        'largeIcon'	=> '',
        'smallIcon'	=> 'https://img.icons8.com/material-sharp/50/000000/nut.png'
    );

    $fields = array('to' => $registrationId, 'notification'	=> $msg);
    $headers = array('Authorization: key=' . API_ACCESS_KEY, 'Content-Type: application/json');

    #Send Reponse To FireBase Server
    $ch = curl_init();

    curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
    curl_setopt( $ch,CURLOPT_POST, true );
    curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
    curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );

    $result = curl_exec($ch );
    curl_close( $ch );

    #Echo Result Of FireBase Server
    return $result;
}
