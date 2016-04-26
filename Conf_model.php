<?php
// function : read conf 
// auther : wei
// start : 20160424
require './application/libraries/vendor/autoload.php';
use Elasticsearch\ClientBuilder;
class Conf_model extends CI_Model{
        private $maillog_conf = "conf/maillog.json";
        private $maillog_detail_access_account = array();
        private $hosts = array();
        private $query_limit = 0;


        public function __construct(){
                parent::__construct();
        }
        //function : conf_exist
        //description : 檢查檔案
        //return : boolean
        //auther : wei
        //update : 20160424
        public function conf_exist(){
                return file_exists($this->maillog_conf);
        }
        //function : mod_logs_test
        //description : 檢查檔案
        //return : boolean
        //auther : wei
        //update : 20160424 
        public function mod_logs_test(){ 
                $check = (fileperms("./logs/")==16895)?true:false;
                return $check;
        }
        
        //function : conf_AccessAccount_exist
        //description : 檢查特權清單
        //return : boolean
        //auther : wei
        //update : 20160424
		public function conf_AccessAccount_exist(){
                $check = false;
                try{
                        $data = file_get_contents($this->maillog_conf);
                        $data = json_decode($data,true);
                        foreach($data["maillog_detail"] as $item){
                                        array_push($this->maillog_detail_access_account,$item);
                                }
                        $check =  ($this->maillog_detail_access_account!=null)?true:false;
                } catch (Exception $e) {
                        $check = false;
                }
                return $check;
        }
        //function : conf_QueryLimit_exist
        //description : 檢查最高閥值設定檔
        //return : boolean
        //auther : wei
        //update : 20160424
        public function conf_QueryLimit_exist(){
                $check = false;
                try{
                        $data = file_get_contents($this->maillog_conf);
                        $data = json_decode($data,true);
                        $this->query_limit = $data["query_limit"];
                        $check =  ($this->query_limit!=0)?true:false;
                } catch (Exception $e) {
                        $check = false;
                }
                return $check;
        }
        //function : conf_ELKConf_exist
        //description : 檢查ELK server設定檔在不在
        //return : boolean
        //auther : wei
        //update : 20160424
        public function conf_ELKConf_exist(){
        public function conf_ELKConf_exist(){
                $check = false;
                try{
                        $data = file_get_contents($this->maillog_conf);
                        $data = json_decode($data,true);
                        $testArray = $data["elasticsearch"];
                        foreach($data["elasticsearch"] as $item){
                                        array_push($this->hosts,$item);
                                }
                        $check =  ($this->hosts!=null)?true:false;
                } catch (Exception $e) {
                        $check = false;
                }
                return $check;
        }
        //function : conf_ELK_exist
        //description : 檢查ELK服務狀態
        //return : boolean
        //auther : wei
        //update : 20160424
        public function conf_ELK_exist(){
                $check = false;
                try{
                        set_time_limit(0);// to infinity for example
                        date_default_timezone_set("Asia/Taipei");
                        //必須做的前置測試
                        if(!$this->conf_ELKConf_exist()) return $check;

                        $ch = curl_init();
                        foreach($this->hosts as $item){
                                $ch = curl_init();
                                curl_setopt($ch, CURLOPT_URL, "http://".$item."/");
                                curl_setopt($ch, CURLOPT_TIMEOUT, 400); //timeout in seconds
                                curl_setopt($ch, CURLOPT_HEADER, false);
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
                                $context = json_decode(curl_exec($ch));
                                if($context==null || $context->status != 200) return $check;
                                curl_close($ch);
                        }
                        $check=true;

                } catch (Exception $e) {
                        $check = false;
                }
                return $check;
        }
        //function : getDetailAccount
        //description : 取得具有閱覽詳細權限的帳號
        //return : array
        //auther : wei
        //update : 20160424
        public function getDetailAccount(){
                $this->conf_AccessAccount_exist();
                return $this->maillog_detail_access_account;
        }

        //function : getQueryLimit
        //description : 取得查詢最大上線值
        //return : int
        //auther : wei
        //update : 20160424
        public function getQueryLimit(){
                $this->conf_QueryLimit_exist();
                return $this->query_limit;
        }

        //function : getHosts()
        //description : 取得所有需要連線的ELK對象
        //return : array
        //auther : wei
        //update : 20160424
        public function getHosts(){
                $this->conf_ELKConf_exist();
                return $this->hosts;
        }
        //function : maillogSearch()
        //description : 向elk 提出 search要求
        //return : object
        //auther : wei
        //update : 20160424
        public function maillogSearch($querydate,$querylocal,$querydomain){
                try{
                        date_default_timezone_set("Asia/Taipei");
                        $this->conf_AccessAccount_exist();
                        $this->conf_ELKConf_exist();
                        $this->conf_QueryLimit_exist();
                        $client = ClientBuilder::create()->setHosts($this->hosts)->build();
                        //須查詢的index
                        $indexArray = array($this->todayIndex($querydate),$this->yesdayIndex($querydate));
                        //開始祖json
                        $params = [];
                        $params['index'] = $indexArray;
                        $params['type'] = 'maillog';
                        $params['sort'] = '@timestamp:asc';
                        if($querydomain != ""){
                                $params['body']['query']['query_string']['query']='+local:"'.$querylocal.'" +remote:"'.$querydomain.'"';
                        }
                        else{
                                $params['body']['query']['query_string']['query']='+local:"'.$querylocal.'"';
                        }
                        //最高閥值
                        $params['body']['size']=$this->query_limit;
                        $query = $client->search($params);
                        return $query;
                }
                catch(exception $e){
                        return "fail";
                }
        }
  		//function : todayIndex()
        //description : 取得今天的index名稱
        //return : string
        //auther : wei
        //update : 20160424
        public function todayIndex($unixtime){
                $date = new DateTime();
                $date->setTimestamp($unixtime);
                $Ymd = 'maillog-'.$date->format("Y.m.d");
                return $Ymd;
        }
        //function : yesdayIndex()
        //description : 取得前一天的index名稱
        //return : string
        //auther : wei
        //update : 20160424
        public function yesdayIndex($unixtime){
                $date = new DateTime();
                $date->setTimestamp($unixtime);
                $date->modify("-1 day");
                $Ymd = 'maillog-'.$date->format("Y.m.d");
                return $Ymd;
        }

}



//end of file Conf_model.php
