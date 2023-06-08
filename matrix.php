<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>matrix</title>
    <style>
        table,tr,th,td{
            border:1px solid black;
            border-collapse:collapse;
            padding:10px;
            margin: auto;
        }
       
    </style>
</head>
<body>
    <table id="table">
        

    </table>
</body>

<script>
    let table = document.getElementById("table")
    database_names = ['local','master','prod','qa']
    
    let row = document.createElement('tr')
    row.innerHTML+="<td>   </td>"
    for(i=0; i<database_names.length; i++){
        
        row.innerHTML +=`<td>${database_names[i]}</td>`

    }
    
    table.append(row)

    for(i=0; i<database_names.length; i++){

        let row = document.createElement('tr')
        row.innerHTML +=`<td>${database_names[i]}</td>`

        for(j=0; j<database_names.length; j++){
            if(i==j){
                row.innerHTML += "<td>   </td>"
            }
            else{
                row.innerHTML +=`<td><button class="btn1"> Schema  </button> <button class="btn2"> Master </button></td>`
            }

        }
        
       table.append(row); 
    }
    
    function compare(event){
        let className = event.target.getAttribute("class")
        let tr = event.target.closest("tr");
        db1=tr.children[0].innerHTML;

        let td = event.target.closest('td');
        db2 =  database_names[td.cellIndex-1]
        console.log(db1)
        console.log(db2)
        if(className=="btn1")
        {
      
       
       

        window.open(`http://localhost/phppractise/SchemasComparision.php?db1=${db1}&db2=${db2}`)
      }
      else{
       
        window.open(`http://localhost/phppractise/database.php?db1=${db1}&db2=${db2}`)

      }
      

    }
    


btn1 = document.getElementsByClassName("btn1");
for(i=0; i<btn1.length; i++){
    btn1[i].addEventListener("click",compare)
}


btn2 = document.getElementsByClassName("btn2");
for(i=0; i<btn2.length; i++){
    btn2[i].addEventListener("click",compare)
}




</script>
</html>