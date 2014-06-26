<html>
    <head>
        <link rel="stylesheet" href="search.css">
    </head>
    <body>
        <form id="clientsearch">
            <label for="sitename">clients.sitename LIKE </label>
            <input type="text" name="sitename"></input>
            <input type="submit" value="Submit">
        </form>
<?php
    if(isset($_GET["sitename"]) && $_GET["sitename"] !== ""){
        $mysqli = mysqli_connect("ded-2301.prod.hosting.acquia.com", "radashstg", "pYvhDvsqUBpnaxQ", "radashstgdb8142");
        $sitename = $mysqli->real_escape_string($_GET["sitename"]);
        $columns = array(
            "sub",
            "sitename",
            "update_inform",
            "update_auto",
            "deploy_auto",
            "update_source",
            "deploy_testenv",
            "deploy_testdb",
            "deploy_cptestdb",
        );
        $column_names = array(
            "sub"=>"Sub (CCI)",
            "sitename"=>"Sitename",
            "update_inform"=>"Inform",
            "update_auto"=>"Update",
            "deploy_auto"=>"Deploy",
            "update_source"=>"Branch Source",
            "deploy_testenv"=>"Test Env",
            "deploy_testdb"=>"Test DB",
            "deploy_cptestdb"=>"Copy Test DB",
        );
        $column_string = implode(", ",$columns);
        $result = $mysqli->query("
            SELECT  ".$column_string." FROM clients
                WHERE sitename LIKE '%".$sitename."%'
                LIMIT 10;");
        if(!$result){
            echo "No results found.";
            die;
        }
        echo "<table><thead>";
        foreach($columns as $column){
            echo "<th>" . $column_names[$column] . "</th>";
        }
        echo "</thead><tbody>";
        while($row = $result->fetch_assoc()){
            echo "<tr>";
            foreach($columns as $column){
                echo "<td>".$row[$column]."</td>";
            }
            echo "</tr>";
        }
    }
?>
</tbody>
</table>
</body>
</html>
