<?php
use Phalcon\Logger\Adapter\File as Logger;
class MyDbListener
{
    protected $_logger;
    public $logPath = APP_PATH."/app/logs/db.log";
    public function __construct()
    {
        $this->_logger = new Logger($this->logPath);
    }

    public function afterConnect()
    {

    }

    public function beforeQuery()
    {

    }

    public function afterQuery($event, $conn)
    {
        if(QA) {
            $db = $conn->getDescriptor()['dbname'];
            $sql  = $conn->getRealSQLStatement();
            $vars = $conn->getSqlVariables();
            if(!empty($vars)) {
                foreach ($vars as $k => $v) {
                    $sql = str_replace(":" . $k, $v, $sql);
                }
            }
            $this->_logger->getFormatter()->setDateFormat('Y-m-d H:i:s');
            $this->_logger->info("[$db]".$sql);
        }
    }

}