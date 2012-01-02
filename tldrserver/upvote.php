<?php
    $DB_NAME=       "urls";
    $DB_LOGIN=      "urls";
    $DB_PASSWORD  = "urlspw";
    $DB_HOST =      "localhost";
    $con =          null;
    $g_result     = null;
    $kwid = $_POST["kwid"];
    
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

    
    
    
    if (isset ($kwid))
    {
        openConnection();
        echo upVote($kwid);
    }
    
    function openConnection()
    {
        global $log, $con,  $DB_NAME, $DB_LOGIN, $DB_PASSWORD,$DB_HOST;
        $con = mysql_connect($DB_HOST, $DB_LOGIN, $DB_PASSWORD);
        if (!$con)
        {
            $log->lwrite("DB_INIT FAILED: ".$kwid);
            die('Could not connect: ' . mysql_error());
        }
        mysql_select_db($DB_NAME, $con);        
    }
    
    function upVote($kwid)
    {
        global $log, $con;
        $sql = "UPDATE keywords set upvotes = upvotes + 1 WHERE id=" . $kwid;
        $log->lwrite($sql);
        $result = mysql_query($sql);
        if ($result)
        {
            $log->lwrite("upvoted: ".$kwid);
            return "OK";
        }
        else
        {
            $log->lwrite("not upvoted: ".$kwid);
            return "NOTOK";
        }
    }
?>