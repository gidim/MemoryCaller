<?

//
// Simple class to use the Google Full-Duplex speech recognition API.
//
//  Mike (mike@mikepultz.com)
//
// version 1.0
//
//
// Simple example:
//
//      require 'google_speech.php';
//
//      $s = new cgoogle_speech(< API KEY >);
//
//      $result = $s->process('@audio_file.flac', 'en-US', 8000);
//
//
class cgoogle_speech
{
    //
    // the base URL to connect to
    //
    private $m_base_url = 'https://www.google.com/speech-api/full-duplex/v1/';
    
    //
    // the unique pair to use, and the auth key
    //
    private $m_pair;
    private $m_key;

    //
    // the upload/download cURL handles
    //
    private $m_up_handle;
    private $m_dn_handle;

    //
    // set up the base connection
    //
    //  $_key is the Google API key; you'll need to sign up for one
    //
    public function __construct($_key)
    {
        //
        // store the key
        //
        $this->m_key = $_key;

        //
        // create a pair id
        //
        $this->m_pair = $this->generate_pair(16);

        //
        // intialize the cURL objects
        //
        $this->m_up_handle = curl_init();
        $this->m_dn_handle = curl_init();

        //
        // set up the download handle
        //
        curl_setopt($this->m_dn_handle, CURLOPT_URL, $this->m_base_url . 'down?pair=' . $this->m_pair);
        curl_setopt($this->m_dn_handle, CURLOPT_RETURNTRANSFER, true);

        //
        // set up the upload handle
        //
        curl_setopt($this->m_up_handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->m_up_handle, CURLOPT_POST, true);
    }

    //
    // clean up
    //
    public function __destruct()
    {
        curl_close($this->m_up_handle);
        curl_close($this->m_dn_handle);
    }

    //
    // generate a pair id
    //
    private function generate_pair($_length)
    {
        $c = '0123456789';
        $s = '';

        for($i=0; $i<$_length; $i++)
        {
            $s .= $c[rand(0, strlen($c) - 1)];
        }

        return $s;
    }

    //
    // make a request
    //
    //  $_data      can be either a filename (preceeded with an '@'), or the raw flac content
    //  $_language  is the ISO language code- not sure what languages are available
    //  $_rate      is the bitrate of the flac content
    //
    public function process($_data, $_language = 'en-US', $_rate = 8000)
    {
        //
        // add the data and rate to the upload handle
        //
        curl_setopt($this->m_up_handle, CURLOPT_URL, $this->m_base_url . 'up?lang=' . $_language . 
            '&lm=dictation&client=chromium&pair=' . $this->m_pair . '&key=' . $this->m_key);
        curl_setopt($this->m_up_handle, CURLOPT_HTTPHEADER, array('Transfer-Encoding: chunked', 
            'Content-Type: audio/x-flac; rate=' . $_rate));
        curl_setopt($this->m_up_handle, CURLOPT_POSTFIELDS, array('file' => $_data));

        //
        // create a mutli request object
        //
        $m = curl_multi_init();

        curl_multi_add_handle($m, $this->m_dn_handle);
        curl_multi_add_handle($m, $this->m_up_handle);

        $active = null;
        
        //
        // make the request
        //
        do
        {
            curl_multi_exec($m, $active);

        } while($active > 0);

        //
        // get the content
        //
        $res = curl_multi_getcontent($this->m_dn_handle);

        //
        // there can be multiple lines, so we'll split them, and go through each
        //
        $output = array();

        $results = explode("\n", $res);
        foreach($results as $result)
        {
            //
            // JSON decode it
            //
            $object = json_decode($result, true);

            //
            // grab all the results, and build one big list
            //
            if ( (isset($object['result']) == true) && (count($object['result']) > 0) )
            {
                foreach($object['result'] as $obj)
                {
                    $output[] = $obj;
                }
            }
        }

        //
        // remove the handles
        //
        curl_multi_remove_handle($m, $this->m_dn_handle);
        curl_multi_remove_handle($m, $this->m_up_handle);

        curl_multi_close($m);

        return $output;        
    }
};

?>
