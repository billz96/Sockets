<?php 
    namespace BillZ96\Net;
    
    class Request {
        
        protected $method = null;
        protected $uri = null;
        protected $parameters = [];
        protected $headers = [];
        
        public static function withHeaderString( $header ) {
            $lines = explode( "\n", $header );
            
            // get method and uri
            list( $method, $uri ) = explode( ' ', array_shift( $lines ) );
            
            $headers = [];
            
            foreach( $lines as $line ) {
                // clean the line
                $line = trim( $line );
                
                if ( strpos( $line, ': ' ) !== false ) {
                    list( $key, $value ) = explode( ': ', $line );
                    if (!isset($headers[$key])) {
                        $headers[$key] = [$value];
                    } else {
                        $headers[$key][] = $value;
                    }
                }
            }	
            
            // create new request object
            return new static( $method, $uri, $headers );
        }
        
        public function __construct( $method, $uri, $headers = [] ) {
            $this->headers = $headers;
            $this->method = strtoupper( $method );
            
            // split uri and parameters string
            @list( $this->uri, $params ) = explode( '?', $uri );

            // parse the parmeters
            parse_str( $params, $this->parameters );
        }
        
        public function method() {
            return $this->method;
        }
        
        public function uri() {
            return $this->uri;
        }
        
        public function header( $key, $default = null ) {
            if (!isset( $this->headers[$key] )) {
                return $default;
            }
            
            return count($this->headers[$key]) > 1 ? $this->headers[$key] : $this->headers[$key][0];
        }

        public function getHeaders() {
            return $this->headers;
        }
        
        public function param( $key, $default = null ) {
            if (!isset( $this->parameters[$key] )) {
                return $default;
            }
            
            return $this->parameters[$key];
        }

        public function getCookies() {
            $cookieHeader = $this->$headers['Cookie'];
            if ($cookieHeader !== null) {
                $rawCookies = explode(' ', $cookieHeader);
                $cookies = [];
                foreach($rawCookies as $item) {
                    list($key, $val) = explode('=', $item); // "cookie-name=cookie-value"
                    $cookies[$key] = explode(";", $val)[0]; // $cookies["cookie-name"] = "cookie-value"
                }
                return $cookies;
            }
            return;
        }

        public function getCookie($name, $default = '') {
            $cookies = $this->getCookies();
            if ($cookies !== null) {
                $value = $cookies[$name];
                if (!$value) {
                    return $default;
                }
                return $value;
            }
            return $default;
        }
    }

?>