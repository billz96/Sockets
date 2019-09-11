<?php
    namespace BillZ\Sockets\TCP;

    abstract class TCPSocket {

        protected $socket;
        
        public function open() { }
        
        public function close() {
            @socket_shutdown($this->socket);
            @socket_close($this->socket);
            $this->socket = null;
        }
        
        public function isOpen() : bool {
            return $this->socket != null && is_resource($this->socket);
        }
    }
?>