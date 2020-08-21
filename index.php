<?php
$token="YOUR TOKEN";
//Pošlje POST na API in vrne na novo prejeto sporočilo
$url = 'https://dev.nikigre.si/sms/api.php';
$data = array('func' => '0001', 'user' => 'USER NAME');


$options = array(
    'http' => array(
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($data)
    )
);

$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if($result != "NO RECEIVED SMS")
{
    //Iz rezultata dobi telefonsko številko in iskanje in ju shrani
    $polje= explode("'",$result);
    
    $stevilka= $polje[3];
    $iskanje= $polje[5];
    
    //Preveri, če slučajno uporabnik potrebuje pomoč pri iskanju in če jo, mu pošlje podatke kako iskati.
    if($iskanje=="pomoč" || $iskanje=="pomoc" || $iskanje=="POMOČ" || $iskanje=="POMOC" || $iskanje=="help" || $iskanje=="Help" || $iskanje=="HELP")
    {
        PosljiSMS("Pozdravljeni!\nZa iskanje po besedilih, pošljite SMS z ključno besedo t [iskani izraz].\nIskani izraz lahko vsebuje šumnike.\nPrimer: t bad guy.\nLep pozdrav", $stevilka);
    }
    else{
        
        //Zahteva se pošlje na strežnik z iskanim pojmom/besedo
        $odgovor = file_get_contents("https://api.genius.com/search?access_token=" . $token . "&q=" . urlencode($iskanje));
        
        //Podatke pretvori iz JSON v asociativno polje
        $assocArray = json_decode($odgovor, true);
        
        //Pripravimo spremenljivko, ki vsebuje podatke, ki so najpomembnejši
        $podatki=$assocArray["response"]["hits"];
        
        //Z zanko obdelamo vse podatke in jih shranimo v novo polje
        $rezultat=array();

        foreach($podatki as $result)
        {
            $rezultat[] = array($result["result"]["full_title"], "https://genius.com" . $result["result"]["path"]);
        }
        
        
        //Z novo zanko gremo čez polje z osnovnimi informacijami in sproti sestavljamo sporočilo
        $noviRezultati=array();
        
        $i =0;
        $sporocilo="Rezultati za vašo zahtevo:\n";
        foreach ($rezultat as $rezu)
        {
            if($i==1)
                break;
            //$noviRezultati[] =array($rezu[0],"https://l.nikigre.si/" . LinkerNarediLink($rezu[1]));
            $sporocilo .="Naslov: " . $rezu[0] . "\nURL: https://l.nikigre.si/" . LinkerNarediLink($rezu[1]) . "\n";
            $i++;
        }
        
        //Sporočilo se pošlje na izbrano telefonsko številko
        if(strlen($sporocilo)>2)
        {
            PosljiSMS($sporocilo, $stevilka);
        }
        else{
            PosljiSMS("Odgovora ne najdem!", $stevilka);
        }
    }
}
else{
    echo "No Info received";
}
//Pošlje SMS na določeno številko in vrne OK če je poslano
function PosljiSMS($sms, $tel)
{
    $url = 'https://dev.nikigre.si/sms/api.php';
    $data = array('func' => '10000', 'user' => 'USER NAME', 'message' => $sms, 'phone' => $tel);


    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        )
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    if ($result === FALSE) { echo "Error"; } else { echo "OK"; }
}

//Metoda naredi kratko povezavo iz dane povezave in vrne samo kratekURL
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

//Metoda zgenerira naključno kodo
function generateRandomString($length = 4) {
    $characters = 'abcdefghijklmnoprstuvzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

//Metoda preveri, ali nakključna koda obstaja
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

//Metoda vpiše v bazo kratico in URL
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

//Metoda preveri, če je že za ta URL naredil povezavo in če je vrne kratko kodo
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