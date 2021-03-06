<?php // Establish connection to MySQL database $con = 

$con = mysqli_connect("localhost","root","buckeyes","westest");
// Check the connection - not really necessary, but fuck it why not
if (mysqli_connect_errno()) {
	
	echo "Failed to connect to MySQL: " . mysqli_connect_error();
	
}
// Take in the desired messageName
$requestedMessage = $_GET['systemName'];

// Required for security checks when running mobile interface cross-domain
header('Access-Control-Allow-Origin: *');	

$output = array();

if ( $requestedMessage = "powertrain" ) {

	$variables = array("PhaseAtemp", "BusVoltage", "MotorId", "MotorTemp", "MotorVelocity", "PackTemp", "PackSOC", "PackBalance", "PrechargeCont", "MainCont", "EStop");

	foreach( $variables as $messageName ) {
		// this is probably bad and should be fixed (performance wise) -abk
		$query = "select*from Messages  left join Names on Messages.MsgName = Names.MsgName WHERE Names.MsgName = '" . $messageName . "' union  select*from Messages right join Names on Messages.MsgName = Names.MsgName ORDER BY time DESC  LIMIT 1";
		$result = mysqli_query($con, $query) or die( mysqli_error($con) );
		$data = mysqli_fetch_row($result);
		
		// status ranges
		$okMin 		= $data[5];
		$okMax 		= $data[6];
		$warnMin	= $data[7];
		$warnMax 	= $data[8];
		
		$value = $data[2];
		if ( ($okMin <= $value) && ($value <= $okMax) ){
			$status = "OK";
			
			//debug
			// echo $messageName . " between " . $okMin . " and " . $okMax . "<br>";
		} else if ( ($warnMin <= $value) && ($value <= $warnMax) ) {
			$status = "Warn";
		} else {		
			$status = "Fail";
			//FIXME: this does not currently catch the "no data" case
		}
		
		// UGLY SPECIAL CASE HACK FOR CONTACTORS AND ESTOP
		// SORRY, THE FUTURE
		if( $messageName == "MainCont" || $messageName == "PrechargeCont" ){
			$value = $data[2] ? "On" : "Off";
			$status = "OK";
		} else if( $messageName == "EStop" ) {
			$value = $data[2] ? "Open (stop)" : "Closed (drive)";
			$status = "OK";
		} else {
			$value = $data[2];
		}
		
		$output[] = array(	"messageName"	=>	$messageName,
							"messageValue" 	=>	$value,
							"messageStatus"	=>	$status,
							"messageUnit"	=>	$data[4]			);
	}
	
	echo json_encode($output);

}

// Free the result set
mysql_free_result($result);
// Close the connection
mysql_close($con);
?>
