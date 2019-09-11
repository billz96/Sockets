<?php
    namespace BillZ96\Net;

    class WsServer {
        public $socket;
        public $clients = [];

        public function __construct($options) {
            $this->socket = new WebSocket($options);
            $this->socket->setOption(SO_REUSEADDR, true);
            $this->socket->setBlocking(false);
        }

        public function run(callable $hanlder) {
            $this->socket->bind();
            $this->socket->listen();
            $this->log('Connected');
            $this->log("Listen at {$this->socket->getAddress()}:{$this->socket->getPort()}");

            while (true) {
                try {
                    $sock = $this->socket->accept();
                } catch (Exception $e) {
                    $sock = null;
                }

                if ($sock) {
                    if ($this->socket->handshake($sock)) {
                        $client = $this->createClient($sock);
                        $this->appendClient($client);
                    }
                }

                foreach ($this->clients as $client) {
                    $now = time();
                    $data = $this->receiveFrom($client);
                    
                    if ($data) {
                        $request = Request::withHeaderString($data);

                        $response = $handler($request);
                        if (!$response || !$response instanceof Response ) {
                            $response = Response::error(404);
                        }
                        $response = (string) $response;
                        $this->sendTo($client, $response);
                    }

                    $client->last = $now;
                    $client->pinging = false;
                }

                // check inactivity (1 min)
                $elapsed = $now - $client->last;
                if ($elapsed > 60) {
                    // send ping message
                    if ($elapsed < 80) {
                        if (!$client->pinging) {
                            $this->sendTo($client, 'ping');
                            $client->pinging = true;
                        }
                    } else {
                        $this->removeClient($client);
                    }
                }
            }
        }

        public function createClient(Socket $sock) {
            $socket->setBlocking(false);
            $id = uniqid('client_');
            return new WsClient($id, $socket);
        }

        public function appendClient(WsClient $client) {
            $this->clients[$client->id] = $client;
            $this->log('Accepted new client: '.$client->id);
        }

        public function receiveFrom(WsClient $client) {
            $data = $this->socket->recvFrom($client->socket);
            if (!empty($data)) {
                $this->log('Received data from: '.$client->id);
                return $data;
            }
            return;
        }

        public function sendTo(WsClient $client, $data) {
            $this->socket->writeTo($client->socket, $data);
            $this->log('Sent data to: '.$client->id);
        }

        public function removeClient(WsClient $client) {
            // close connection
            $client->socket->close();
            // remove client from clients array
            unset($this->clients[$client->id]);
            $this->log("Removed client: {$client->id}");
        }

        public function log($msg) {
            $date = date('Y-m-d H:i:s');
            echo "[$date] $msg\n";
        }
    }
?>