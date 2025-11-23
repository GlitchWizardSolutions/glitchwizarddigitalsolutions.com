<?php
/* THE FOLLOWING ERROR HANDLING CODE IS SITE WIDE */
//Establish connection with the error handling database (on glitchwi)
try {
	$error_db = new PDO('mysql:host=' . db_host . ';dbname=' . db_name9 . ';charset=' . db_charset, db_user, db_pass);
	$error_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
	// If there is an error with the connection, stop the script and display the error.
	exit('Failed to connect to the on the error handling database!');
}

//initialize error handling function parameters
    $application = '';
    $path = '';
    $section = '';
    $inputs = '';
    $outputs = '';
    $thrown = '';
    $noted = '';
    $errno = ''; 
    $errmsg = ''; 
    $filename = ''; 
    $linenum = ''; 
    $vars = '';  
    
// We will use our own custom error handling.
error_reporting(0);

// This is the custom error handling function used in all GWS Web Applications.
Function errorHandler($errno, $errmsg, $filename, $linenum, $vars, $path, $outputs, $noted){
    global $error_db;
    $dt = date('Y-m-d H:i:s');
       
    // Define an assoc array of error strings
    // (Common:) E_WARNING, E_NOTICE, E_USER_ERROR, E_USER_WARNING and E_USER_NOTICE
    $errortype = array (
                E_ERROR              => 'Error',
                E_WARNING            => 'Warning',
                E_PARSE              => 'Parsing Error',
                E_NOTICE             => 'Notice',
                E_CORE_ERROR         => 'Core Error',
                E_CORE_WARNING       => 'Core Warning',
                E_COMPILE_ERROR      => 'Compile Error',
                E_COMPILE_WARNING    => 'Compile Warning',
                E_USER_ERROR         => 'User Error',
                E_USER_WARNING       => 'User Warning',
                E_USER_NOTICE        => 'User Notice',
                E_STRICT             => 'Runtime Notice',
                E_RECOVERABLE_ERROR  => 'Catchable Fatal Error'
                );
                
     // The following are the set of errors for which a var trace will be saved.
    $user_errors = array(E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE);
    
    $err = "<errorentry>\n";
    $err .= "\t<datetime>" . $dt . "</datetime>\n";
    $err .= "\t<errornum>" . $errno . "</errornum>\n";
    $err .= "\t<errortype>" . $errortype[$errno] . "</errortype>\n";
    $err .= "\t<errormsg>" . $errmsg . "</errormsg>\n";
    $err .= "\t<scriptname>" . $filename . "</scriptname>\n";
    $err .= "\t<scriptlinenum>" . $linenum . "</scriptlinenum>\n";            
    
    if (in_array($errno, $user_errors)) {
        $err .= "\t<vartrace>" . serialize($vars) . "</vartrace>\n";
    }
    $err .= "</errorentry>\n\n";
  
    // Uncomment below for testing.
   //   echo $err;
    //SAVE TO DATABASE
    $application = $filename;
    $section = $linenum;
    $inputs = serialize($vars);
    $outputs = is_array($outputs) ? serialize($outputs) : $outputs;
    $thrown = $err;
    $noted = $_SERVER['SCRIPT_NAME'];
    $stmt = $error_db->prepare('INSERT INTO error_handling (application,pagename,path,section,inputs,outputs,thrown,noted) VALUES (?,?,?,?,?,?,?,?)');
    $stmt->execute([ $application, basename($_SERVER['PHP_SELF']), $_SERVER['REQUEST_URI'], $section, $inputs, $outputs, $thrown, $noted ]);
  
    // Save to the error log 
     error_log($err, 3, "error.log");
   // error_log($err, 1, "sidewaysy@gmail.com");
    //Can have it send email to me.
   // if ($errno == E_USER_ERROR) {
    //     mail("sidewaysy@gmail.com", "Critical User Error", $err); 
    //}        
}//This ends the errorHandler function.
set_error_handler('errorHandler', E_ALL);