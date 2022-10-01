<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <meta http-equiv="Content-type" content="text/html; charset=utf-8"/>
        <title>LoxBerry Language Diff for LoxBerry Core</title>
    </head>
    <body>
        <?php
        // Version 04.12.2018 22:38:03
        // Include the diff class
        require_once 'diff.php';
        require_once './Diff/Renderer/Text/Unified.php';

        #######################################
        # Part 1 - Read file and save locally #
        #######################################
        // Init curl
        $curl   = curl_init();
        // URL to read from GitHub to compare it later
        $url    = "https://raw.githubusercontent.com/mschlenstedt/Loxberry/master/templates/system/lang/language_en.ini";

        curl_setopt($curl, CURLOPT_URL,  $url);
        // Delete local file if exists
        if ( file_exists('language_en.ini') ) unlink('language_en.ini'); 
        touch ("language_en.ini");
        // local file to write GitHub file content to it
        $fp     = fopen ("language_en.ini", 'w+') or die("PROBLEM_CREATING_COMPARE_FILE");
        curl_setopt($curl, CURLOPT_FILE, $fp);
        // Read the file and write content to local file
        curl_exec($curl);
        // Close curl
        curl_close($curl);
        // Close file
        fclose ($fp);

        ##########################################################
        # Part 2 - Connect to GitHub to add comment in issue 6 #
        ##########################################################
        // Local file to keep the last state to find differences
        touch ("language_en.ini_old");
        // Files for comparison
        $a = explode("\n", file_get_contents('language_en.ini_old'));
        $b = explode("\n", file_get_contents('language_en.ini'));
        if ( array_search("[ADMIN]",$b,true) === FALSE )
        {
			die("Something is wrong. New file has no [ADMIN] section. Aborting.");
        }
        // Options for generating the diff
        $options = array();
        // Initialize the diff class
        $diff = new Diff($a, $b, $options);
        // Generate a unified diff
        $renderer = new Diff_Renderer_Text_Unified;
        $diffbody =  "```diff ".$diff->render($renderer)."```";
        // Save downloaded as local old file to keep the last state to find differences next time
        rename('language_en.ini', 'language_en.ini_old');
        // Add comment data
        $data_string = '{  "body": '.json_encode($diffbody).' }';
        // Create new issue data
        #$data_string = '{  "title": "Translation changed",  "body": '.json_encode($diffbody).',  "labels": [    "Language"  ] }';
        // Init curl
        $curl = curl_init();
        // Add comment URL
        $url  = 'https://api.github.com/repos/mschlenstedt/Loxberry/issues/666/comments';
        // Create new issue URL
        #$url = 'https://api.github.com/repos/mschlenstedt/Loxberry/issues';
        // Set options for Curl
        curl_setopt($curl, CURLOPT_URL,  $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER   , true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION   , true);
        curl_setopt($curl, CURLOPT_COOKIESESSION    , true );
        curl_setopt($curl, CURLOPT_HTTPAUTH         , constant("CURLAUTH_BASIC"));
        curl_setopt($curl, CURLOPT_USERPWD          , 'ghp_YOUR_API_ACCESSTOKEN_HERE:x-oauth-basic');
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST    , "POST");
        curl_setopt($curl, CURLOPT_COOKIEJAR        , 'cookie.txt');
        curl_setopt($curl, CURLOPT_COOKIEFILE       , 'cookie.txt');
        curl_setopt($curl, CURLOPT_USERAGENT        , 'LoxBerry_Translate');
        curl_setopt($curl, CURLOPT_POSTFIELDS       , $data_string);
        curl_setopt($curl, CURLOPT_HTTPHEADER       , array('Content-Type: application/json','Content-Length: ' . strlen($data_string)));
        // Check for difference
        if ($diffbody != "```diff ```")
        {
            // Difference found.
            // Create request to add a comment
            curl_exec($curl);
			// HTTP-Status-Code prüfen
			if (!curl_errno($curl)) {
			  switch ($http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE)) 
			  {
			    case 201:  # Comment created
		            // Get answer as json
		            $data = json_decode(curl_multi_getcontent($curl), true);
		            // Message for new issue
		            #echo 'Changes found and issue created. Click <a href="https://github.com/mschlenstedt/Loxberry/issues/'.$data["number"].'">here</a> to see the issue #'.$data["number"];
		            // Message for new comment
		            echo 'Changes found. Click <a href="https://github.com/mschlenstedt/Loxberry/issues/666">here</a> to see the issue.';
			      break;
			    default:
			      echo 'Unerwarter HTTP-Code: ' . $http_code . "\n";
			  }
			}
        }
        else
        {
            // Message for no difference
            echo "Nothing changed since last check.";
        }
        ?>

    </body>
</html>

