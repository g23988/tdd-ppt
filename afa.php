<?php
date_default_timezone_set("Asia/Taipei");
//require '../vendor/autoload.php';
require './application/libraries/vendor/autoload.php';
use Elasticsearch\ClientBuilder;
$hosts = ['10.10.10.10:9222'];
$client = ClientBuilder::create()->setHosts($hosts)->build();
$unixtime = (int)$querydate;
//今天
$date = new DateTime();
$date->setTimestamp($unixtime);
//前一天
$beforeDate = new DateTime();
$beforeDate->setTimestamp($unixtime);
$beforeDate->modify("-1 day");
//後一天
$afterDate = new DateTime();
$afterDate->setTimestamp($unixtime);
$afterDate->modify("+1 day");

//須查詢的index
$datearray = array('maillog-'.$date->format("Y.m.d"),'maillog-'.$beforeDate->format("Y.m.d"));

//開始祖json
$params = [];
$params['index'] = $datearray;
$params['type'] = 'maillog';
$params['sort'] = '@timestamp:asc';

// wei.liu@104.com.tw  local=wei.liu remote=104.com.tw
//$local = $_GET['querylocal'];
//$remote = $_GET['querydomain'];
$local = $querylocal;
$remote = isset($querydomain)? $querydomain:"";
$indate = (int)$querydate;
//domain(remote)選填
if($indate!=""){
        $params['body']['query']['query_string']['query']='+local:"'.$local.'" +remote:"'.$remote.'"';
}
else{
        $params['body']['query']['query_string']['query']='+local:"'.$local.'"';
}

//最高閥值
$params['body']['size']=500;

//權限設定檔
$maillog_conf="conf/maillog.json";
$maillog_detail_access_account = array();
try {
        if(file_exists($maillog_conf)){
                $data = file_get_contents($maillog_conf);
                $data = json_decode($data,true);
                foreach($data["maillog_detail"] as $item){
                        array_push($maillog_detail_access_account,$item);
                }
        }
} catch (Exception $e) {
        echo "全限設定檔讀取錯誤。<br>";
}



