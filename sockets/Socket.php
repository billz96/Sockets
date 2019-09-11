<?php
    namespace BillZ96\Net;

    /** Simple Socket Class */
    class Socket {

        private $port;
        private $address;
        private $blocking;

        private $buffer;
        private $backlog;

        private $type;
        private $family;
        private $protocol;

        private $resource;

        /**
         * Socket constructor
         * @param  $resource
         */
        public function  __construct($input = null) {
            if (!is_resource($input)) {
                $this->port     = $input['port'] ? $input['port'] : 3000;
                $this->address  = $input['address'] ? $input['address'] : "localhost";
                $this->blocking = $input['blocking'] ? $input['blocking'] : true;
                $this->buffer   = $input['buffer'] ? $input['buffer'] : 2048;
                $this->backlog  = $input['backlog'] ? $input['backlog'] : 5;
                $this->type     = $input['type'] ? $input['type'] : SOCK_STREAM;
                $this->family   = $input['family'] ? $input['family'] : AF_INET;
                $this->protocol = $input['protocol'] ? $input['protocol'] : SOL_TCP;
                $this->resource = socket_create($this->family, $this->type, $this->protocol);
            } else {
                $this->resource = $input;
            }
        }

        /**
         * Gets socket options for the socket
         * @param type $option
         * @return type
         */
        public function getOption($option) {
            return socket_get_option($this->resource, SOL_SOCKET, $option);
        }

        /**
         * Sets socket options for the socket
         * @param type $option
         * @param type $value
         * @return type
         */
        public function setOption($option, $value) {
            return socket_set_option($this->resource, SOL_SOCKET, $option, $value);
        }

        /**
         * Returns the socket port
         * @return int
         */
        public function getPort() {
            return $this->port;
        }

        /**
         * Define the socket port
         * @param int $port
         */
        public function setPort($port) {
            $this->port = $port;
        }

        /**
         * Returns the socket address
         * @return string
         */
        public function getAddress() {
            return $this->address;
        }

        /** Define the socket address */
        public function setAddress($address) {
            $this->address = $address;
        }

        /**
         * Returns if the socket is blocking mode
         * @return boolean
         */
        public function isBlocking() {
            return $this->blocking;
        }

        /**
         * Defines whether the socket is blocking or nonblocking mode
         * @param boolean $block
         */
        public function setBlocking($blocking) {
            $this->blocking = ($blocking == true);
            $this->updateBlockingMode();
        }

        /**
         * Returns the socket communication type
         * @return int
         */
        public function getType() {
            return $this->type;
        }

        /**
         * Define the socket communication type
         * @param int $type
         */
        public function setType($type) {
            if ($type == SOCK_STREAM || $type == SOCK_DGRAM || $type == SOCK_SEQPACKET || $type == SOCK_RAW || $type == SOCK_RDM) {
                $this->type = $type;
            } else {
                throw new SocketException("Invalid socket communication type");
            }
        }

        /**
         * Returns the socket protocol family
         * @return int
         */
        public function getFamily() {
            return $this->family;
        }

        /**
         * Define the socket protocol family
         * @param int $family
         */
        public function setFamily($family) {
            if ($family == AF_INET || $family == AF_INET6 || $family == AF_UNIX) {
                $this->family = $family;
            } else {
                throw new SocketException("Invalid socket protocol family");
            }
        }

        /**
         * Returns the socket protocol
         * @return int
         */
        public function getProtocol() {
            return $this->protocol;
        }

        /**
         * Define the socket protocol, must be compatible whit the protocol family
         * @param int $protocol
         */
        public function setProtocol($protocol) {
            if ($protocol == SOL_TCP || $protocol == SOL_UDP || $protocol == SOL_SOCKET) {
                $this->protocol = $protocol;
            } else {
                throw new SocketException("Invalid socket protocol");
            }
        }

        /**
         * Binds to a socket
         * @throws SocketException
         */
        public function bind() {
            if (socket_bind($this->resource, $this->address, $this->port) === false) {
                throw new SocketException("Socket bind failed: " . $this->error());
            }
        }

        /**
         * Listens for a connection on a socket
         * @throws SocketException
         */
        public function listen() {
            if (socket_listen($this->resource, $this->backlog) === false) {
                throw new SocketException("Socket listen failed: " . $this->error());
            }
        }

        /**
         * Accepts a connection
         * @throws SocketException
         * @return Socket
         */
        public function accept() {
            $sock = socket_accept($this->resource);
            if ($sock === false) {
                throw new SocketException("Socket accept failed: " . $this->error());
            }
            return new Socket($sock);
        }

        /**
         * Receives data from a connected socket.
         * @return string
         */
        public function recv() {
            $len = @socket_recv($this->resource, $data, $this->buffer, 0);
            if ($len == 0) {
                return false;
            }
            return $data;
        }


        /**
         * Reads data from the connected socket
         * @return string
         */
        public function read() {
            $data = "";
            while (($m = socket_read($this->resource, $this->buffer)) != "") {
                $data .= $m;
            }
            return $data;
        }

        /**
         * Send the data to connected socket
         */
        public function send($data) {
            return socket_send($this->resource, $data, strlen($data), 0);
        }

        /**
         * Write the data to connected socket
         * @param string $message
         */
        public function write($data) {
            socket_write($this->resource, $data, strlen($data));
        }

        /**
         * Initiates a connection on a socket
         * @throws SocketException
         * @return boolean
         */
        public function connect() {
            try {
                return socket_connect($this->resource, $this->address, $this->port);
            } catch (SocketException $e) {
                return false;
            }
        }

        /**
         * Close the socket connection
         */
        public function close() {
            socket_close($this->resource);
        }

        /** Change the blocking mode */
        private function updateBlockingMode() {
            if ($this->blocking) {
                socket_set_block($this->resource);
            } else {
                socket_set_nonblock($this->resource);
            }
        }

        /** Returns a string describing the last error on the socket */
        private function error() {
            return socket_strerror(socket_last_error($this->resource));
        }
    }
?>