<?php
/*
 *
 *  CDNZZ PHP SDK
 *
 * @version 3.0.1
 * @Copyright (C) 2015 GridSafe, Inc.
 */

if(!defined('CDNZZ_API_PATH'))
    define('CDNZZ_API_PATH', dirname(__FILE__));

require_once CDNZZ_API_PATH.DIRECTORY_SEPARATOR.'config.inc.php';


class CDNZZException extends Exception {
    public function getCDNZZCode(){
        return $this->code;
    }

    public function getCDNZZMessage(){
        return $this->message;
    }

    public function __toString(){
        return "\"{$this->code}: {$this->message}\"";
    }
}


class CDNZZAPI
{
    public $user;   // 用户邮箱
    public $secretkey;  // 用户 secretkey, 在官网 “个人信息” 页获取
    public $auto_auth;  // 开启自动认证? 若开启则会自动获取 token 并在 token 过期时重新获取
    private $token = null;
    private $token_expires = 0;

    function __construct($user, $secretkey, $auto_auth=TRUE){
        $this->user = $user;
        $this->secretkey = $secretkey;
        $this->auto_auth = $auto_auth;
    }

    private function checkToken(){
        return ($this->token && (0 < $this->token_expires && $this->token_expires < time()));
    }


    private function doPostRequest($method, array $params=null){
        if(is_null($params))
            $params = array();

        $params["method"] = $method;

        if(!array_key_exists("user", $params)) {
            $params["user"] = $this->user;
        }
        if(!array_key_exists("secretkey", $params)){
            if($this->auto_auth && !$this->checkToken()){
                $this->fetchToken();
            }
            $params["token"] = $this->token;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, API_URL);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        $res = curl_exec($ch);
        if(curl_errno($ch) != 0){
            throw new CDNZZException(curl_error($ch), curl_errno($ch));
        }else{
            $res = json_decode($res, 1);
            if($res["error"] != 0){
                throw new CDNZZException($res["msg"], $res["error"]);
            }
        }
        curl_close($ch);
        return $res["result"];
    }

    public function postRequest($method, array $params=null){
        $rv = null;
        try{
            $rv = $this->doPostRequest($method, $params);    
        }catch(CDNZZException $e){
            if($this->auto_auth && $e->getCDNZZCode() == INVALID_TOKEN_ERROR){
                $this->fetchToken();
                $rv = $this->doPostRequest($method, $params);
            }else{
                throw $e;
            }
        }

        return $rv;
    }

    public function fetchToken($expires=null, $name=null){
        $params = array('secretkey' => $this->secretkey);
        if(!is_null($expires)){
            $params['exp'] = $expires + time();
        }
        if(!is_null($name)){
            $params['name'] = $name;
        }
        $result = $this->postRequest("FetchToken", $params);
        $this->token = $result["token"];
        $this->token_expires = $result["payload"]["exp"];
        return $result;
    }

    public function addDomain($domain){
        return $this->postRequest("AddDomain", array('domain' => $domain));
    }

    public function listDomain(){
        return $this->postRequest("ListDomain");
    }

    public function fetchVerifyInfo($domain){
        return $this->postRequest("FetchVerifyInfo", array('domain' => $domain));
    }

    public function verifyDomain($domain){
        return $this->postRequest("VerifyDomain", array('domain' => $domain));
    }

    public function addSubDomain($domain, $host, $type, $value){
        return $this->postRequest("AddSubDomain", 
            array('domain' => $domain, 'host' => $host, 'type' => $type, 'value' => $value));
    }

    public function delSubDomain($domain, $sub_id){
        return $this->postRequest("DelSubDomain", array('domain' => $domain, 'sub_id' => $sub_id));
    }

    public function listSubDomain($domain){
        return $this->postRequest("ListSubDomain", array('domain' => $domain));
    }

    public function modifySubDomain($domain, $sub_id, $host, $type, $value){
        return $this->postRequest("ModifySubDomain",
            array('domain' => $domain, 'sub_id' => $sub_id, 
                'host' => $host, 'type' => $type, 'value' => $value));       
    }

    public function activeSubDomain($domain, $sub_id){
        return $this->postRequest("ActiveSubDomain", array('domain' => $domain, 'sub_id' => $sub_id));
    }

    public function inactiveSubDomain($domain, $sub_id){
        return $this->postRequest("InactiveSubDomain", array('domain' => $domain, 'sub_id' => $sub_id));
    }

    public function addPreload($domain, $url){
        return $this->postRequest("AddPreload", array('domain' => $domain, 'url' => $url));
    }

    public function purgeCache($domain, $url){
        return $this->postRequest("PurgeCache", array('domain' => $domain, 'url' => $url));
    }
}
