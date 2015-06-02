<?php
require_once "./src/cdnzzv3.sdk.class.php";
require_once "./src/config.inc.php";

$user_email = "apitest@cdnzz.com";
$user_secretkey = "3388b365b1eab03dfd68c578c8fee5fb";

function RandomString($len=6)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
    $randstring = '';
    for ($i = 0; $i < $len; $i++) {
        $randstring .= $characters[rand(0, strlen($characters))];
    }
    return $randstring;
}

// 不启用自动认证
$api = new CDNZZAPI($user_email, $user_secretkey, FALSE);
$result = $api->fetchToken();
echo "Token: {$result['token']}\n";
$result = $api->fetchToken(100, "named-token");
echo "Token: {$result['token']}\nname: {$result['payload']['name']}\n";
assert($result['payload']['name'] == 'named-token');

// 测试 token 过期
$api->fetchToken(-1);
try{
	$api->listDomain();
	assert(FALSE);
}catch(CDNZZException $e){
	assert($e->getCDNZZCode() == INVALID_TOKEN_ERROR);
}


// 启用自动认证
$zzapi = new CDNZZAPI($user_email, $user_secretkey);

// 域名操作
$domain = RandomString()."-cdnzzv3-sdk-test.com";
$result = $zzapi->addDomain($domain);
print_r($result);
assert($result["domain"] == $domain);

$domains = $zzapi->postRequest("ListDomain");
print_r($domains);
assert($domains[0]);

// 域名验证
$result = $zzapi->fetchVerifyInfo($domain);
print_r($result);
assert($result["domain"] == $domain);
assert(!empty($result["dns_txt_record"]));

$result = $zzapi->verifyDomain($domain, "dns");
print_r($result);
assert($result["domain"] == $domain);
$result = $zzapi->verifyDomain($domain, "file");
print_r($result);
assert($result["domain"] == $domain);

// 子域名操作
$domain = "api-test.com";
$sub_host = RandomString();
$result = $zzapi->addSubDomain($domain, $sub_host, "CNAME", "{$sub_host}.{$domain}");
print_r($result);
assert($result["host"] == $sub_host);
$sub_id = $result["id"];

$result = $zzapi->delSubDomain($domain, $sub_id);
assert($result["host"] == $sub_host);
assert($result["id"] == $sub_id);

$result = $zzapi->addSubDomain($domain, $sub_host, "A", "1.1.1.1");
assert($result["host"] == $sub_host);
assert($result["value"] == "1.1.1.1");

$result = $zzapi->listSubDomain($domain);
print_r($result);
assert(count($result) > 0);
$sub_host = $result[0]["host"];
$sub_id = $result[0]["id"];

$result = $zzapi->modifySubDomain($domain, $sub_id, $sub_host, "A", "9.9.9.9");
print_r($result);
assert($result["record_type"] == "A");
assert($result["value"] == "9.9.9.9");
assert($result["id"] == $sub_id);

$result = $zzapi->activeSubDomain($domain, $sub_id);
print_r($result);
assert($result["id"] == $sub_id);
assert($result["active"]);

$result = $zzapi->inactiveSubDomain($domain, $sub_id);
print_r($result);
assert($result["id"] == $sub_id);
assert(!$result["active"]);

// 内容操作
$url = "http://api-test.com/logo.png";
$result = $zzapi->addPreload($domain, $url);
print_r($result);
assert($result["url"] == $url);

$result = $zzapi->purgeCache($domain, $url);
print_r($result);
assert($result["url"] == $url);
