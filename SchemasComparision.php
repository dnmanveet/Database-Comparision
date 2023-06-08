<?php 
/**------------------------------------------Connections.php---------------------------------------- */
    // $db1 = $_GET['db1'];
    // $db2 = $_GET['db2'];
    $queries = array();
    parse_str($_SERVER['QUERY_STRING'], $queries);
    // echo $_SERVER['QUERY_STRING'];

    $db1 = $queries['db1'];
    $db2 = $queries['db2'];
    include "connection.php";
    // $details = [
    //     "local"=>[
    //         "server"   => "localhost",
    //         "username" => "root",
    //         'password' => "",
    //     ],
        
    //     "master"=>[
    //         "server"   => "localhost",
    //         "username" => "root",
    //         'password' => "",
    //     ],
    //     "localhost"=>[
    //         "server"   => "localhost",
    //         "username" => "root",
    //         'password' => "",
    //     ],
    //     "prod"=>[
    //         'server'   => "localhost",
    //         'username' => "root",
    //         'password' => "",
    //     ],
    //     "qa"=>[
    //         "server"   => "localhost",
    //         "username" => "root",
    //         'password' => "",
    //     ],
    //     "DB2"=>[
    //         "server"   => "localhost",
    //         "username" => "root",
    //         'password' => "",
    //     ],
    // ];
    
    if(!isset($details[$db1]) || !isset($details[$db2])){
        echo "Error in db names";
        exit();
    }
    $conn1 = mysqli_connect($details[$db1]["server"], $details[$db1]["username"], $details[$db1]["password"], $db1);
    if(!$conn1){
        echo "Cannot connect to $db1",mysqli_connection_error();
    }
    $conn2 = mysqli_connect($details[$db2]["server"], $details[$db2]["username"], $details[$db2]["password"], $db2);
    if(!$conn2){
        echo "Cannot connect to $db2",mysqli_connection_error();
    }
    echo "<br/><br/>";
/**-------------------------------------------------------------------------------------------------- */

