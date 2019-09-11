<?php
    namespace BillZ96\Net;
    
    /** A Socket type Exception */
    class SocketException extends Exception {

        public function  __construct($message, $code = 0, $previous = null) {
            $this->message = $message;
            $this->code = $code;
            $this->message = $previous;
        }

    }
?>