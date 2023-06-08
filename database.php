<html>
    <head>
        <style>
            h3{
                text-align:center;
            }
            table{
                margin : auto;
                line-height:30px;
            }
            table,tr,td{
                border: 1px solid black;
                border-collapse : collapse;
                padding : 10px;
            }

        </style>
    </head>       
    <body>
        <h3> Database Comparison</h3>
       





            <?php 
            include 'connection.php';
            $queries = array();
            parse_str($_SERVER['QUERY_STRING'],$queries);
            
            $db1 = $queries['db1'];
            $db2 = $queries['db2'];
            echo " <table>
            <tr>
                <td>Tables</td>
                <td>$db1</td>
                <td>$db2</td>
                <td>Action</td>
            </tr>";
                            
            
            
            $conn1 = mysqli_connect($details[$db1]["server"], $details[$db1]["username"], $details[$db1]["password"], $db1);
            $conn2 = mysqli_connect($details[$db2]["server"], $details[$db2]["username"], $details[$db2]["password"], $db2);
         


            $query = "SHOW TABLES FROM $db1";
            $result = mysqli_query($conn1,$query);
            

            while($table = mysqli_fetch_assoc($result)){

                $firstkey =  array_key_first($table);
                $table1   =  $table[$firstkey];
                $table2   =  "SELECT * FROM INFORMATION_SCHEMA.TABLES
                              WHERE table_name = '$table1' AND table_schema = '$db2' ";
                $table2 =   mysqli_fetch_assoc(mysqli_query($conn2,$table2));
                
                if(!$table2){
                    continue;
                }
                

                $query    =  "SELECT * from $table1";

                
                $no1      =  mysqli_num_rows(mysqli_query($conn1,$query));
                $no2      =  mysqli_num_rows(mysqli_query($conn2,$query));
                
                if($no2 != $no1 ){
                    $tag = "<td>$no1</td>
                            <td style = 'background-color : red'>$no2</td>";

                }
                else{
                    $tag = "<td>$no1</td>
                            <td >$no2</td>";
                }

                echo "<tr>
                        <td>$table1</td>
                        $tag
                        <td><button class='action'>click</button></td>
                    </tr>";

            }






// $result1 = mysqli_query($conn1,$x);
// $result2 = mysqli_query($conn2,$x);


// if(mysqli_num_rows($result1)>0){

//     while($data = mysqli_fetch_assoc($result1)){
    
//         $id        =  $data['id'];
//         $firstName =  $data['firstname'];
//         $lastName  =  $data['lastname'];
//         $email     =  $data['email'];



//         $query = "SELECT * FROM tab1 where id=$id";
//         $result = mysqli_query($conn2,$query);
    
//         if(mysqli_num_rows($result)==0){
//             echo "in ";
//             echo $id;
//             $q= "INSERT INTO tab1
//                     VALUES ($id,'$firstName','$lastName','$email');";
//             echo $q; 
            
           
          
          
//         }
//         else{
            
//             $data1 = mysqli_fetch_assoc($result);
//             print_r ($data1);
//             echo "<br>";
//             print_r($data);
//             echo "<br><pre>";
//             $difference = array_diff($data,$data1);
//             if(count($difference)>0){
//               echo "<pre>";
//               foreach($difference as $key=> $value)
//               {
//                 echo $key : $value;
//               }
              
//             }


//         }


//     }

// }




?>

        </table>
    </body>
    <script>




        function compare(){
            let url = new URL(document.URL);
            let searchparams = new URLSearchParams(url.search);
            db1 = searchparams.get('db1');
            db2 = searchparams.get('db2');
            console.log(searchparams);
            
            tablerow = event.target.closest("tr");
            childtags = tablerow.children;
            table = childtags[0].innerHTML;
            no_of_rows1 = childtags[1].innerHTML;
            no_of_rows2 = childtags[2].innerHTML;
            if((no_of_rows1==0)&&(no_of_rows2==0)){
                return;
            }
            else{
                console.log("hi")
                console.log(childtags[2])
                let url = `compare.php?table=${table}&no_of_rows1=${no_of_rows1}&no_of_rows2=${no_of_rows2}&db1=${db1}&db2=${db2}`;
                console.log(url);
                window.open(url);

            }
            
            
        }
        let buttons = document.querySelectorAll(".action");
        for(i of buttons){
            i.addEventListener("click",compare);
        }

    </script>


</html>
