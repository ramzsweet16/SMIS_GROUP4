<?php
include "connection.php";
     class payment extends DB_Connect{
	//DELETE FROM ALL ASSESSMENT
	function delAS(){
	    $sql = "Delete from tblallassessment where fldAssessmentNo = 2";
	    mysql_query($sql,$this->openCon());
	}
	
	
        //SEARCHING STUDENT
        function p_search_student($student_id){
            $con = $this->openCon();
            //
            $sql1 = "SELECT fldStudent_No,fldStud_FirstName, fldStud_MiddleName, fldStud_LastName FROM tblstudentrecord WHERE fldStudent_No='$student_id' AND fldStudent_Status='enrolled'";
            $result1 = mysql_query($sql1,$con);
            $row1 = mysql_fetch_array($result1);
            $full_name = $row1[1]." ".$row1[2]." ".$row1[3];
            //echo $row1[0]." ".$row1[1]." ".$row1[2];
            //
            $enrolled = true;
            $sql2 = "SELECT fld_Enrollment_Id, fld_Grade_Year_Level FROM tblenrolldata WHERE fld_Student_Num = '$student_id'";
            $result2 = mysql_query($sql2,$con);
            $row2 = mysql_fetch_array($result2);
            if($row2[0] == "" || $row2[1] == null){
                $enrolled = false;
            }
            if($enrolled){
                $json_data = array('studentNo'=>$row1[0],'studentName'=>$full_name,'enrollmentNo'=>$row2[0],'gradeYearLevel'=>$row2[1]);
                $json_string = json_encode($json_data);
                echo $json_string;
            }else{
                echo "notEnrolled";
            }

            $this->closeCon();

        }

        //GET CURRENT ASSESSMENT
        function p_getCurrentAssessment($enrollmentNo,$studentNo){
            $con = $this->openCon();
            //
            $sql1 = "SELECT MAX(fldAssessmentNo) FROM tblnextassessment WHERE fldEnrollmentNo = '$enrollmentNo' AND fldStudentNo = '$studentNo'";
            $result1 = mysql_query($sql1,$con);
            $row1 = mysql_fetch_array($result1);
            $assNo = $row1[0];
	    
	    //getting data from table tblnextassessment
	    $sqlGetNS = "SELECT fldId,fldTransactionNo,fldEnrollmentNo,fldStudentNo,fldAssessmentName,
	    fldOriginalAmount,fldOriginalBalance,fldAssessmentAmount,fldAdvancedPayment,fldAssessmentNo FROM tblnextassessment WHERE fldEnrollmentNo = '$enrollmentNo' AND fldStudentNo = '$studentNo'";
            $result3 = mysql_query($sqlGetNS, $con);
	    while($rowGNS = mysql_fetch_array($result3)){
		$autoId = $rowGNS[0];$transNo = $rowGNS[1];$enrollNo = $rowGNS[2];$studentNo = $rowGNS[3];
		$assName = $rowGNS[4];$assOrigAmnt = $rowGNS[5];$assOrigBal = $rowGNS[6];$assAmount = $rowGNS[7];
		$assAdvance = $rowGNS[8];$assNo = $rowGNS[9];
		
		//inserting data into table tblallassessment
		$sqlInAS = "INSERT INTO tblallAssessment (fldTransactionNo,fldEnrollmentNo,fldStudentNo,fldAssessmentName,
		fldOriginalAmount,fldOriginalBalance,fldAssessmentAmount,fldAdvancedPayment,fldAssessmentNo)
		VALUES ('$transNo','$enrollNo','$studentNo','$assName',$assOrigAmnt,$assOrigBal,$assAmount,$assAdvance,$assNo)";
		mysql_query($sqlInAS, $con);
		
	    }
	    
	    //getting data from table tblallassessment and table tblamountPerAssessment
	    $sql = "SELECT aa.fldId, aa.fldTransactionNo, aa.fldEnrollmentNo, aa.fldStudentNo, aa.fldAssessmentName, aa.fldOriginalAmount,
	    aa.fldOriginalBalance, aa.fldAssessmentAmount, aa.fldAdvancedPayment,
	    aa.fldAssessmentNo, aps.fldAssessmentAmount FROM tblallassessment AS aa, tblamountPerAssessment AS aps
	    WHERE aa.fldAssessmentNo = $assNo AND aa.fldAssessmentAmount !=0 AND aa.fldEnrollmentNo = '$enrollmentNo' AND aa.fldStudentNo = '$studentNo' AND aps.fldEnrollmentNo = '$enrollmentNo'
	    AND aps.fldStudentNo = '$studentNo' AND aa.fldAssessmentName = aps.fldAssessmentName AND aa.fldTransactionNo = aps.fldTransactionNo AND aa.fldStudentNo = aps.fldStudentNo ";
	    $result2 = mysql_query($sql, $con);
	    //echo $sql;
	    $notFound = true;
	    while($row2 = mysql_fetch_array($result2)){
		
		$assAmount = $row2[7];
		if($assAmount > $row2[6]){
		    $assAmount = $row2[6];
		}else{
		    $assAmount = $row2[7];
		}
		
		echo "<tr>";
                echo "<td><span id=assName".$row2[0].">".$row2[4]."</span>
		            <input type=hidden id=assOrigBal".$row2[0]." value = ".$row2[6]." />
		             <input type=hidden id=amntPerAss".$row2[0]." value = ".$row2[10]." /></td>";
                echo "<td><span id=assAmnt".$row2[0].">".$assAmount."</span></td>";
                echo "<td><span id=assBalance".$row2[0].">".$row2[7]."</span></td>";
                echo "<td><input type='text' id=c_payment".$row2[0]." onkeyup = 'computeTotalCPayment(".$row2[0].")' />
		    <input type=hidden id=assNum value=".$row2[9]." />
		    <input type=hidden id=assOrigAmnt".$row2[0]." value=".$row2[5]." /></td>";
                echo "<td><span id=advanceP".$row2[0].">0</span></td>";
                echo "</tr>";
		$notFound = false;
	    }
	    if($notFound){
		echo "<tr><td>No assessment found!!!!</td></tr>";
	    }
	    
	    
            //close connection
            $this->closeCon();
        }

        //SET MODE OF PAYMENT
        function p_setModePayment($enrollmentNo,$studentNo){
            $sql = "SELECT fldModeOfPayment FROM tblassissment WHERE fldEnrollmentNo = '$enrollmentNo' AND fldStudentNum = '$studentNo' limit 0,1";
            $result = mysql_query($sql, $this->openCon());
            $row = mysql_fetch_array($result);
            echo $row[0];
        }
        
        //NO BALANCE
        function p_noadvanceAss($enrollmentNo,$studentNo,$autoId,$assName,$assessmentNo,$balance,$assOrigAmnt,$assCPayment){
            $con = $this->openCon();
            $balanceFromDB = 0;
            $newBal = $assOrigAmnt - $assCPayment;
			 
            $sqlUpdate1 = "UPDATE tblassissment SET fldBalance = $newBal WHERE fldEnrollmentNo = '$enrollmentNo' AND fldStudentNum = '$studentNo' 
        	AND fldAssessmentName = '$assName'";
             mysql_query($sqlUpdate1,$con);
        		 
        		 
            //close connection
            $this->closeCon();
        }
	
         function p_hasadvance($enrollmentNO,$studentNo,$autoId,$assName,$assessmentNo,$assBalance,$assAdvance,$assOrigAmnt,$assCPayment){
             $con = $this->openCon();
             $newBal = $assOrigAmnt - $assCPayment;

             $sqlUpdate1 = "UPDATe tblallAssessment SET fldOriginalBalance = $newBal, fldfldAdvancedPayment = $assAdvance WHERE fldId = $autoId AND fldEnrollmentNO='$enrollmentNO'
                            AND fldStudentNo='$studentNo'";
                            echo $sqlUpdate1;
             mysql_query($sqlUpdate1,$con);

             //Note: 04/15/2013 11: 25 AM
             //UNFINISHED..updating...
         }
        
        //delete all data from tblnextAssessment
        /*function p_deleteDNAss(){
            $sql = "DELETE FROM tblnextAssessment";
            mysql_query($sql, $this->openCon());
        }*/
     }
?>
