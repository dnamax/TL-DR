<?php
    //dummy string is stored as the default keyword for each URL
    //we need this so as to have only one DB call to start with (will get the URL and the keywords - if no keywords, we 
    //will still get one row that will contain the url_id
    $DUMMY_STRING=  "tldr_secret_x1999";
    $DB_NAME=       "urls";
    $DB_LOGIN=      "urls";
    $DB_PASSWORD  = "urlspw";
    $DB_HOST =      "localhost";
    $con =          null;
    $g_result     = null;
    $g_urlid =      0;
    $q            = urldecode($_GET["url"]);
    if ( strlen( $_GET["keywords"]) > 0) 
    {
        $keywords     = urldecode($_GET["keywords"]);   
    }
    $url_id       = 0;
    //logging class. Should not be here ;)
    class Logging{
        // define log file
        private $log_file = '/www/ak.learningenterprises.org/tldr/url.log';
        // define file pointer
        private $fp = null;
        // write message to the log file
        public function lwrite($message){
            // if file pointer doesn't exist, then open log file
            if (!$this->fp) $this->lopen();
            // define script name
            $script_name = pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME);
            // define current time
            $time = date('H:i:s');
            // write current time, script name and message to the log file
            fwrite($this->fp, "$time ($script_name) $message\n");
        }
        // open log file
        private function lopen(){
            // define log file path and name
            $lfile = $this->log_file;
            // define the current date (it will be appended to the log file name)
            $today = date('Y-m-d');
            // open log file for writing only; place the file pointer at the end of the file
            // if the file does not exist, attempt to create it
            $this->fp = fopen($lfile . '_' . $today, 'a') or exit("Can't open $lfile!");
        }
    }
	$log = new Logging();
     if (isset ($q)) //main body
     {
         $log->lwrite("query is " . $q);
         //header('Content-type: text/xml');
         $output = "";
         $g_result = openConnection();
         if (urlExists($g_result))
         {
             $row0 = mysql_fetch_array($g_result);
             $url_id = $row0["id"];
             $g_url_id = $url_id;
             //update hit count
             updateHitCount($url_id);
             //Return number of keywords, actual keywords
         }
         else
         {
             //add entry
             $url_id = addUrl($q);
             $g_url_id = $url_id;
             updateHitCount($url_id);
         }
         if ( isset ($keywords) )
         {
             addKeywords($g_url_id,$keywords);
             closeConnection();
             $g_result = openConnection();
         }
         $output = getKeywords($g_url_id);
         header('Content-Type: text/xml');
         header ('Cache-Control: no-cache');
         header ('Cache-Control: no-store' , false);
         echo $output;
     }    
     //This function will connect to the database and perform the mysql query. it returns a result-set
     function openConnection(){
         global $q ,  $DB_HOST , $DB_LOGIN , $DB_PASSWORD, $DB_NAME, $log, $con;
         $con = mysql_connect($DB_HOST, $DB_LOGIN, $DB_PASSWORD);
         if (!$con)
         {
             //echo "DB_INITIALIZE_FAILURE";
             $log->lwrite("DB_INIT FAILED: ".$q);
             die('Could not connect: ' . mysql_error());
         }
         mysql_select_db($DB_NAME, $con);
         $sql="SELECT u.url,u.id, k.id AS kwid, k.keyword, k.upvotes FROM url u, keywords k WHERE LOWER(url) = '".mysql_real_escape_string($q)."' AND u.id = k.url_id"
             ." ORDER BY k.upvotes DESC, k.id DESC ";
         $log->lwrite("openconnection: " . $sql);
         $result = mysql_query($sql);
         return $result;
     }
     function closeConnection(){
         global  $log, $con, $g_result;
         $log->lwrite("Closing Database Connection!");
         if ($con)
         {    
             mysql_close($con);         
             $g_result = null;
         }
     }
     //returns true if keywords exist for this URL (false if the URL doesn't exist or DOES exist without kw
     function keywordsExist($resultset){
         if (mysql_num_rows($resultset) > 1)
         {
            return true;
         }
         else
         {
            return false;
         }
     }
     function urlExists($resultset){
         if (mysql_num_rows($resultset) > 0) //this assumes we have the dummystring
         {
            return true;
         }
         else
         {
            return false;
         }
     }
     //returns XML string of all keywords, with keyword IDs
     function getKeywords($url_id){
         global $q,$g_result, $log;
        $doc = new DomDocument('1.0');
        $root = $doc->createElement('root');
        $root = $doc->appendChild($root);
        $occ = $doc->createElement("keywords");
        $occ = $root->appendChild($occ);
        if (!$g_result || mysql_num_rows($g_result) ==0)
        {
             return "<?xml version=\"1.0\"?><root><keywords></keywords></root>";
        }
        mysql_data_seek($g_result,0);
        
        while ($row = mysql_fetch_assoc($g_result))
        {
                //filter out dummy - which is needed for urlexists when no keywords exist
                $kw = $row["keyword"];
                if ( isNotDummy ($kw) )
                {
                  $child       = $doc->createElement("keyword");
                  $child       = $occ->appendChild($child);
                  $kwtext      = $doc->createElement("text");
                  $kwtext      = $child->appendChild($kwtext);
                  $kwid        = $doc->createElement("id");
                  $kwid        = $child->appendChild($kwid);
                  $kwtextvalue = $doc->createTextNode($row["keyword"]);
                  $kwtextvalue = $kwtext->appendChild($kwtextvalue);
                  $kwidvalue   = $doc->createTextNode($row["kwid"]);
                  $kwidvalue   = $kwid->appendChild($kwidvalue);
                }
        }
        $xml_string = $doc->saveXML();
        $log->lwrite("Query string:" . $q);
        $log->lwrite("XMLstring:" . $xml_string);
        return $xml_string;
     }
     //updates the hit count for this URL. Analytics anyone? :)
     function updateHitCount($url_id){
        $updatehitcountsql = "UPDATE url set hitcount = hitcount + 1 WHERE id =" . $url_id;
         //echo "SQL:" . $updatehitcountsql;
        $result = mysql_query($updatehitcountsql);
     }
     //Adds the URL with the dummy kw
     function addUrl($url){
        global $log, $DUMMY_STRING;
        $sql = "INSERT INTO url (url) values ('". mysql_real_escape_string($url) . "'".  ")";
        //echo $sql;
        if (!mysql_query($sql))
        {
            // error
            echo "DB_INSERT_ERROR";
            $log->lwrite("DB_INSERT_ERROR ".$url);
            return 0; //error
        }
        else
        {
            $url_id = mysql_insert_id();
            $log->lwrite("Successfully added url (" . $url . ") with ID " . $url_id);
            $log->lwrite("dummy is " . $DUMMY_STRING);
            addKeywords($url_id, $DUMMY_STRING);
            return $url_id;
            //success
        }
     }
     //adds keywords in a single insert statement
     function addKeywords($url_id,$kwcsv)
     {
        global $log;
        $log->lwrite("kwcsv is " . $kwcsv);

        //create an array from csv
        $a_keywords = preg_split("/,/" , $kwcsv);
        if (sizeof($a_keywords) > 0)
        {
            $sql = "INSERT INTO keywords (url_id,keyword) values " ;
            $firstloop = true;
            $atleastone = false; //means that at least one keyword has been validated 
            foreach ($a_keywords as $kw)
            {   
                //check to see if this keyword exists
                //if not, add it to the INSERT string
                if ( isunique($kw) )
                {
                    if (!$firstloop)
                    {
                        $sql .= ",";
                    }
                    $sql .= "($url_id,'". strtolower( trim( mysql_real_escape_string( $kw) ) )  . "'".  ")";    
                    $firstloop      = false;
                    $atleastone     = true;
                }
            }
            if ( $atleastone )
            {
              $log->lwrite("Insert keywords: " . $sql); 
              if (!mysql_query($sql) )
              {
                // error
                echo "DB_INSERT_ERROR";
                $log->lwrite("DB_INSERT_ERROR ". mysql_error());
                return 0; //error
              }
              else
              {
                  $log->lwrite("Successfully added keywords (" . $kwcsv . ") for URL ID " . $url_id); 
                  return mysql_insert_id();
                  //success
              }
            }
        }
    }
    function isNotDummy($candidate)
    {
        global $DUMMY_STRING;
        return ($candidate != $DUMMY_STRING);
    }
    function isunique($candidatekw)
    {
        global $log, $g_result;
        if (mysql_num_rows($g_result) > 0) {
            mysql_data_seek($g_result, 0);
        }
        else
        {
            $log->lwrite("Not seeking");
        }
        
        $found = false;
        while ($row = mysql_fetch_assoc($g_result))
        {
            if ( strtolower( trim( $candidatekw ) ) == strtolower( trim( $row["keyword"] ) ) )
            {
                $log->lwrite("Found keyword " . $candidatekw . " not adding");
                return false;
            }
        }
        return true;       
    }
?>
