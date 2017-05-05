<?php
/**
 * @package database
 * @category Database MySQL Shortlink
 */
final class database{
	/**
	 * set Host Name
	 * @var string
	 * @access private
	 */
	private $dbhost = DBHOST;
	
	/**
	 * set database name
	 * @var string
	 * @access private
	 */
    private $dbname = DBNAME;
    
    /**
     * set user name
     * @var string
     * @access private
     */
    private $dbuser = DBUSER;
    
    /**
     * set user pass
     * @var string
     * @access private
     */
    private $dbpass = DBPASS;
    
    /**
     * set DSN connection string
     * @var string
     * @access private
     */
    private $dsn;
    
    /**
     * set Database connection object
     *
     */
    private $dbconn;
    
    public function getDateTimeNow(){
        $dtnow = new DateTime('NOW');
        //return $dtnow->format(DateTime::ISO8601);
        return $dtnow->format('Y-m-d H:i:s');
    }
    
    public function nowUnixDateTime(){
    	$nowdt = new DateTime();
    	return $nowdt->format('U');
    }

	public function todayMidnightUnixDateTime(){
    	$timedt = new DateTime();
    	$timedt->setTime(0,0);
    	return $timedt->format('U');
    }
    
    private function getDsn() {
        $this->dsn = "mysql:dbname=".$this->dbname.";host=".$this->dbhost.";charset=utf8";
        return $this->dsn;
    }
    
    //public function dbconnect(){
    public function __construct() {
        try{
            $this->dbconn = new PDO($this->getDsn(),$this->dbuser,$this->dbpass,array(PDO::ATTR_EMULATE_PREPARES => FALSE,PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,PDO::ATTR_PERSISTENT => TRUE));
            //$this->dbconn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            //return "Connection to database \"".$this->dbname."\" establish";
    		//error_log("Connected!", 3, "/home/admin/html/new/php_errors.log");
        
            return true;
        }  catch(Exception $e){
        	//error_log("Connection to database \"".$this->dbname."\" fail<br/>".$e->getMessage(), 3, "/home/admin/html/new/php_errors.log");
            return "Connection to database \"".$this->dbname."\" fail<br/>".$e->getMessage();
            die();
        }
    }

    public function __destruct() {
        $this->dbconn = NULL;
    }
    
    public function dbdisconnect(){ //if persitent connect establish
        $this->dbconn = NULL;
    }
    
    public function doQuery($qstring,$bindparam=NULL,$format="array",$getid=FALSE){ //$format->"json" or "Array"
        //what type statement is this
        $states = explode(' ',trim($qstring)); 
        $state = strtolower($states[0]);
        $data = "";
        
        switch($state){
            case "select":
                try{	
                    $query = $this->dbconn->prepare($qstring);
                    $query->execute($bindparam);
                    $result = $query->fetchAll(PDO::FETCH_ASSOC); //resulting with table row name as key
                    //error_log("ccc - ", 3, "/home/admin/html/new/php_errors.log");
            		
            		$data = array("data"=>array("result"=>"ok","query_string"=>(string)$qstring,"query_param"=>$bindparam,"query_result"=>$result));
                }catch(Exception $e){
                    $data=array("data"=>array("result"=>"fail","query_string"=>(string)$qstring,"error_message"=>(string)$e->getMessage()));
                	
                }
                break;
            case "insert":
            case "update":
			case "call":
            case "delete":
                try{
                    $query = $this->dbconn->prepare($qstring);
                    $query->execute($bindparam);
                    if($getid){
                        $data = array("data"=>array("result"=>"ok","query_string"=>(string)$qstring,"query_param"=>$bindparam,"query_id"=>(int)$this->dbconn->lastInsertId()));
                    }else{
                        $data = array("data"=>array("result"=>"ok","query_string"=>(string)$qstring,"query_param"=>$bindparam, "query_id"=>(int)$this->dbconn->lastInsertId()));
                    }
                }catch(Exception $e){
                    $data=array("data"=>array("result"=>"fail","query_string"=>(string)$qstring,"error_message"=>(string)$e->getMessage()));
                }
                break;
            case "truncate":
            	try{
            		$query = $this->dbconn->exec($qstring);
            		$data = array("data"=>array("result"=>"ok","query_string"=>(string)$qstring));
            	}catch(Exception $e){
                    $data=array("data"=>array("result"=>"fail","query_string"=>(string)$qstring,"error_message"=>(string)$e->getMessage()));
                }
            	break;
        }
        //default output format is array
        //$data = $this->dbconn->query($qstring);
            		
        switch($format){
            case "json":
                $output = json_encode($data);
                break;
            case "array":
                $output = $data;
                break;
        }
        return $output;
    }

}

?>
