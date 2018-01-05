<?php

error_reporting(E_ERROR);

class SocketClient {

    protected $socket = null;
    protected $last_error = '';
    protected $bConnected = false;
    
    public function __construct() {
        $this->create_socket();
    }
    
    public function __destruct() {
        $this->disconnect();
    }

    public function connect($address, $port){
        
        if ($this->socket == null)            $this->create_socket();
        if ($this->socket === false){
            return false;
        }
        
        $this->last_error = '';
        $result = socket_connect($this->socket, $address, $port);

        if ($result === false) {
            $this->last_error = "socket_connect() failed.\nReason: ($result) " . socket_strerror(socket_last_error($this->socket)) . "\n";
        }
        $this->bConnected = !($result === false);
        return $this->bConnected;
    }
    
    public function disconnect(){
        if ($this->socket === false){
            return false;
        }
        socket_close($this->socket);
        $this->socket = null;
        $this->bConnected = false;
        return true;
    }
    
    public function isConnected(){        return $this->bConnected;    }

    public function setTimeout($sendTimeout, $rcvTimeout){
        if($this->isConnected() == false)            return false;
        
        socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, $sendTimeout );
        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, $rcvTimeout );
        
        return true;
    }
    
    public function write($txtBuf){
        if($this->isConnected() == false)            return false;
        
        $written = socket_write($this->socket, $txtBuf, strlen($txtBuf));
        return $written == strlen($txtBuf);
    }

    public function read($maxLen = 1460){
        if($this->isConnected() == false)            return false;
        
        // ATTENZIONE: importante perchè in php non si riesce a ricevere contempoareamente più di un certo numero di byte
        if($maxLen > 1460)
            $maxLen = 1460;
        
        $tmp = $response = socket_read($this->socket, $maxLen);
        do {
            //echo strlen( $tmp ).'  ';
            if ( strlen( $tmp ) < $maxLen ) {
                break;
            }
            $tmp = socket_read($this->socket, $maxLen);
            $response = $response.$tmp; 
        }while(true);

        return $response;
    }

    public function getLastError(){
        return $this->last_error;
    }
    
    
    //------------------------------------------------------------------------------------//
    
    protected function create_socket(){
        
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        
        $this->last_error = '';
        if ($this->socket === false) {
            $this->last_error = "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
        }
    }

}
?>
