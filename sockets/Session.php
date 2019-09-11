<?php 
    namespace BillZ96\Net;

    /** Custom Session Store class */
    class SessionStore {
        private $config;

        public function __construct() {
            $this->config = require_once('Config.php')['sess_config'];
        }

        /** Open a database connection */
        public function open() {
           $this->config['sess_conn'] = mysqli_connect( 
                $this->config['sess_server'], 
                $this->config['sess_username'], 
                $this->config['sess_password']
            );

            mysqli_select_db($this->config['sess_conn'], $this->config['sess_database']);
        }

        /** Get session value */
        public function read($id, $key) {
            $result = mysqli_query(
                $this->config['sess_conn'],
                sprintf(
                    'SELECT data FROM Sessions WHERE id = \'%s\' AND key = \'%s\'', 
                    mysqli_real_escape_string($this->config['sess_conn'], $id),
                    mysqli_real_escape_string($this->config['sess_conn'], $key)
                )
            );
            
            if ($row = mysqli_fetch_object($result)) {
                $ret = $row->data;
                mysqli_query(
                    $this->config['sess_conn'],
                    sprintf(
                        'UPDATE Sessions SET access=\'%s\' WHERE id=\'%s\' AND key = \'%s\'',
                        date('YmdHis'), 
                        mysqli_real_escape_string($this->config['sess_conn'], $id),
                        mysqli_real_escape_string($this->config['sess_conn'], $key)
                    )
                );
            } else {
                $ret = '';
            }

            return $ret;    
        }

        /** Update an old session or create a new one */
        public function write($id, $key, $data) {
            mysqli_query(
                $this->config['sess_conn'],
                sprintf(
                    'UPDATE Sessions SET data=\'%s\', access=\'%s\' WHERE id=\'%s\' AND key = \'%s\'', 
                    mysqli_real_escape_string($this->config['sess_conn'], $data), 
                    date('YmdHis'), 
                    mysqli_real_escape_string($this->config['sess_conn'], $id),
                    mysqli_real_escape_string($this->config['sess_conn'], $key)
                )
            );

            if (mysqli_affected_rows($this->config['sess_conn']) < 1) {
                mysqli_query(
                    $this->config['sess_conn'],
                    sprintf('INSERT INTO Sessions (data, access, id, key) VALUES (\'%s\', \'%s\', \'%s\', \'%s\')',
                        mysqli_real_escape_string($this->config['sess_conn'], $data),
                        date('YmdHis'),
                        mysqli_real_escape_string($this->config['sess_conn'], $id),
                        mysqli_real_escape_string($this->config['sess_conn'], $key)
                    )
                );
            }

            return true;
        }

        /** Close database connection */
        public function close() {
            mysqli_close($this->config['sess_conn']);
        }

        /** Remove session */
        public function destroy($id, $key) {
            mysqli_query(
                $this->config['sess_conn'],
                sprintf(
                    'DELETE FROM Sessions WHERE id=\'%s\' AND key = \'%s\'', 
                    mysqli_real_escape_string($this->config['sess_conn'], $id),
                    mysqli_real_escape_string($this->config['sess_conn'], $key)
                )
            );
            
            return true;
        }

        /** Remove expired sessions from the database */
        public function clean($timeout) {
            $timestamp = date('YmdHis', time() - $timeout);
            mysqli_query(
                $this->config['sess_conn'],
                sprintf('DELETE FROM Sessions WHERE access < \'%s\'', $timestamp)
            );
        }
    }

    /** Session Store Wrapper class */
    class Session {
        private $sid;
        private $store;
        
        static function generateID() {
            $salt                   = 'x7^!bo3p,.$$!$6[&Q.#,//@i"%[X';
            $random_number          = mt_rand(0, mt_getrandmax());
            $ip_address_fragment    = md5(substr($_SERVER['REMOTE_ADDR'], 0, 5));
            $timestamp              = md5(microtime(TRUE) . time());

            $hash_data = $random_number . $ip_address_fragment . $salt . $timestamp;
            $hash = hash('sha256', $hash_data);

            return $hash;
        }

        function __construct(SessionStore $store, $sid=null) {
            if ($sid === null) {
                $this->sid = Session::generateID();
            } else {
                $this->$sid = $sid;
            }
            $this->store = $store;
        }

        function get($key) {
            return $this->store->read($this->sid, $key);
        }

        function set($key, $val) {
            $this->store->write($this->sid, $val);
        }

        function isset($key) {
            $data = $this->store->read($this->sid, $key);
            return ($data !== '') ? false : true;
        }

        function unset($key) {
            $this->store->destroy($this->$sid, $key);
        }
    }


    // example:
    $globalStore = new SessionStore();
    $globalStore->open();

    $session = new Session($globalStore);
    if ($session->isset("foo")) {
        echo "set";
    } else {
        $session->set("foo", "FOO");
    }

    echo "foo is: ".$session->get("foo");
?>