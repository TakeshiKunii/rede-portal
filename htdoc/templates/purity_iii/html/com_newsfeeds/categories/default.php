<?php

$params = array(
 "abbreviatedName"=>"Teste",
  "callbackRefund"=> "http://localhost:8083/api/portal/e",
  "cpfCnpj"=>'11111111111',
  "email"=>"teste@teste.com",
  "fantasyName"=>"Teste",
  "phoneNumber"=>'(00)00000-0000',
  "site"=> "http://www.userede.com.br.com.br/desenvolvedores",
  "socialReasonName"=>"Teste");
        

//$json_data = json_encode($params);

$ch = curl_init("https://10.7.82.171:8065/gy3/saude"); 
//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_GET, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
$myjson = json_decode($result);
curl_close($ch);


echo $result;

?>