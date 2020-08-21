<?php

$odgovor = file_get_contents("https://api.genius.com/search?access_token=TOKEN&q=Someone+like+you");
//echo $odgovor;
$assocArray = json_decode($odgovor, true);

var_dump($assocArray["response"]["hits"]);

$podatki=$assocArray["response"]["hits"];

$rezultat=array();
echo "--------------------------------------------------------------";

foreach($podatki as $result)
{
    $rezultat[] = array($result["result"]["full_title"], "https://genius.com" . $result["result"]["path"]);
}

var_dump($rezultat);

$noviRezultati=array();

$i =0;
foreach ($rezultat as $rezu)
{
    if($i==3)
        break;
    $noviRezultati[] =array($rezu[0],"https://l.nikigre.si/" . LinkerNarediLink($rezu[1]));
    $i++;
}

echo "---------------------------------------------------------------------------------------";

var_dump($noviRezultati);

function LinkerNarediLink($url)
{
    $rezultat=PreveriCeZeURLobstaja($url);
    if($rezultat != "")
        return $rezultat;
    
    $okej==FALSE;
    $random="";
    while($okej==FALSE)
    {
        $random= generateRandomString();
        $okej= AliNeObstaja($random);
    }
    
    VpisiVbazo($random,$url);
    
    return $random;
    
}

function generateRandomString($length = 4) {
    $characters = 'abcdefghijklmnoprstuvzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function AliNeObstaja($kratica)
{
    include("db.php");

    $sql = "SELECT Kratica FROM Linker WHERE Kratica='" . mysqli_real_escape_string($conn, $kratica) ."';";

    $result = $conn->query($sql);

    if ($result->num_rows == 0) {

        return TRUE;
    } else {

      return FALSE;
    }
$conn->close();
}

function VpisiVbazo($kratica, $url)
{
    include("db.php");
    $sql = "INSERT INTO Linker (Kratica, Link, IP) VALUES ('" . mysqli_real_escape_string($conn, $kratica) . "','" . mysqli_real_escape_string($conn, $url) . "', '" . mysqli_real_escape_string($conn, $_SERVER['REMOTE_ADDR']) . "')";

    if ($conn->query($sql) === TRUE) {
        return TRUE;
    } else {
        return "Error: " . $sql . "<br>" . $conn->error;
    }
$conn->close();
}

function PreveriCeZeURLobstaja($URL)
{
    include("db.php");

    $sql = "SELECT Kratica FROM Linker WHERE Link='" . mysqli_real_escape_string($conn, $URL) ."';";

    $result = $conn->query($sql);
    $kratica="";
    if ($result->num_rows == 0) {
        return "";
    } else {
        while($row = $result->fetch_assoc()) {
            $kratica= $row["Kratica"];
        }
    }
    $conn->close();
    
    return $kratica;

}


?>