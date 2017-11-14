<!DOCTYPE html>
<html>
    <?php
    $alert=""; //alert Message
    $query=""; //to resend user query user
    $result_table = ""; //result

    function validatequery(&$query)
    {
        $query = str_replace(' ','',$query); //trim
        //$reg = '/^<([A-D](([:][0]\.[0-9]*)|([:][1])|())[;])+>$/'; old one
        $reg = '/^([A-E])+$/';
        return preg_match($reg,$query);
    }

    if(isset($_POST['query']))
    {
        if(!empty($_POST['querytxt']))
        {
            $query = htmlspecialchars($_POST['querytxt']);

            if(validatequery($_POST['querytxt']))
            {
                $records = array('A'=>array(0,0,0),'B'=>array(0,0,0),'C'=>array(0,0,0),'D'=>array(0,0,0),'E'=>array(0,0,0)); //records 
                $itf_records = array('A'=>array(0,0,0),'B'=>array(0,0,0),'C'=>array(0,0,0),'D'=>array(0,0,0),'E'=>array(0,0,0)); //tf
                $idfi_records =  array('A'=>0,'B'=>0,'C'=>0,'D'=>0,'E'=>0);//idf
                $weights = array('A'=>array(0,0,0),'B'=>array(0,0,0),'C'=>array(0,0,0),'D'=>array(0,0,0),'E'=>array(0,0,0)); //weight
                $Files = array(fopen("Document_1.txt", "r"),fopen("Document_2.txt", "r"),fopen("Document_3.txt", "r"));
                $file_counter=0;
                $maxValue=0;
                $counter=0;
                foreach($Files as $file)
                {
                    while(!feof($file)) 
                    {
                        $key = fgetc($file);
                        if(ord($key)>=65&&ord($key)<=69)
                        {
                            $records[$key][$file_counter] = $records[$key][$file_counter]+1;
                        }
                    }
                    $maxValue =$records['A'][$file_counter];
                    foreach($records as $key=>$value)
                    {
                        if($records[$key][$file_counter] > $maxValue)
                            $maxValue = $records[$key][$file_counter];
                    }
                    foreach($itf_records as $key=>$value)  //calculate itf_records
                    {
                        $itf_records[$key][$file_counter] = $records[$key][$file_counter] / $maxValue;
                        //echo '<strong>Key: '.$key.' For file: '.$file_counter.' = '. $itf_records[$key][$file_counter].'<strong><br>';
                    }
                    //echo '<br><br>';
                    fclose($file);
                    $file_counter++;
                    $maxValue = 0;
                }
                
                //calculate idfi 
                foreach($records as $key=>$value)
                {
                    for ($i=0;$i<3;$i++)
                            if($records[$key][$i]!=0)
                                $counter++;
                    $idfi_records[$key] = log(3/$counter,2);
                    //echo '<strong>Key: '.$key.' = '.$idfi_records[$key].'<strong><br>';
                    $counter = 0;
                }
                
                //lets calculate weights ^_^
                
                foreach($weights as $key=>$value)
                {
                    for($i =0; $i<3;$i++)
                    {
                        $weights[$key][$i] = $itf_records[$key][$i] * $idfi_records[$key];
                    //echo '<strong>WEIGHT: '.$weights[$key][$i].'</strong><br>';
                    }
                   // echo '<br><br>';
                }
                
                //lets do it for query
                $Qrecords = array('A'=>0,'B'=>0,'C'=>0,'D'=>0,'E'=>0); //Query records 
                $Qitf_records = array('A'=>0,'B'=>0,'C'=>0,'D'=>0,'E'=>0); //itf
                $Qweights = array('A'=>0,'B'=>0,'C'=>0,'D'=>0,'E'=>0);
                $query = $_POST['querytxt'];
                
                for($i=0;$i<strlen($query);$i++)
                {
                    $Qrecords[$query[$i]]=$Qrecords[$query[$i]]+1;
                }
                 arsort($Qrecords);
                 foreach($Qrecords as $key=>$value)
                 {
                    $maxValue=$Qrecords[$key];
                    //echo $maxValue. '<br>';
                    break;
                 }
                //calculate $Qitf
                 foreach($Qrecords as $key=>$value)
                {
                    $Qitf_records[$key]=$Qrecords[$key] /$maxValue ;
                     //echo '<strong>Key: '.$key.' = '.$Qitf_records[$key].'<strong><br>';
                }
                //calculate QWEIGHT
                  foreach($Qweights as $key=>$value)
                {
                    $Qweights[$key]=$Qitf_records[$key] * $idfi_records[$key];
                    //echo '<strong>WEIGHT: '.$Qweights[$key].'</strong><br>';
                }
                
                //lets calculate Cousine similarity Between files & Query.
                $upTerm =0;
                $sumofWeight = 0;
                $sumofQWeight = 0;
                $score = array('1'=>0,'2'=>0,'3'=>0);
                for($i=0;$i<3;$i++)
                {
                    foreach($weights as $key=>$value)
                    {
                        $upTerm += $weights[$key][$i]*$Qweights[$key];
                        $sumofWeight +=pow($weights[$key][$i],2);
                        $sumofQWeight +=pow($Qweights[$key],2);
                    }
                    $l ="".($i+1);
                   // echo $l.'<br>';
                    if($sumofWeight==0||$sumofQWeight==0)
                       {
                               $score [$l[0]]=0;
                       }
                    else{
                            $score [$l[0]] = $upTerm / sqrt($sumofWeight *$sumofQWeight);
                           //echo sqrt($sumofWeight *$sumofQWeight).'<br>';
                       }
                    $upTerm =0;
                    $sumofWeight = 0;
                    $sumofQWeight = 0;
                }
                /*$arry = explode(";",($_POST['querytxt']));
                array_pop($arry);
                $arry[0]= str_replace('<','',$arry[0]);

                foreach($arry as $split)
                {
                    if(strlen($split)!=1)
                    {
                        $value = floatval(substr($split,2,strlen($split)));
                    }
                    else
                        $value = 1;

                    $score['1']=$score['1']+$records[substr($split,0,1)][0]*$value;
                    $score['2']=$score['2']+$records[substr($split,0,1)][1]*$value;
                    $score['3']=$score['3']+$records[substr($split,0,1)][2]*$value;
                }*/
                arsort($score);

                $result_table = ' <div class="form"><table>
  <tr>
    <th>File</th>
    <th>Score</th> </tr>';
                foreach($score as $k=>$v)
                { 
                    $result_table = $result_table . ' <tr> <td>'.$k.'</td>
    <td>'.$v.'</td>';
                    //echo '<br> '.$k.': '.$v;
                }
                $result_table = $result_table . '</table></div>';
            }
            else
            {
                $alert ='<div class="alert warning">
  <span class="closebtn" onclick="this.parentElement.style.display=\'none\';">&times;</span> 
  <strong>Wrong format!! </strong> Only A B C D E Char are allowed <strong> Captial letters </strong>.
</div>';

            }
        }
        else
        {
            $alert ='<div class="alert warning">
  <span class="closebtn" onclick="this.parentElement.style.display=\'none\';">&times;</span> 
  <strong>Its Empty!! </strong> Please Fill it  
</div>';
        }
    }

    elseif(isset($_POST['gen']))
    {
        $Files = array(fopen("Document_1.txt", "w"),fopen("Document_2.txt", "w"),fopen("Document_3.txt", "w"));
        foreach($Files as $file)
        {
            for($i=0;$i<rand(3,6);$i++) //to AVOID IDF WITH ZERO VALUES.
            {
                fwrite($file,chr(rand(65,69)));
            }
        }
        $alert ='<div class="alert done">
  <span class="closebtn" onclick="this.parentElement.style.display=\'none\';">&times;</span> 
  <strong>Successfully Generated!! </strong>file now ready to be used :)
</div>';
    }
    else{
    }

    ?>

    <head>
        <title>Searcheko</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <center><img src="Design.png"></center>
        <form class="form" action="" method="post">
            <?php echo $alert ?>
            <input type="text" name="querytxt" placeholder="Search Me" value="<?php echo $query ?>">
            <center>
                <span></span>
                <br><input type="submit" name="query" value="Query"><input type ="submit" name="gen" value="Generate"></center>
        </form>
        <?php echo $result_table ?>
    </body>
</html>