/**--------------------------------------------Comparing Tables-------------------------------------- */
function compareTables($conn1, $conn2,$db1, $db2){
    $get_tables_db1 = mysqli_query($conn1,'show tables');
    $db1_table_names = []; $db2_table_names = [];
    $flag1= true;
    while($table_name = mysqli_fetch_array($get_tables_db1)){
        array_push($db1_table_names, $table_name[0]);
        $flag1=false;
    }
    if(!count($db1_table_names)){
        echo "No table in Db1";
        exit();
    }
    else{
        $get_tables_db2 = mysqli_query($conn2,'show tables');
        $flag2=true;
        while($table_name = mysqli_fetch_array($get_tables_db2)){
            array_push($db2_table_names, $table_name[0]);
            $flag2=false;
        }
        $tables_not_in_db2 = array_values(array_diff($db1_table_names, $db2_table_names));
        if($tables_not_in_db2==[]){
            echo "<h3>All tables are in Synchrony</h3>";
        }
        else{
            for($i=0; $i<count($tables_not_in_db2); $i++){
                $table_name = $tables_not_in_db2[$i];
                $db1_cols = "SHOW CREATE TABLE $table_name";
                $res = mysqli_query($conn1, $db1_cols);
                $flag2=true;
                while($create_stmt = mysqli_fetch_array($res)){
                    echo "<pre>", "DROP TABLE IF EXISTS $table_name; </pre>";
                    echo "<pre>", $create_stmt[1],"<br/>";
                    $flag2=false;
                    echo "</pre>";
                }
            }
        }
    }
    $flag=true;
    for($i=0; $i<sizeof($db1_table_names); $i++){
        if(in_array($db1_table_names[$i], $db2_table_names)){
            $get_cols_db1 = mysqli_query($conn1, "SHOW COLUMNS FROM $db1_table_names[$i]");
            $get_cols_db2 = mysqli_query($conn2, "SHOW COLUMNS FROM $db1_table_names[$i]");
            echo "<pre>";
            $cols_db1_tab1=[]; $cols_db2_tab1=[]; 
            $col_names_db1=[]; $col_names_db2=[];
            while($cols_db1 = mysqli_fetch_array($get_cols_db1)){
                array_push($cols_db1_tab1, $cols_db1);
                array_push($col_names_db1, strtolower($cols_db1['Field']));
            }
            while($cols_db2 = mysqli_fetch_array($get_cols_db2)){
                array_push($cols_db2_tab1, $cols_db2);
                array_push($col_names_db2, strtolower($cols_db2['Field']));
            }
            $same_cols_names = array_intersect($col_names_db1, $col_names_db2);
            $cols_not_in_db2_tab1 =[];
            for( $j=0; $j<count($cols_db1_tab1); $j++){
                if(!in_array($cols_db1_tab1[$j], $cols_db2_tab1)){
                    array_push($cols_not_in_db2_tab1, $cols_db1_tab1[$j]);
                }
            }
            if($cols_not_in_db2_tab1==[]){
                $flag=false;
            }
            echo "</pre>";
            for($j=0; $j<count($cols_not_in_db2_tab1); $j++){
                $col_name = $cols_not_in_db2_tab1[$j]['Field'];
                $addORModify = (in_array(strtolower($col_name), $same_cols_names) ? 'MODIFY': 'ADD');
                $col_data_type = $cols_not_in_db2_tab1[$j]['Type'];
                $col_extra = '';
                if(isset($cols_not_in_db2_tab1[$j]['Extra'])){
                    $col_extra = $cols_not_in_db2_tab1[$j]['Extra'];
                }
                $col_key = '';
                $stmt='';
                if(isset($cols_not_in_db2_tab1[$j]['Key'])){
                    $col_key = $cols_not_in_db2_tab1[$j]['Key'];
                    if($col_key == 'PRI'){
                        $col_key = 'PRIMARY KEY';
                    }
                    else if($col_key == 'MUL'){
                        $col_key='';
                        $sql = "select *
                                    from information_schema.KEY_COLUMN_USAGE b
                                    JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS USING (CONSTRAINT_NAME)
                                    where b.TABLE_NAME = '$db1_table_names[$i]' AND b.REFERENCED_TABLE_NAME IS NOT NULL";
                        $res = mysqli_query($conn1, $sql);
                        
                        $key_info = mysqli_fetch_array($res);
                        echo "<pre>";
                        $stmt = "ALTER TABLE $db1_table_names[$i] ADD CONSTRAINT $key_info[CONSTRAINT_NAME] FOREIGN KEY ($key_info[COLUMN_NAME]) REFERENCES $key_info[REFERENCED_TABLE_NAME]($key_info[REFERENCED_COLUMN_NAME]) " ;
                        if($key_info["UPDATE_RULE"]){
                            $stmt .= " ON UPDATE $key_info[UPDATE_RULE]";
                        }
                        if($key_info["DELETE_RULE"]){
                            $stmt .= " ON DELETE $key_info[DELETE_RULE]; <br/>"; 
                        }
                        echo "</pre>";
                    }
                }
               
                $col_not_null=''; $col_default='';
                if(isset($cols_not_in_db2_tab1[$j]['Null'])){
                    $col_not_null = $cols_not_in_db2_tab1[$j]['Null'] == 'NO' ? 'not null': '';
                }
                if(isset($cols_not_in_db2_tab1[$j]['Default'])){
                    $col_default = $cols_not_in_db2_tab1[$j]['Default'];
                    $col_default = $col_default ? " default '$col_default'":'';
                }
                if($col_key=='PRIMARY KEY'){
                    $col_not_null='';
                }
                echo '<strong>',"ALTER TABLE $db2.$db1_table_names[$i] $addORModify ",$col_name,' ', $col_data_type, $col_default,' ', $col_extra, ' ', $col_key, ' ', $col_not_null,';','</strong>', "<br/>";
                if($stmt){
                    echo '<strong>', $stmt, '</strong>',"<br/>";
                }
            }
        }
    } 
    if($flag){
        echo "<h3> All columns are in Synchrony</h3>";
    }
}
// /**-----------------------------------------Comparing Columns--------------------------------------- */
// function compareComlumns($conn1, $conn2,$db1, $db2){
   
