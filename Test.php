
<?php
//function : to test all unit test
//auther : wei
//lastupdate : 20160424
class Test extends MY_Controller{
        public function __construct(){
                parent::__construct();
                $this->load->library('unit_test');
                $this->load->model('conf_model');
        }
        public function index(){
                $this->unit->run($this->conf_model->mod_logs_test(), true, 'logs 寫入權限','系統');
                $this->unit->run($this->conf_model->conf_exist(), true, '設定檔位置','系統');
                $this->unit->run($this->conf_model->conf_AccessAccount_exist(), true, '讀取明細閱覽特權使用者設定檔','系統');
                $this->unit->run($this->conf_model->conf_QueryLimit_exist(), true, '讀取最高閥值設定檔','系統');
                $this->unit->run($this->conf_model->conf_ELKConf_exist(), true, '讀取 ELK 設定檔','系統');
                $this->unit->run($this->conf_model->conf_ELK_exist(), true, '測試 ELK server 連線','系統');
                $this->unit->run($this->conf_model->getDetailAccount(), 'is_array', '測試 取得具有特殊權限的使用者','邏輯');
                $this->unit->run($this->conf_model->getHosts(), 'is_array', '測試 取得所有需要連線的對象','邏輯');
                $this->unit->run($this->conf_model->getQueryLimit(), 'is_int', '測試 取得最高查詢閥值','邏輯');
                $this->unit->run($this->conf_model->todayIndex(1461427200), 'maillog-2016.04.24', '測試 取得當天的 ELK index名稱','邏輯');
                $this->unit->run($this->conf_model->yesdayIndex(1461427200), 'maillog-2016.04.23', '測試 取得前一天的 ELK index名稱','邏輯');
                $this->unit->run($this->conf_model->maillogSearch('1461427200','g23988','gmail.com'), 'is_array', '測試 主要的query search json','邏輯');

                echo $this->unit->report();
                log_message('error','觸發單元測試');
        }


}
//end of file Test.php