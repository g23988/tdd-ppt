
<?php
/*
if ($count >= $query_limit){
        echo '<div class="alert alert-danger">';
        echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
        echo "資料筆數 ".$count."， 大於上限閥值，只顯示最符合的 ".$query_limit." 筆";
        echo '</div>';
}*/
if ($count >= 1){
        echo $username." 特權轉為明細模式，本次搜尋時間： ".$took." ms<br/>";
        echo '<table class="table table-bordered">';
        echo '<tbody>';
        echo '<tr><th>時間</th><th>伺服器</th><th>收件者信箱</th><th>結果</th><th>qid</th><th>判斷</th></tr>';
        foreach($showarray as $item){
                if($query_context['remote'] != "" && $item['remote'] != $query_context['remote']) continue;
                if($query_context['local'] != "" && $item['local'] != $query_context['local'] ) continue;
                echo "<tr>";
                echo "<td>".$item['timestamp']."</td>";
                echo "<td>".$item['hostname']."</td>";
                echo "<td>".$item['local']."@".$item['remote']."</td>";
                echo "<td>".$item['result']."</td>";
                echo "<td>".$item['qid']."</td>";
                echo "<td>".$item['dsn']."</td>";
                echo "</tr>";
                echo '<tr><td colspan="6" style="background-color:#d0d0d0;">'.htmlentities($item['message'])."</td></tr>";
         }
        echo '</tbody>';
        echo "</table>";
}
else{
        echo "沒有符合的。";
}



// end of file views/search/query_detail.php