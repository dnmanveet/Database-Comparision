<html>
    


<?php 
    include 'connection.php';
    $queries = array();
    parse_str($_SERVER['QUERY_STRING'], $queries);
    $GLOBAL_FLAG=0;

    $db1 = $queries['db1'];
    $db2 = $queries['db2'];
    
    
    $conn1 = mysqli_connect($details[$db1]["server"], $details[$db1]["username"], $details[$db1]["password"], $db1);
    $conn2 = mysqli_connect($details[$db2]["server"], $details[$db2]["username"], $details[$db2]["password"], $db2);
 
    
    $table = $queries['table'];
    $query = "SELECT * from $table";
    $result1 = mysqli_query($conn1,$query);

    if(mysqli_num_rows($result1)==0){
        echo "DELETE FROM $db2.$table";
        $GLOBAL_FLAG=1;
    }
  
    
    while($data = mysqli_fetch_assoc($result1)){
        $index = array_key_first($data);
        $value_of_firstcol = $data[$index];
        $query = "SELECT * from $table where $index =  $value_of_firstcol";
        $keys = array_keys($data);
        $keys = implode(",",$keys);
        $result = mysqli_query($conn2,$query);
        $values = '';
        $type_array = array();



        foreach($data as $k =>  $v){

            $query = "SELECT data_type FROM information_schema.columns WHERE table_schema = '$db1' AND table_name = '$table' AND column_name = '$k' ";
            $type  =  mysqli_query($conn1,$query);
            $type_array[$k]=  mysqli_fetch_assoc($type)['data_type'];
            $type  = $type_array[$k];
            
            if($type=='varchar' || $type == 'char')
                    {
                        $values= $values ." '$v' ".", ";
                    }
                    else{
                        $values = $values ." $v".", ";

                    }
            
        }
        $values = rtrim($values,", ");
        
        if(mysqli_num_rows($result)==0){
            $query = "INSERT INTO $db2.$table ($keys) VALUES ($values)";
            echo $query."<br>";
            $GLOBAL_FLAG=1;

        }
        
        else{
            $flag = 0;
            $row = mysqli_fetch_assoc($result);
            $update_keys = array();
            $querystring = '';
            foreach($row as $key => $value){
                
                if($data[$key]!= $value){
                    $flag =1 ;
                    
                    if($type_array[$key]=='varchar' || $type_array[$key] == 'char')
                    {
                        $querystring = $querystring .$key. '='.  " '$data[$key]' ".", ";
                    }
                    else{
                        $querystring = $querystring .$key. '='.  " $data[$key] ".", ";

                    }
                }
            }
            
            $querystring = rtrim($querystring,", ");
            if($flag == 1){
                echo " UPDATE  $db2.$table SET $querystring WHERE $index = $value_of_firstcol; "."<br>";
                $GLOBAL_FLAG=1;
            }
            }
        }
    if($GLOBAL_FLAG==0){
        echo "No changes need to be done";
    }

   

?>


</html>