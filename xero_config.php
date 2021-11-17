<?php
class ConfigClass
{
	public function connect_config(){
    	$connection = array(
			'clientId'                => '96BD152EF5B049FEB1B85E114C714450',   
		    'clientSecret'            => '15cModI0cU0By_udI-wXlmbYSyolyK7bezJu_K2V7Op0gWJG',
		    'redirectUri'             => 'http://localhost/wang/xero_callback.php',
		);	
		return $connection;
	}
  
}
?>