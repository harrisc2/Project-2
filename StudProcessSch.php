<?php
session_start();
$debug = false;
include('CommonMethods.php');
$COMMON = new Common($debug);

//check to make sure the students wants the appointment
if($_POST["finish"] == 'Cancel'){
	$_SESSION["status"] = "none";
}
else{
	
			
	$advisor = $_SESSION["advisor"];

	if($debug) { echo("Advisor -> $advisor<br>\n"); }

	
	$apptime = $_SESSION["appTime"];
	
	$studid = $_SESSION["studID"];
	
	$sql = "select * from Proj2Students where `StudentID` like '%$studid%'";
	$rs = $COMMON->executeQuery($sql, $_SERVER["SCRIPT_NAME"]);
	
	//make sure the student exists otherwise add them to the students table
	$firstn = $row[1]; //Stores first name
	$lastn = $row[2]; //Stores last name
	$major = $row[5]; //Major is stored
	
	if($major == 'ENGR'){$major = 'Engineering Undecided' ;}
	if($major == 'MENG'){$major = 'Mechanical Engineering';}
	if($major == 'CMSC'){$major = 'Computer Science';}
	if($major == 'CMPE'){$major = 'Computer Engineering';}
	if($major == 'CENG'){$major = 'Chemical Engineering';}
	
	$email = $row[5]; //stores the students email
	{
		
	}


	// ************************ Lupoli 9-1-2015
	// we have to check to make sure someone did not steal that spot just before them!! (deadlock)
	// if the spot was taken, need to stop and reset
	if( isStillAvailable($apptime, $advisor) ) // then good, take that spot
	{ } 
	else // spot was taken, tell them to pick another and prompt with the next avaible appointment
	{
		if($debug == false) 
		{
			header('Location: 13StudDenied.php');
			return;
		}
	}

	
	//regular new schedule
	if($_POST["finish"] == 'Submit'){
		if($_SESSION["advisor"] == 'Group')  // student scheduled for a group session
		{
			//selects the group appount at the desire time
			$sql = "select * from Proj2Appointments where `Time` = '$apptime' and `AdvisorID` = 0";
			$rs = $COMMON->executeQuery($sql, $_SERVER["SCRIPT_NAME"]);
			$row = mysql_fetch_row($rs);
			$groupids = $row[4];
			//adds an enrolle to the appointment and adds the student to the enrolled id string
			$sql = "update `Proj2Appointments` set `EnrolledNum` = EnrolledNum+1, `EnrolledID` = '$groupids $studid' where `Time` = '$apptime' and `AdvisorID` = 0";
			$rs = $COMMON->executeQuery($sql, $_SERVER["SCRIPT_NAME"]);
		}
		else // student scheduled for an individual session
		{
			//increments EnrolledNum by 1 and sets the enrolled id to the students id 
			$sql = "update `Proj2Appointments` set `EnrolledNum` = EnrolledNum+1, `EnrolledID` = '$studid' where `AdvisorID` = '$advisor' and `Time` = '$apptime'";
			$rs = $COMMON->executeQuery($sql, $_SERVER["SCRIPT_NAME"]);
		}
		
	
		$_SESSION["status"] = "complete";
	}
	//otherwise must clear stud info from old appointment and the go through the normal new appointment code
	elseif($_POST["finish"] == 'Reschedule'){
		//remove stud from EnrolledID
		$sql = "select * from Proj2Appointments where `EnrolledID` like '%$studid%'";
		$rs = $COMMON->executeQuery($sql, $_SERVER["SCRIPT_NAME"]);
		$row = mysql_fetch_row($rs);
		$oldAdvisorID = $row[2];
		$oldAppTime = $row[1];
		$newIDs = str_replace($studid, "", $row[4]);
		
		$sql = "update `Proj2Appointments` set `EnrolledNum` = EnrolledNum-1, `EnrolledID` = '$newIDs' where `AdvisorID` = '$oldAdvisorID' and `Time` = '$oldAppTime'";
		$rs = $COMMON->executeQuery($sql, $_SERVER["SCRIPT_NAME"]);
		
		//schedule new app
		if($_SESSION["advisor"] == 'Group'){
			$sql = "select * from Proj2Appointments where `Time` = '$apptime' and `AdvisorID` = 0";
			$rs = $COMMON->executeQuery($sql, $_SERVER["SCRIPT_NAME"]);
			$row = mysql_fetch_row($rs);
			$groupids = $row[4];
			$sql = "update `Proj2Appointments` set `EnrolledNum` = EnrolledNum+1, `EnrolledID` = '$groupids $studid' where `Time` = '$apptime' and `AdvisorID` = 0";
			$rs = $COMMON->executeQuery($sql, $_SERVER["SCRIPT_NAME"]);
		}
		else{
			$sql = "update `Proj2Appointments` set `EnrolledNum` = EnrolledNum+1, `EnrolledID` = '$studid' where `Time` = '$apptime' and `AdvisorID` = '$advisor'";
			$rs = $COMMON->executeQuery($sql, $_SERVER["SCRIPT_NAME"]);
		}

		$_SESSION["status"] = "resch";
	}

	//update stud status to ''
	$sql = "update `Proj2Students` set `Status` = '' where `StudentID` = '$studid'";
	$rs = $COMMON->executeQuery($sql, $_SERVER["SCRIPT_NAME"]);

}
if($debug == false) { header('Location: 12StudExit.php'); }



function isStillAvailable($apptime, $advisor)
{
	// advisor could be "Group"
	global $debug; global $COMMON;
	$sql = "";
	
	if($advisor == "Group")
	{ $sql = "select `EnrolledNum`, `Max` from `Proj2Appointments` where `Time` = '$apptime' and `AdvisorID` = 0";  }
	else // then specific
	{ $sql = "select `EnrolledNum`, `Max` from `Proj2Appointments` where `Time` = '$apptime' and `AdvisorID` = '$advisor'";  }
	$rs = $COMMON->executeQuery($sql, $_SERVER["SCRIPT_NAME"]);
	$row = mysql_fetch_row($rs);

	// if max [1] =< EnrolledNum[0], then the spot was indeed taken
	if($row[1] > $row[0]) // then all good
	{ 
		if($debug) { echo("spot available\n<br>"); }
		return true; 
	}
	else // spot was taken
	{
		if($debug) { echo("spot NOT available\n<br>"); }	
		return false; 
	}

}

?>


