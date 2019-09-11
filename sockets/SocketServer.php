<?php
    namespace BillZ96\Net;

    /** A simple server socket class */
    class SocketServer {

        private $socket;
        private $clients;

        public function __construct(array $options) {
            $this->$socket = new Socket($options);
            $this->$socket->setBlocking(false);
        }

        public function run(callable $handler) {
            $this->socket->bind();
            $this->socket->listen();
            $this->log("Listening at {$this->socket->getAddress()}:{$this->socket->getPort()}");

            while (true) {
                // accept a connection
                try {
                    $sock = $this->socket->accept();
                } catch (Exception $e) {
                    $sock = null;
                }

                // append client if it exists
                if ($sock) {
                    $this->addClient($sock);
                }

                // read client's data and send a response
                foreach ($this->clients as $socket) {
                    $data = $this->receiveFrom($socket);
                    if ($data) {
                        $request = Request::withHeaderString($data);

                        $response = $handler($request);
                        if (!$response || !$response instanceof Response ) {
                            $response = Response::error(404);
                        }
                        $response = (string) $response;

                        $this->sendTo($socket, $response);
                    } else {
                        // remove client if it hasn't any data
                        $this->removeClient($socket);
                    }
                }
            }
        }

        public function addClient(Socket $sock) {
            $sock->setBlocking(false);
            $this->clients[] = $sock;
        }

        public function removeClient(Socket $sock) {
            $sock->close(); // close connection
            $key = array_search($sock, $this->clients);
            unset($this->clients[$key]);
            $this->log("client[$key] was removed");
        }

        public function receiveFrom(Socket $sock) {
            $data = $sock->recv();
            if (!empty($data)) {
                return $data;
            }
            return;
        }

        public function sendTo(Socket $sock, string $message) {
            $sock->write($message);
            $key = array_search($sock, $this->clients);
            $this->log("A message was sent to client[$key]");
        }

        public function log($msg) {
            $date = date('Y-m-d H:i:s');
            echo "[$date] $msg\n";
        }
    }
?>