<?php
    namespace BillZ\Sockets\TCP;

    require_once('./TCPSocket.php');
    require_once('./TCPClient.php');
    
    use BillZ\Sockets\TCP\{TCPSocket, TCPClient};
    use BillZ\Sockets\Errors\{TCPServerError, TCPClientError};

    class TCPServer extends TCPSocket {
        
        private $address;
        private $port;
        private $isListening = false;
        private $childProcessIds = [];
        public static $MAX_CLIENT_COUNT = 5;
        public static $BACKLOG = 5;
        public static $DELAY = 100;
        
        public function __construct() {
            $this->open();
            
            // Adds handlers on signals
            $this->addSignalHandler(SIGHUP, [$this, "stopListening"]);
            $this->addSignalHandler(SIGCHLD, [$this, "handleChildProcesses"]);
        }
        
        public function open() {
            $this->socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            if (!$this->socket) {
                throw new TCPServerError('Unable to create TCP socket server');
            }

            @socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
            @socket_set_nonblock($this->socket);
        }
        
        public function bind(string $address, int $port) {
            if (!$this->isOpen()) {
                throw new TCPServerError('TCP socket server is not created');
            }
            
            $this->address = $address;
            $this->port = $port;

            $result = @socket_bind(
                $this->socket, 
                $this->address, 
                $this->port
            );

            if (!$result) {
                throw new TCPServerError(
                    sprintf("TCP socket server: can not bind '%s':'%s'", $this->address, $this->port)
                );
            }

            return $this;
        }
        
        public function listen() {
            if (!$this->isOpen()) {
                throw new TCPServerError('TCP socket server is not created');
            }

            $result = @socket_listen($this->socket, self::$BACKLOG);
            if (!$result) {
                throw new TCPServerError('TCP socket server: can not start listening'); 
            }

            $this->isListening = true;
            while ($this->isListening) {
                $tcpClientResource = @socket_accept($this->socket);
                if (is_resource($tcpClientResource) && !$this->isTheClientsLimitReached()) {
                    $tcpClient = new TCPClient($tcpClientResource);
                    $this->handleTCPClient($tcpClient);
                }
                
                $this->dispatchSignal();
                usleep(self::$DELAY);
            }

            $this->releaseAllChildProcs();
        }
        
        public function stopListening() {
            $this->isListening = false;
            return $this;
        }
        
        public function isTheClientsLimitReached(): bool {
            return count($this->childProcessIds) >= self::$MAX_CLIENT_COUNT;
        }
        
        public function addSignalHandler(int $SIGNAL, Callable $signalHandler) {
            pcntl_signal($SIGNAL, $signalHandler);
        }
        
        private function dispatchSignal() {
            pcntl_signal_dispatch();
        }
        
        private function handleTCPClient(TCPClient $clientTCPSocket) {
            $childProcessId = pcntl_fork();
            if ($childProcessId > 0) {
                $this->childProcessIds[] = $childProcessId;
                return;
            }

            try {
                $clientTCPSocket->write("\nWelcome to the PHP Test Server. \n");
                while (true) {
                    $buffer = $clientTCPSocket->read();
                    
                    if ($buffer == 'quit' || $buffer === false) {
                        $clientTCPSocket->close();
                        break;
                    }
                    if ($buffer !== '') {
                        $talkback = sprintf("PHP: You said %s\n", $buffer);
                        $clientTCPSocket->write($talkback);
                        print sprintf(": %s\n", $buffer);
                    }
                }
            } catch (\Exception $exception) {
                $clientTCPSocket->close();
                exit(1);
            }               
            
            exit(0);
        }
        
        private function releaseAllChildProcs() {
            while ($this->hasChildProcs()) {
                $this->handleChildProcs();
                usleep(self::$DELAY);
            }
        }
        
        public function handleChildProcs() {
            foreach ($this->childProcessIds as $key => $childProcessId) {
                $result = pcntl_waitpid($childProcessId, $status, WNOHANG);
                if ($result == -1 || $result > 0) {
                    unset($this->childProcessIds[$key]);
                }
            }
        }
        
        public function hasChildProcs(): bool {
            return !empty($this->childProcessIds);
        }

    }
    
?>