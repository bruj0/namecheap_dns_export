<?php
$dom=$argv[1];
$loginurl="https://www.namecheap.com/myaccount/signout?loggedout=yes";
$url='https://ap.www.namecheap.com/Domains/dns/GetAdvancedDnsInfo?domainName='.$dom;


$post = [
    'LoginUserName' => $argv[2],
    'LoginPassword' => $argv[3],
];
$agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36';
$ch = curl_init($loginurl);
curl_setopt($ch, CURLOPT_POST, 1);
//curl_setopt($ch, CURLOPT_VERBOSE, true);
curl_setopt($ch, CURLOPT_USERAGENT,$agent);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
curl_setopt($ch, CURLOPT_HEADER, 1);

$cookies=[];

$curlResponseHeaderCallback  = function ($ch, $headerLine) use (&$cookies) {
    if (preg_match('/^Set-Cookie:\s*([^;]*)/mi', $headerLine, $cookie) == 1)
        $cookies[] = $cookie[1];
    return strlen($headerLine); // Needed by curl
};
curl_setopt($ch, CURLOPT_HEADERFUNCTION, $curlResponseHeaderCallback);
$result = curl_exec($ch);

curl_close($ch);
//print_r($cookies);

$ch = curl_init($url);
//curl_setopt($ch, CURLOPT_VERBOSE, true);
curl_setopt($ch, CURLOPT_COOKIE, implode('; ',$cookies));
curl_setopt($ch, CURLOPT_USERAGENT,$agent);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$result = curl_exec($ch);

$data=json_decode($result,true);
/*
$zone=var_export($zone,true);
echo $zone;
file_put_contents("tmp",$zone);


include('tmp.php');
*/
//print_r($data);

require_once __DIR__ . '/vendor/autoload.php';

use Badcow\DNS\Zone;
use Badcow\DNS\Rdata\Factory;
use Badcow\DNS\ResourceRecord;
use Badcow\DNS\AlignedBuilder;

$dom=$dom.'.';
$zone = new Zone($dom);
$zone->setDefaultTtl(3600);

$soa = new ResourceRecord;
$soa->setName('@');
$soa->setRdata(Factory::Soa(
    $dom,
    'post.'.$dom,
    '2014110501',
    3600,
    14400,
    604800,
    3600
));
$RecordTypes=[
    1 => 'A', //IsDynDns=true
    2 => 'CNAME',
    5 => 'TXT',
    8 => 'AAAA',
    9 => 'NS',
    11 => 'SRV',
];
$dom='.'.$dom;
foreach($data['Result']['CustomHostRecords']['Records'] as $r)
{
    switch ($r['RecordType'])
    {
        case 1:
            $a= new ResourceRecord;
            if($r['Host']=='@') $a->setName($r['Host']);
            else
                $a->setName($r['Host'].$dom);
            $a->setRdata(Factory::A($r['Data']));
            $a->setTtl($r['Ttl']);
            $zone->addResourceRecord($a);
            break;
        case 2:
            $a= new ResourceRecord;
            $a->setName($r['Host'].$dom);
            $a->setRdata(Factory::Cname($r['Data']));
            $a->setTtl($r['Ttl']);
            $zone->addResourceRecord($a);
            break;
        case 5:
            $a= new ResourceRecord;
            $a->setName($r['Host']);
            $a->setRdata(Factory::txt($r['Data']));
            $a->setTtl($r['Ttl']);
            $zone->addResourceRecord($a);
            break;
        case 8:
            $a= new ResourceRecord;
            $a->setName($r['Host'].$dom);
            $a->setRdata(Factory::Aaaa($r['Data']));
            $a->setTtl($r['Ttl']);
            $zone->addResourceRecord($a);
            break;
        case 9:
            $a= new ResourceRecord;
            $a->setName($r['Host'].$dom);
            $a->setRdata(Factory::Ns($r['Data']));
            $a->setTtl($r['Ttl']);
            $zone->addResourceRecord($a);        
            break;
        default:
            /*echo "Not supported";
            print_r($r);
            break;*/
    }
}
$zoneBuilder = new AlignedBuilder();

echo $zoneBuilder->build($zone);