<?php

/*
* Retrieve a list of the domains that have been added to the server. There are
* two options for this script. "webs" will look in the provided directory recursively
* for every web.config file and parse it to retrieve the rules that contain the HTTP_HOST
* attribute. "apphost" will search the applicationHost.config file for each site and its
* respective binding information. The applicationHost.config will need to be copied to the 
* directory in which this script is running due to a permission issue that I don't want to
* deal with. 
*
* With both options (seperately) a fairly clear picture may be obtained of the domains bound 
* to the server.
*/

// Prevent browser access
if ( !isset( $argv ) ) exit;

$arg_1  = isset( $argv[1] ) ? $argv[1] : false;

$dir  = isset( $argv[2] ) ? $argv[2] : false;

$err = array(
    'Min one arg required. "webs" to seach web.config for for HTTP_HOST or "apphost" to search applicationHost.config.',
    'Please include inetpub directory path. Ex: webs "E:\inetpub2"',
);

function list_web_config_http_hosts( $d = "C:\inetpub" )
{
    $break = "\r\n";

	$files = array();

	$dir = new RecursiveDirectoryIterator( $d );

	foreach( new RecursiveIteratorIterator( $dir ) as $file )
	{
		$pathinfo = pathinfo( $file );
		if ( isset( $pathinfo['extension'] ) && isset( $pathinfo['filename'] ) ){
			if ( "config" == $pathinfo['extension'] && "web" == $pathinfo['filename'] ) {
				$files[] = $file;
			}
		}
	}

	foreach( $files as $file )
	{	
		$web_config = simplexml_load_file( $file );
        
        if ( !is_object( $web_config ) ) continue;

		foreach( $web_config->xpath("//rule") as $rule_object )
		{
			$rule = simplexml_load_string( $rule_object->asXML() );
			
			foreach( $rule->xpath("//add") as $add )
			{
				if ( "{HTTP_HOST}" == $add->attributes()->input ){
					echo trim( preg_replace( '/[^A-Za-z\.0-9|]/' , '', $add->attributes()->pattern ), "." ) . "  [path: " . $file . "]" . $break;
				}
			}
		}
	}
}

function list_app_host_config_binding_info()
{
	$break = "\r\n";
	// just copying this to the desktop for now. Permission error trying to read it from C:\windows\system32\inetsrv\config
	$applicationHostConfig = simplexml_load_file( "applicationHost.config" );

	foreach( $applicationHostConfig->xpath("//site") as $site_object )
	{
		$site = simplexml_load_string( $site_object->asXML() );
        
        $names = $site->xpath("//@name");
		
		echo $names[0] . $break;
		
		foreach( $site->xpath("//binding/@bindingInformation") as $bindingInformation )
			echo $bindingInformation . $break;

		echo $break;
	}
}

if ( $arg_1 )
{
	if ( 'webs' == $arg_1 )
    {
		if ( $dir )
			list_web_config_http_hosts( $dir );
		else
			echo $err[1]; 
	}
    else if ( 'apphost' == $arg_1 )
		list_app_host_config_binding_info();
    else
        echo $err[0];
} else
    echo $err[0];
