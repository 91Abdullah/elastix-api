<?php 
include_once dirname(__FILE__)."/lib/ini/ini_handler.php";
include_once dirname(__FILE__)."/lib/json/phpJson.class.php";
include_once dirname(__FILE__)."/lib/ast/Extension.php";
class Elastix{
	public function __construct(){
		$fh = fopen('/etc/elastix.conf','r');
		$data = array();
		while ($line = fgets($fh)) {
			if(strlen($line) > 1){
				$doarr = split("=", $line);
				$passwd = (string)$doarr[1];
				$passwd = str_replace("\n", "", $passwd);
				$data[(string)$doarr[0]] = $passwd;
			}
		}
		fclose($fh);
		$this->hostname = "127.0.0.1";
		$this->username = "root";
		$this->password = $data["mysqlrootpwd"];
		$this->db = null;
	}
	public function __destruct(){
		try {
			$this->db = null;
		} catch (PDOException $e) {
			echo $e->getMessage();
		}
	}
	private function _get_db_connection($dbname){
		try {
			$this->db = new PDO("mysql:host=".$this->hostname.";dbname=".$dbname.";charset=utf8", $this->username, $this->password);
			$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->db->query("SET CHARACTER SET utf8");
		} catch (PDOException $e) {
			echo $e->getMessage();
		}
	}
	private function _cdr_where_expression($start_date, $end_date, $field_name, $field_pattern, $status, $custom){
		$where = "";
		$where .= "(calldate BETWEEN '$start_date' AND '$end_date')";
		$where .= " AND ";
		$field_name_arr = array();
		$field_name_arr["src"] = "src";
		$field_name_arr["dst"] = "dst";
		$field_name_arr["channel"] = "channel";
		$field_name_arr["dstchannel"] = "dstchannel";
		$field_name_arr["accountcode"] = "accountcode";

		$where .= "(".$field_name_arr[$field_name]." LIKE '%".$field_pattern."%')";
		$where .= " AND ";
		$where .= ($status === "ALL") ? "(disposition IN ('ANSWERED', 'BUSY', 'FAILED', 'NO ANSWER'))" : "(disposition = '$status')";
		$where .= " AND dst != 's' ";
		$where .= $custom;
		return $where;
	}

	
	public function get_cdr_recordings()
	{
		try {
                        $this->_get_db_connection("asteriskcdrdb");
                        $start_date             = $_GET["start_date"];
                        $end_date                       = $_GET["end_date"];
                        $field_name             = $_GET["field_name"];
                        $field_pattern          = $_GET["field_pattern"];
                        $status                         = $_GET["status"];
                        $limit                          = $_GET["limit"];
                        $custom                         = $_GET["custom"];
                        $where_expression       = $this->_cdr_where_expression($start_date, $end_date, $field_name, $field_pattern, $status, $custom);
                        $limit                          = $limit > 0 ? " LIMIT ".$limit." " : "";
                        $sql_cmd        = "SELECT * FROM cdr WHERE $where_expression AND recordingfile <> '' ORDER BY calldate DESC $limit";
                        //echo json_encode($sql_cmd);
			$stmt           = $this->db->query($sql_cmd);
                        $stmt->execute();
                        $result = (array)$stmt->fetchAll(PDO::FETCH_ASSOC);
                        header('Content-Type: application/json');
                        echo json_encode($result);
                } catch (Exception $e) {
                        echo $e->getMessage();
        	}

	}	


