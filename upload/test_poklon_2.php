<?php
//echo phpinfo();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$ip_server = $_SERVER['SERVER_ADDR'];

// Printing the stored address
//echo "Server IP Address is: $ip_server";

//echo'<br>';

// TEST :: http://luceedapi-test.tomsoft.hr:3676/datasnap/rest/

//$url        =  'http://luceedapi.tomsoft.hr:3675/datasnap/rest/artikli/lista';
//$url      =  'http://luceedapi.tomsoft.hr:3675/datasnap/rest/artikli/sifra/124758';
//$url      =  'http://luceedapi.tomsoft.hr:3675/datasnap/rest/StanjeZalihe/Skladiste/[001M,D38,D46,D73,D68,D53,D44,D47,P31,D16,D25,D11,D69,D13,D19,D70,D61,D30,D31,D32,K01,D64,D14,D71,D04,D72,D36,D52,D51,D50,D45,D42,D48,D49,D72,D22,D20,D17,D54,D55,D66,D28,D26,D27,D09,D18,D15,D67,D06,WA,D43,001,D12,D29]/124758';
// http://luceedapi.tomsoft.hr:3675/datasnap/rest/artikli/atribut/atribut_uid/59-2987
// http://luceedapi.tomsoft.hr:3675/datasnap/rest/artikli/dokumenti/37107-2987

//$url      = 'http://luceedapi.tomsoft.hr:3675/datasnap/rest/akcije/lista';

// http://luceedapi.tomsoft.hr:3675/datasnap/rest/vrsteplacanja/list

// http://luceedapi.tomsoft.hr:3675/datasnap/rest/grupeartikala/lista

// http://luceedapi.tomsoft.hr:3675/datasnap/rest/robnemarke/lista

// http://luceedapi.tomsoft.hr:3675/datasnap/rest/skladista/lista
// http://luceedapi.tomsoft.hr:3675/datasnap/rest/skladista/sifra/101

// http://luceedapi.tomsoft.hr:3675/datasnap/rest/vrsteplacanja/list

// http://luceedapi.tomsoft.hr:3675/datasnap/rest/partneri/naziv/
// http://luceedapi.tomsoft.hr:3675/datasnap/rest/partneri/uid/61259-2987

// http://luceedapi-test.tomsoft.hr:3676/datasnap/rest/StanjeZalihe/Skladiste/101
// http://luceedapi.tomsoft.hr:3675/datasnap/rest/StanjeZalihe/Skladiste/[101,001]/9150032160

// http://luceedapi-test.tomsoft.hr:3676/datasnap/rest/partneri/email/ljubica.polimac@amds.hr

//$url      = 'http://luceedapi.tomsoft.hr:3718/datasnap/rest/StanjeZalihe/Skladiste/[101,001]/42177';
//$url      = 'http://luceedapi.tomsoft.hr:3718/datasnap/rest/akcije/lista';
//$url      = 'http://luceedapi.tomsoft.hr:3718/datasnap/rest/StanjeZalihe/Skladiste/[101,001]/126957';

//$url      =  'http://luceedapi.tomsoft.hr:3718/datasnap/rest/StanjeZalihe/Skladiste';


//$url ='http://luceedapi.tomsoft.hr:3718/datasnap/rest/artikli/lista/[0,10]';
//$url ='http://luceedapi.tomsoft.hr:3718/datasnap/rest/vrsteplacanja/list';
//$url ='http://luceedapi.tomsoft.hr:3718/datasnap/rest/StanjeZalihe/Skladiste/128007';
//$url ='http://luceedapi.tomsoft.hr:3718/datasnap/rest/StanjeZalihe/Skladiste/';

//$url ='http://luceedapi.tomsoft.hr:3718/datasnap/rest/artikli/lista/[0,500]';

//$url ='http://luceedapi.tomsoft.hr:3718/datasnap/rest/StanjeZalihe/Skladiste/134944';

//$url = 'http://luceedapi.tomsoft.hr:3718/datasnap/rest/StanjeZaliheSerijski/Skladiste';
//$url = 'http://luceedapi.tomsoft.hr:3718/datasnap/rest/StanjeZaliheSerijski/serijski/990-0199-060478';
//$url = 'http://luceedapi-test.tomsoft.hr:3675/datasnap/rest/StanjeZaliheSerijski/serijski/EBN15';

$url ='http://luceedapi.tomsoft.hr:3718/datasnap/rest/PoklonBonovi/Stanje/serijskibroj/990-0199-321717';

$username = 'webshop';
$password = '8pdmJH2e';
$ch       = curl_init($url);
curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);

//$response = json_encode($response, JSON_PRETTY_PRINT);
header('Content-Type: application/json');
//echo 'Poziv: <span style="color: darkolivegreen">' . $url . '</span><br><hr><br>';

//echo $url;
echo $response;

if (curl_errno($ch)) {
    echo curl_error($ch);

}







