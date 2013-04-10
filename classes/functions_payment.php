<?php
include "connection.php";
     class payment extends DB_Connect{
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
            $sql1 = "SELECT MAX(fldAssessmentNo) FROM tblpreparedAssessment WHERE fldEnrollmentNo = '$enrollmentNo' AND fldStudentNo = '$studentNo'";
            $result1 = mysql_query($sql1,$con);
            $row1 = mysql_fetch_array($result1);
            $assNo = $row1[0];
            //
            $sql2 = "SELECT fldId,fldTransactionNo,fldEnrollmentNo,fldStudentNo,fldAssessmentName, fldOriginalAmount,
            fldOriginalBalance, fldAssessmentAmount, fldAdvancedPayment FROM tblpreparedAssessment 
            WHERE fldEnrollmentNo = '$enrollmentNo' AND fldStudentNo = '$studentNo' AND fldOriginalBalance 
            !=0 AND fldAssessmentAmount !=0 AND fldAssessmentNo = $assNo ";
            
            $result2 = mysql_query($sql2, $con);
            $notFound = true;
            while($row2 = mysql_fetch_array($result2)){
                echo "<tr>";
                echo "<td><span id=assName".$row2[0].">".$row2[4]."</span></td>";
                echo "<td><span id=assAmnt".$row2[0].">".$row2[7]."</span></td>";
                echo "<td><span id=assBalance".$row2[0].">".$row2[7]."</span></td>";
                echo "<td><input type='text' id=c_payment".$row2[0]." onkeyup = 'computeTotalCPayment(".$row2[0].")'/>
                <input type='hidden' id=assNum value=".$assNo." /><input type='hidden' id=assOrigAmnt".$row2[0]." value=".$row2[6]." /></td>";
                echo "<td><span id=advanceP".$row2[0].">0</span></td>";
                echo "</tr>";
                
                
            	//INSERT NEW ASSESSMENT
            	$transNo = $row2[1];
            	$enrollNo = $row2[2];
            	$studentNo = $row2[3];
            	$assName = $row2[4];
            	$origAmount = $row2[5];
            	$origBal = $row2[6];
            	$assAmount = $row2[7];
            	$advance = $row2[8];
            	$assNum = $assNo + 1;
            	//note: tanggalin ang /**/
            	
            	$sqlInsertA = "INSERT INTO tblpreparedAssessment (fldTransactionNo,fldEnrollmentNo,fldStudentNo,fldAssessmentName,fldOriginalAmount,
            	fldOriginalBalance,fldAssessmentAmount,fldAdvancedPayment,fldAssessmentNo) 
            	VALUES ('$transNo','$enrollNo','$studentNo','$assName','$origAmount','$origBal','$assAmount','$advance','$assNum')";
            	mysql_query($sqlInsertA,$con);
               
               $notFound = false;
            }
				if($notFound){
					echo "<tr><td>No assessment found!!!!</td></tr>";
				}
            //close connection
            $this->closeCon();
            /*NOTE:
                1. Minus the original balance(update tblpreparedAssessment and tblassissment)
                2. set fldAdvanced payment
            */ 
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
        		/*$sql1 = "SELECT fldBalance FROM tblassissment WHERE fldEnrollmentNo = '$enrollmentNo' AND fldStudentNum = '$studentNo'
        		 AND fldAssessmentName = '$assName'";
        		 $result1 = mysql_query($sql1,$con);
        		 $row1 = mysql_fetch_array($result1);
        		 $balanceFromDB = $row1[0];*/
        		 $newBal = $assOrigAmnt - $assCPayment;
        		 
        		  /* Note: 
        		        1.dapat machange ang balance ng current assessment
        		 */
        		 $sqlUpdate1 = "UPDATE tblassissment SET fldBalance = $newBal WHERE fldEnrollmentNo = '$enrollmentNo' AND fldStudentNum = '$studentNo' 
        		 AND fldAssessmentName = '$assName'";
        		 mysql_query($sqlUpdate1,$con);
        		 
        		 
        		//close connection
        		$this->closeCon();
        }

         //HAS BALANCE
         function p_hasadvance($enrollmentNO,$studentNo,$autoId,$assName,$assessmentNo,$assBalance,$assAdvance,$assOrigAmnt){
             $con = $this->openCon();

             $sqlUpdate1 = "UPDATe tblpreparedAssessment SET fldAdvancedPayment = $assAdvance WHERE fldId = $autoId AND fldEnrollmentNo='$enrollmentNO'
                            AND fldStudentNo='$studentNo'";
                            echo $sqlUpdate1;
             mysql_query($sqlUpdate1,$con);
         }
        
     }
?>