	public function get_cdr(){
		/*
			+---------------+
			| COLUMN_NAME   |
			+---------------+
			| calldate      | 
			| clid          | 
			| src           | 
			| dst           | 
			| dcontext      | 
			| channel       | 
			| dstchannel    | 
			| lastapp       | 
			| lastdata      | 
			| duration      | 
			| billsec       | 
			| disposition   | 
			| amaflags      | 
			| accountcode   | 
			| uniqueid      | 
			| userfield     | 
			| recordingfile | 
			| cnum          | 
			| cnam          | 
			| outbound_cnum | 
			| outbound_cnam | 
			| dst_cnam      | 
			| did           | 
			+---------------+
		*/
		try {
			$this->_get_db_connection("asteriskcdrdb");
			$start_date 		= $_GET["start_date"];
			$end_date 			= $_GET["end_date"];
			$field_name 		= $_GET["field_name"];
			$field_pattern 		= $_GET["field_pattern"];
			$status 			= $_GET["status"];
			$limit 				= $_GET["limit"];
			$custom 			= $_GET["custom"];
			$where_expression 	= $this->_cdr_where_expression($start_date, $end_date, $field_name, $field_pattern, $status, $custom);
			$limit 				= $limit > 0 ? " LIMIT ".$limit." " : "";
			$sql_cmd 	= "SELECT * FROM cdr WHERE $where_expression ORDER BY calldate DESC $limit";
			$stmt 		= $this->db->query($sql_cmd);
			//$stmt->execute();
			$result = (array)$stmt->fetchAll(PDO::FETCH_ASSOC);
			header('Content-Type: application/json');
			echo json_encode($result);
		/*try {
			$this->_get_db_connection("asteriskcdrdb");
			$sql_cmd	= "SELECT * FROM cdr";
			$stmt		= $this->db->prepare($sql_cmd);
			$result = (array)$stmt->fetchAll(PDO::FETCH_ASSOC);
                        header('Content-Type: application/json');
                        echo json_encode($this->db->query("SELECT * FROM cdr")->fetchAll(PDO::FETCH_ASSOC));*/
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}
	public function get_wav_file(){
		/*
			$name = "/2015/12/08/out-05355620760-101-20151208-102449-1449563089.106.wav";
		*/
		$is_exist = true;
		$name 		= $_GET["name"];
		$directory 	= "/var/spool/asterisk/monitor";
		//$file = $directory;
		//$file .= $name;
		$file = $name; // changed as name was already up to the mark
		if (!file_exists($file)) {
			$path 	= pathinfo($file);
			$dn 	= $path["dirname"];
			$bn 	= $path["basename"];
			exec("ls ".$dn." | grep ".$bn, $data);
			$file 	= $dn;
			$file .= "/".$data[0];
			if(!file_exists($file)){
				$is_exist = false;
				//die("File not found");
			}
		}
		if($is_exist){
			header("Content-Disposition: attachment; filename=\"" . basename($file) . "\"");
			header("Content-Length: " . filesize($file));
			header("Content-Type: application/octet-stream;");
			readfile($file);	
		} else {
			header("HTTP/1.0 404 Not Found");
			header("Content-Type: application/json");
			echo '{"status": "File not found", "code": 404}';
		}
		
	}
	public function get_harddrivers(){
		$main_arr = array();
		exec("df -H /", $harddisk);
		exec("du -sh /var/log", $logs);
		exec("du -sh /opt", $thirdparty);
		exec("du -sh /var/spool/asterisk/voicemail", $voicemails);
		exec("du -sh /var/www/backup", $backups);
		exec("du -sh /etc", $configuration);
		exec("du -sh /var/spool/asterisk/monitor", $recording);
		$hard_arr = array();
		$tmp_arr = explode(" ", trim(preg_replace("/\s\s+/", " ", $harddisk[2])));
		$hard_arr["size"] 		= $tmp_arr[0];
		$hard_arr["used"] 		= $tmp_arr[1];
		$hard_arr["avail"] 		= $tmp_arr[2];
		$hard_arr["usepercent"] = $tmp_arr[3];
		$hard_arr["mount"] 		= $tmp_arr[4];
		$main_arr["harddisk"] = $hard_arr;
		$main_arr["logs"] = explode("\t", $logs[0]);
		$main_arr["thirdparty"] = explode("\t", $thirdparty[0]);
		$main_arr["voicemails"] = explode("\t", $voicemails[0]);
		$main_arr["backups"] = explode("\t", $backups[0]);
		$main_arr["configuration"] = explode("\t", $configuration[0]);
		$main_arr["recording"] = explode("\t", $recording[0]);
		header("Content-Type: application/json");
		echo json_encode($main_arr);
	}
	public function get_iptables_status(){
		$exist = 'false';
		$pid = shell_exec("sudo /sbin/service iptables status 2>&1");
		if (strlen($pid) > 100) {
			$exist = 'true';
		}
		header("Content-Type: application/json");
		echo '{"pid": "'.$pid.'", "is_exist": '.$exist.'}';
	}
	private function apply_config(){
		exec("/var/lib/asterisk/bin/module_admin reload", $data);
	}
	public function add_sip_extension(){
		$this->_get_db_connection("asterisk");
		$dict = array(
			"name" => $_GET["name"],
			"deny" => $_GET["deny"],
			"secret" => $_GET["secret"],
			"dtmfmode" => $_GET["dtmfmode"],
			"canreinvite" => $_GET["canreinvite"],
			"context" => $_GET["context"],
			"host" => $_GET["host"],
			"trustrpid" => $_GET["trustrpid"],
			"sendrpid" => $_GET["sendrpid"],
			"type" => $_GET["type"],
			"nat" => $_GET["nat"],
			"port" => $_GET["port"],
			"qualify" => $_GET["qualify"],
			"qualifyfreq" => $_GET["qualifyfreq"],
			"transport" => $_GET["transport"],
			"avpf" => $_GET["avpf"],
			"icesupport" => $_GET["icesupport"],
			"encryption" => $_GET["encryption"],
			"callgroup" => $_GET["callgroup"],
			"pickupgroup" => $_GET["pickupgroup"],
			"dial" => $_GET["dial"],
			"mailbox" => $_GET["mailbox"],
			"permit" => $_GET["permit"],
			"callerid" => $_GET["callerid"],
			"callcounter" => $_GET["callcounter"],

			"faxdetect" => $_GET["faxdetect"],
			"account" => $_GET["account"]
		);
		$ext = new Extension($dict, "insert");
		$stmt0 = $this->db->prepare($ext->select_sip_sqlscript());
		$stmt0->execute();
		$row = $stmt0->fetch(PDO::FETCH_ASSOC);
		if(!$row){
			$stmt1 = $this->db->exec($ext->insert_into_users_sqlscript());
			$stmt2 = $this->db->exec($ext->insert_into_devices_sqlscript());
			$stmt3 = $this->db->exec($ext->insert_into_sip_sqlscript());
			$this->apply_config();
			// echo '{"status": '.var_dump($row).', "code": 200}';
		}
		// header('Content-Type: application/json');
		// echo '{"status": "INSERT OK", "code": 200}';
		echo '{"status": '.var_dump($row).', "code": 200}';
	}
	public function update_sip_extension(){
		$this->_get_db_connection("asterisk");
		$dict = array(
			"name" => $_GET["name"],
			"deny" => $_GET["deny"],
			"secret" => $_GET["secret"],
			"dtmfmode" => $_GET["dtmfmode"],
			"canreinvite" => $_GET["canreinvite"],
			"context" => $_GET["context"],
			"host" => $_GET["host"],
			"trustrpid" => $_GET["trustrpid"],
			"sendrpid" => $_GET["sendrpid"],
			"type" => $_GET["type"],
			"nat" => $_GET["nat"],
			"port" => $_GET["port"],
			"qualify" => $_GET["qualify"],
			"qualifyfreq" => $_GET["qualifyfreq"],
			"transport" => $_GET["transport"],
			"avpf" => $_GET["avpf"],
			"icesupport" => $_GET["icesupport"],
			"encryption" => $_GET["encryption"],
			"callgroup" => $_GET["callgroup"],
			"pickupgroup" => $_GET["pickupgroup"],
			"dial" => $_GET["dial"],
			"mailbox" => $_POST["mailbox"],
			"permit" => $_GET["permit"],
			"callerid" => $_GET["callerid"],
			"callcounter" => $_GET["callcounter"],
			"faxdetect" => $_GET["faxdetect"],
			"account" => $_GET["account"]
		);
		$ext = new Extension($dict, "update");
		$stmt1 = $this->db->exec($ext->update_sip_sqlscript());
		$stmt2 = $this->db->exec($ext->update_users_sqlscript());
		$this->apply_config();
		header('Content-Type: application/json');
		echo '{"status": "UPDATE OK", "code": 200}';
	}
	public function delete_sip_extension(){
		$this->_get_db_connection("asterisk");
		$dict = array("account" => $_GET["account"]);
		$ext = new Extension($dict, "delete");
		$stmt1 = $this->db->exec($ext->delete_sip_sqlscript());
		$stmt2 = $this->db->exec($ext->delete_users_sqlscript());
		$stmt3 = $this->db->exec($ext->delete_devices_sqlscript());
		$this->apply_config();
		header('Content-Type: application/json');
		echo '{"status": "DELETE OK", "code": 200}';
	}
	private function apply_retrieve(){
		exec("/var/lib/asterisk/bin/retrieve_conf", $data);
	}
	private function show_ampuser($dict){
		exec('/usr/sbin/asterisk -rx "database show AMPUSER '.$dict["grpnum"].'/followme');
	}
	private function put_ampuser($dict){
		exec('/usr/sbin/asterisk -rx "database put AMPUSER '.$dict["grpnum"].'/followme/changecid default"');
		exec('/usr/sbin/asterisk -rx "database put AMPUSER '.$dict["grpnum"].'/followme/ddial DIRECT"');
		exec('/usr/sbin/asterisk -rx "database put AMPUSER '.$dict["grpnum"].'/followme/fixedcid "');
		exec('/usr/sbin/asterisk -rx "database put AMPUSER '.$dict["grpnum"].'/followme/grpconf ENABLED"');
		exec('/usr/sbin/asterisk -rx "database put AMPUSER '.$dict["grpnum"].'/followme/grplist '.$dict["grplist"].'"');
		exec('/usr/sbin/asterisk -rx "database put AMPUSER '.$dict["grpnum"].'/followme/grptime '.$dict["grptime"].'"');
		exec('/usr/sbin/asterisk -rx "database put AMPUSER '.$dict["grpnum"].'/followme/prering '.$dict["pre_ring"].'"');
	}
	private function deltree_ampuser($dict){
		exec('/usr/sbin/asterisk -rx "database deltree AMPUSER '.$dict["grpnum"].'/followme"');
	}
	public function add_followme_extension(){
		$this->_get_db_connection("asterisk");
		$dict = array(
			"grpnum" => $_POST["grpnum"],
			"strategy" => $_POST["strategy"],
			"grptime" => $_POST["grptime"],
			"grppre" => $_POST["grppre"],
			"grplist" => $_POST["grplist"],
			"annmsg_id" => $_POST["annmsg_id"],
			"postdest" => $_POST["postdest"],
			"dring" => $_POST["dring"],
			"remotealert_id" => $_POST["remotealert_id"],
			"needsconf" => $_POST["needsconf"],
			"toolate_id" => $_POST["toolate_id"],
			"pre_ring" => $_POST["pre_ring"],
			"ringing" => $_POST["ringing"]
		);
		$this->put_ampuser($dict);
		$find = new FindMeFollow($dict, "insert");
		$stmt0 = $this->db->prepare($find->select_findmefollow_sqlscript());
		$stmt0->execute();
		$row = $stmt0->fetch(PDO::FETCH_ASSOC);
		if(!$row){
			$stmt1 = $this->db->exec($find->insert_into_findmefollow_sqlscript());
			$this->apply_retrieve();
			$this->apply_config();
		}
		header('Content-Type: application/json');
		echo '{"status": "INSERT OK", "code": 200}';
	}
	public function update_followme_extension(){
		$this->_get_db_connection("asterisk");
		$dict = array(
			"grpnum" => $_POST["grpnum"],
			"strategy" => $_POST["strategy"],
			"grptime" => $_POST["grptime"],
			"grppre" => $_POST["grppre"],
			"grplist" => $_POST["grplist"],
			"annmsg_id" => $_POST["annmsg_id"],
			"postdest" => $_POST["postdest"],
			"dring" => $_POST["dring"],
			"remotealert_id" => $_POST["remotealert_id"],
			"needsconf" => $_POST["needsconf"],
			"toolate_id" => $_POST["toolate_id"],
			"pre_ring" => $_POST["pre_ring"],
			"ringing" => $_POST["ringing"]
		);
		$this->put_ampuser($dict);
		$find = new FindMeFollow($dict, "update");
		$stmt1 = $this->db->exec($find->update_findmefollow_sqlscript());
		$this->apply_retrieve();
		$this->apply_config();
		header('Content-Type: application/json');
		echo '{"status": "UPDATE OK", "code": 200}';
	}
	public function delete_followme_extension(){
		$this->_get_db_connection("asterisk");
		$dict = array("grpnum" => $_POST["grpnum"]);
		$this->deltree_ampuser($dict);
		$find = new FindMeFollow($dict, "delete");
		$stmt1 = $this->db->exec($find->delete_findmefollow_sqlscript());
		$this->apply_retrieve();
		$this->apply_config();
		header('Content-Type: application/json');
		echo '{"status": "DELETE OK", "code": 200}';
	}
	public function view_followme_extension(){
		$this->_get_db_connection("asterisk");
		$dict = array("grpnum" => $_POST["grpnum"]);
		$find = new FindMeFollow($dict, "select");
		$stmt1 = $this->db->prepare($find->select_findmefollow_sqlscript());
		$stmt1->execute();
		$result = (array)$stmt1->fetchAll(PDO::FETCH_ASSOC);
		header('Content-Type: application/json');
		echo json_encode($result);
	}
	public function view_followme_all_extensions(){
		$this->_get_db_connection("asterisk");
		$dict = array();
		$find = new FindMeFollow($dict, "selectall");
		$stmt1 = $this->db->prepare($find->select_all_findmefollow_sqlscript());
		$stmt1->execute();
		$result = (array)$stmt1->fetchAll(PDO::FETCH_ASSOC);
		header('Content-Type: application/json');
		echo json_encode($result);
	}
}
?>
