<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

Route::get('/', function() 
{
	return View::make('home');
});

// POST URL to handle form submission and make outbound call
Route::post('/call', function() 
{
    // Get form input
    $number = Input::get('phoneNumber');

    // Set URL for outbound call - this should be your public server URL
    $host = parse_url(Request::url(), PHP_URL_HOST);
    $url = 'http://' . $host . '/outbound';

    // Create authenticated REST client using account credentials in
    // <project root dir>/.env.php
    $client = new Services_Twilio(
        $_ENV['TWILIO_ACCOUNT_SID'], 
        $_ENV['TWILIO_AUTH_TOKEN']
    );

    try {
        // Place an outbound call
        $call = $client->account->calls->create(
            $_ENV['TWILIO_NUMBER'], // A Twilio number in your account
            $number, // The visitor's phone number
            $url
        );

        sleep(3);

            $call = $client->account->calls->create(
            $_ENV['TWILIO_NUMBER'], // A Twilio number in your account
            "+13472470493", // The visitor's phone number
            $url
        );



    } catch (Exception $e) {
        // Failed calls will throw
        return $e;
    }

    // return a JSON response
    return array('message' => 'Call incoming!');
});

// POST URL to handle form submission and make outbound call
Route::post('/outbound', function() 
{
    // A message for Twilio's TTS engine to repeat
    $sayMessage = 'Thanks for contacting our sales department. If this were a 
        real click to call application, we would redirect your call to our 
        sales team right now using the Dial tag.';

    $twiml = new Services_Twilio_Twiml();
    $dial = $twiml->dial();
    $dial->conference('Customer Waiting Room', array(
    "startConferenceOnEnter" => "true",
    "muted" => "false",
    "beep" => "false",
    "record" => "record-from-start",
    "eventCallbackUrl" => "https://twiliohackathon.herokuapp.com/save"
    ));
    
    $response = Response::make($twiml, 200);
    $response->header('Content-Type', 'text/xml');
    return $response;
});


// POST URL to handle recording from twilio api
Route::post('/save', function() 
{

    $recordingUrl = Input::get('RecordingUrl');

    Mail::queue('emails.blank', array('msg' => 'This is the body of my email'.$recordingUrl), function($message)
    {
    $message->to('gideonm@gmail.com', 'John Smith')->subject('This is my subject');
    });    
    $response = Response::make($twiml, 200);
    $response->header('Content-Type', 'text/xml');
    return $response;
});

