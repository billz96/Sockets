<?php
    namespace BillZ96\Net;
    
    class WsClient
    {
        public $id;
        public $socket;
        public $last;
        public $pinging;
        public function __construct($id, $socket)
        {
            $this->id = $id;
            $this->socket = $socket;
            $this->last = time();
            $this->pinging = false;
        }
    }
?>