// }
/**-----------------------------------------Comparing Procedures------------------------------------- */ 
function compareProcedures($conn1, $conn2,$db1, $db2){   
    $get_procedures_db1 = mysqli_query($conn1,"SHOW PROCEDURE STATUS WHERE Db='$db1';");
    $procedures_db1= [];
    while($proc = mysqli_fetch_array($get_procedures_db1)){
        array_push($procedures_db1, $proc);
    }
    if($procedures_db1==[]){
        echo "There are no procedures in $db1<br>";
    }
    else{
        $get_procedures_db2 = mysqli_query($conn2,"SHOW PROCEDURE STATUS WHERE Db='$db2';");
        $procedures_db2 = [];
        while($proc = mysqli_fetch_array($get_procedures_db2)){
            array_push($procedures_db2, $proc);
        }
        $procedures_db2= is_countable($procedures_db2)?$procedures_db2:[];
        $procs_not_in_db2 = array_diff_assoc($procedures_db1, $procedures_db2);
        if($procs_not_in_db2==[]){
            echo "<h3>All procedures are in Synchrony</h3>";
        }
        for($i=0; $i<count($procs_not_in_db2); $i++){
            $proc_name = $procs_not_in_db2[$i]['Name'];
            $create_proc = mysqli_query($conn1, "show create procedure $proc_name");
            $create_proc_stmt = mysqli_fetch_array($create_proc);
            echo "<pre>", "DROP PROCEDURE IF EXISTS $proc_name", "</pre>";
            echo "<pre>",$create_proc_stmt["Create Procedure"], "</pre>";   
        }
    }
}
/**-----------------------------------------Comparing Functions--------------------------------------- */
function compareFunctions($conn1, $conn2,$db1, $db2){
    $get_functions_db1 = mysqli_query($conn1,"SHOW FUNCTION STATUS WHERE Db='$db1';");
    $functions_db1= [];
    while($func = mysqli_fetch_array($get_functions_db1)){
        array_push($functions_db1, $func);
    }
    if($functions_db1==[]){
        echo "No Function in $db1<br>";
    }
    else{
        $get_functions_db2 = mysqli_query($conn2,"SHOW FUNCTION STATUS WHERE Db='$db2';");
        $functions_db2 = [];
        while($func = mysqli_fetch_array($get_functions_db2)){
            array_push($functions_db2, $func);
        }
        $funcs_not_in_db2 = array_diff_assoc($functions_db1, $functions_db2);
        if($funcs_not_in_db2==[]){
            echo "<h3>All Functions are in Synchrony</h3>";
        }
        for($i=0; $i<count($funcs_not_in_db2); $i++){
            $func_name = $funcs_not_in_db2[$i]['Name'];
            $create_func = mysqli_query($conn1, "show create function $func_name");
            $create_function_stmt = mysqli_fetch_array($create_func);
            echo "<pre>", "DROP FUNCTION IF EXISTS $func_name", "</pre>";
            echo "<pre>",$create_function_stmt["Create Function"], "</pre>";  
        }
    }
}
/**-----------------------------------------Comparing Triggers--------------------------------------- */
function compareTriggers($conn1, $conn2,$db1, $db2){
    $get_triggers_db1 = mysqli_query($conn1, "SHOW TRIGGERS FROM $db1;");
    $triggers_db1= [];
    while($trigger = mysqli_fetch_array($get_triggers_db1)){
        array_push($triggers_db1, $trigger);
    }
    if(!count($triggers_db1)){
        echo "Triggers not in $db1";
    }
    else{
        $get_triggers_db2 = mysqli_query($conn2, "SHOW TRIGGERS FROM $db2;");
        $triggers_db2= [];
        while($trigger = mysqli_fetch_array($get_triggers_db2)){
            array_push($triggers_db2, $trigger);
        }
        $triggers_not_in_db2 = array_diff_assoc($triggers_db1, $triggers_db2);
        if($triggers_not_in_db2==[]){
            echo "<h3>All triggers are in Synchrony</h3>";
        }
        for($i=0; $i<count($triggers_not_in_db2); $i++){
            $trigger_name = $triggers_not_in_db2[$i]["Trigger"];
            $create_trigger = mysqli_query($conn1, "show create trigger $trigger_name");
            $create_trigger_stmt = mysqli_fetch_array($create_trigger);
            echo "<pre>", "DROP TRIGGER IF EXISTS $trigger_name", "</pre>";
            echo "<pre>", $create_trigger_stmt["SQL Original Statement"], "</pre>";  
        }
    }
}
/**----------------------------------------------------------------------------------------------------- */
compareTables($conn1, $conn2,$db1, $db2);
    // compareComlumns($conn1, $conn2,$db1, $db2);
    compareFunctions($conn1, $conn2,$db1, $db2);
    compareProcedures($conn1, $conn2,$db1, $db2);
    compareTriggers($conn1, $conn2,$db1, $db2);
?>