//執行開始
try{

$query = $client->search($params);
if($query['hits']['total'] >= $params['body']['size']){
        echo '<div class="alert alert-danger">';
        echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
        echo "資料筆數 ".$query['hits']['total']." 大於上限閥值，只顯示最接近的 ".$params['body']['size']." 筆。";
        echo '</div>';
}
if($query['hits']['total']>=1){
        //var_dump($query['hits']['hits']);
        //保存確認過為本日的
       $showarray = array();
        foreach($query['hits']['hits'] as $item){
                $logTimestamp = strtotime($item['_source']['timestamp']);
                $item['_source']['sorttime'] = $logTimestamp;
                $startDay = (int)$date->format("U");
                $endDay = (int)$afterDate->format("U");
                if($logTimestamp>=$startDay && $logTimestamp<$endDay){
                        array_push($showarray,$item["_source"]);
                }
        }
        if(count($showarray)!=0){
                usort($showarray, "cmp");
                //if($username==="wei.liu" || $username==="hill.huang" || $username==="peter.tsai" || $username==="hank.hung" || $username==="kouni.huang" || $username==="richard.luo"){
                if(in_array($username, $maillog_detail_access_account)){
                        echo $username." 特權轉為明細模式<br>";
                        echo '<table class="table table-bordered">';
                        echo '<tbody>';
                        echo '<tr><th>時間</th><th>伺服器</th><th>收件者信箱</th><th>結果</th><th>qid</th><th>判斷</th></tr>';
                        foreach($showarray as $item){
                                        if($remote!="" && $item['remote']!=$remote) continue;
                                        echo "<tr>";
                                        echo "<td>".$item['timestamp']."</td>";
                                        echo "<td>".$item['hostname']."</td>";
                                        echo "<td>".$item['local']."@".$item['remote']."</td>";
                                        echo "<td>".$item['result']."</td>";
                                        echo "<td>".$item['qid']."</td>";
                                        echo "<td>".checkdsn($item['dsn'])."</td>";
                                        echo "</tr>";
                                        echo '<tr><td colspan="6" style="background-color:#d0d0d0;">'.htmlentities($item['message'])."</td></tr>";
                        }
                        echo '</tbody>';
                        echo "</table>";

                }
                else{
                        echo '<table class="table table-bordered">';
                        echo '<tbody>';
                        echo '<tr><th>時間</th><th>伺服器</th><th>收件者信箱</th><th>結果</th><th>判斷</th></tr>';
                        foreach($showarray as $item){
                                        //略過不找的domain
                                        if($remote!="" && $item['remote']!=$remote) continue;
                                        echo "<tr>";
                                        echo "<td>".$item['timestamp']."</td>";
                                        echo "<td>".$item['hostname']."</td>";
                                        echo "<td>".$item['local']."@".$item['remote']."</td>";
                                        echo "<td>".$item['result']."</td>";
                                        echo "<td>".checkdsn($item['dsn'])."</td>";
                                        echo "</tr>";
                        }
                        echo '</tbody>';
                        echo "</table>";
                }
        }
        else{echo "沒有符合的";}


}
else{
        echo "沒有符合的";
}

} catch(Exception $e){
        echo "資料庫不存在，請聯絡 SE";
}
function checkdsn($dsn){
        $result = "";
        $str = explode(".",$dsn);
        switch ($str[0]){
                case "2":
                        $result = "成功。";
                        break;
                case "4":
                        $result = "暫時錯誤，郵件服務器將稍後再試。";
                        break;
                case "5":
                        $result = "郵件傳送失敗。";
                        break;
        }
        switch ($str[1].".".$str[2]){
                case "1.1":
                        $result .= "收件地址信箱錯誤";
                        break;
                case "1.2":
                        $result .= "收件地址系統錯誤";
                        break;
                case "1.3":
                        $result .= "收件地址信箱句法錯誤";
                        break;
                case "1.4":
                        $result .= "收件地址信箱不清";
                        break;
                case "1.5":
                        $result .= "收件地址信箱無效";
                        break;
                case "1.6":
                        $result .= "信箱已移除";
                        break;
                case "1.7":
                        $result .= "寄件者信箱句法錯誤";
                        break;
                case "1.8":
                        $result .= "寄件者系統錯誤";
                        break;
                case "2.1":
                        $result .= "信箱無法運作，無法接收信件";
                        break;
                case "2.2":
                        $result .= "信箱已滿";
                        break;
                case "2.3":
                        $result .= "郵件長度超出管理者限制";
                        break;
                case "2.4":
                        $result .= "此信箱屬於特定郵件群組，但群組無法開展";
                        break;
                case "3.1":
                        $result .= "郵件系統儲存空間已滿";
                        break;
                case "3.2":
                        $result .= "主機無法接收信件，可能暫時關機、維修等";
                        break;
                case "3.3":
                        $result .= "指定的郵件特徵無法為收件主機所接收";
                        break;
                case "3.4":
                        $result .= "郵件大小超出郵件系統限制";
                        break;
                case "3.5":
                        $result .= "系統設定錯誤，無法接收郵件";
                        break;
                case "4.1":
                        $result .= "主機無響應";
                        break;
                case "4.2":
                        $result .= "連線錯誤";
                        break;
                case "4.3":
                        $result .= "無法連線到DNS伺服器";
                        break;
                case "4.4":
                        $result .= "路由解析發生錯誤，無法連線到指定的主機";
                case "4.5":
                        $result .= "網路壅塞";
                        break;
                case "4.6":
                        $result .= "路由解析發生繞行，無法連線到指定的主機";
                        break;
                case "4.7":
                        $result .= "郵件滯留過久";
                        break;
                case "5.1":
                        $result .= "命令無效";
                        break;
                case "5.2":
                        $result .= "郵件傳送協議的語法錯誤";
                        break;
                case "5.3":
                        $result .= "太多收件者";
                        break;
                case "5.4":
                        $result .= "命令敘述錯誤";
                        break;
                case "5.5":
                        $result .= "錯誤的通訊協議版本";
                        break;
                case "6.1":
                        $result .= "傳送協議或轉送郵件的系統不支持該媒介";
                        break;
                case "6.2":
                        $result .= "郵件內容在傳送之前必需經過轉換動作，但該動作不被允許";
                        break;
                case "6.3":
                        $result .= "郵件內容在轉送之前須經過轉換動作，但無法執行";
                        break;
                case "6.4":
                        $result .= "郵件傳送成功，但部份內容因無法轉換而遺失";
                        break;
                case "6.5":
                        $result .= "轉換動作失敗";
                        break;
                case "7.1":
                        $result .= "寄件者無授權傳送信件";
                        break;
                case "7.2":
                        $result .= "寄件者無授權寄信予該郵件群組";
                        break;
                case "7.3":
                        $result .= "違反郵件安全協定";
                        break;
                case "7.4":
                        $result .= "郵件含有安全特徵如認證措施，但不為傳送協議所接受";
                        break;
                case "7.5":
                        $result .= "經授權得以認證或解密該郵件的系統無法完成該動作，因所需信息不完全";
                        break;
                case "7.6":
                        $result .= "經授權得以認證或解密該郵件的系統無法完成該動作，因算法不支持";
                        break;
                case "7.7":
                        $result .= "經授權得以認證該郵件的系統無法完成該動作，因郵件已損毀";
                        break;
                default:
                        break;

        }
        return $result." (".$dsn.")";
}
function cmp($a, $b)
{
        if ($a['sorttime'] == $b['sorttime']) return 0;
        return ($a['sorttime'] < $b['sorttime']) ? -1 : 1;
}