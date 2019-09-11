<?php
    namespace BillZ\Sockets\TCP;

    require_once('./TCPExceptions.php');

    class TCPClient extends TCPSocket {
        
        public function __construct($tcpClient) {
            $this->socket = $tcpClient;
        }
        
        public function read(int $length = 2048, int $type = PHP_BINARY_READ) {
            if (!$this->isOpen()) {
                throw new TCPClientError('');
            }

            $buffer = @socket_read($this->socket, $length, $type);
            
            return trim($buffer);
        }
        
        public function write(string $message) {
            if (!$this->isOpen()) {
                throw new TCPClientError('');
            }

            @socket_write($this->socket, $message, strlen($message));
            return $this;
        }
    }
    
?>