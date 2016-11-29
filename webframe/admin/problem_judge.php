<?php
	/*  
		This is a HUST OJ adapter.
		HUST OJ's Judged (Judge Daemon) use this as an api to set solution judge result.
		
		POST:
		'manual'
			'sid','result',
			'explain' // optional, judge result explain.
		'update_solution'
			'sid','result',
			'time','memory','pass_rate',
			'sim','simid' // optional, for sim (cheat check)
		'checkout' // Update all unjudged sumbit as {$result} ???
			'sid','result'
		'getpending' // Return a list of pending status solution_id, one sid per line.
			'max_running'
			'oj_lang_set'
		'getsolutioninfo' // Return problem_id, user_id and language of given sid.
			'sid'
		'getsolution' // Get solution source of given sid. 
			'sid'
		'getcustominput' // Custon input of given sid. wait, wtf is it?
			'sid'
		'getprobleminfo' // given pid. finally one not using sid.
			'pid'
	*/

	session_start();
	
	$ON_ADMIN_PAGE="Yap";
	require_once("../include/setting_oj.inc.php");
	
	// Permission check
	if (!(isset($_SESSION['http_judge']))){
		echo "403";
		exit(1);
	}
	
	if (isset($_POST['manual'])) {
		$sid = intval($_POST['sid']);
        $result = intval($_POST['result']);
		if ($result >= 0) {
			$sql=$pdo->prepare("UPDATE solution SET result=? WHERE solution_id=? LIMIT 1");
			$sql->execute(array($result,$sid));
		}
		if(isset($_POST['explain'])){
			$sql=$pdo->prepare("DELETE FROM runtimeinfo WHERE solution_id=?");
			$sql->execute(array($sid));
			
			// make sure $reinfo safe for db?
			$sql=$pdo->prepare("INSERT INTO runtimeinfo VALUES(?,?)");
			$sql->execute(array($sid,$reinfo));
        }
		echo "<script>history.go(-1);</script>";
		exit(1); // should return now?
	}
	
	if(isset($_POST['update_solution'])) {
		$sid = intval($_POST['sid']);
		$result = intval($_POST['result']);
		$time = intval($_POST['time']);
		$memory = intval($_POST['memory']);
		$sim = intval($_POST['sim']);
		$simid = intval($_POST['simid']);
		$pass_rate = floatval($_POST['pass_rate']);
		
		$sql=$pdo->prepare("UPDATE solution SET result=?,time=?,memory=?,judgetime=NOW(),pass_rate=? WHERE solution_id=? LIMIT 1");
		$sql->execute(array($result,$time,$memory,$pass_rate,$sid));
		
		if ($sim) {
			$sql=$pdo->prepare("INSERT INTO sim(s_id,sim_s_id,sim) VALUES(?,?,?) ON DUPLICATE KEY UPDATE sim_s_id=?,sim=?");
			$sql->execute(array($sid,$simid,$sim,$simid,$sim));
		}
		
		exit(1);
	}
	
	if(isset($_POST['checkout'])) {
		$sid = intval($_POST['sid']);
		$result = intval($_POST['result']);
		$sql=$pdo->prepare("UPDATE solution SET result=?,time=0,memory=0,judgetime=NOW() WHERE solution_id=? and (result<2 or (result<4 and NOW()-judgetime>60)) LIMIT 1");
		$affectedRowCnt = $sql->execute(array($result, $sid));
		
		echo ($affectedRowCnt > 0) ? "1" : "0";
		exit(1);
	}
	
	if(isset($_POST['getpending'])) {
		$max_running = intval($_POST['max_running']);
		$oj_lang_set = $_POST['oj_lang_set'];
		$sql=$pdo->prepare("SELECT solution_id FROM solution WHERE language in ($oj_lang_set) and (result<2 or (result<4 and NOW()-judgetime>60)) ORDER BY result ASC,solution_id ASC limit $max_running");
		$sql->execute();
		$result = $sql->fetchAll(PDO::FETCH_ASSOC);
		
		foreach($result as $row) {
			echo $row['solution_id']."\n";
		}
		
		exit(1);
	}
	
	if(isset($_POST['getsolutioninfo'])) {
		$sid = intval($_POST['sid']);
		$sql=$pdo->prepare("SELECT problem_id, user_id, language FROM solution WHERE solution_id=?");
		$sql->execute(array($sid));
		$result = $sql->fetch(PDO::FETCH_ASSOC);
		
		if(count($result) == 1) {
			echo $result['problem_id']."\n";
			echo $result['user_id']."\n";
			echo $result['language']."\n";	
		}
		
		exit(1);
	}
	
	if(isset($_POST['getsolution'])) {
		$sid = intval($_POST['sid']);
		$sql = $pdo->prepare("SELECT source FROM source_code WHERE solution_id=?");
		$sql->execute(array($sid));
		$result = $sql->fetch(PDO::FETCH_ASSOC);

		if(count($result) == 1) {
			echo $result['source']."\n";
		}
		
		exit(1);
	}
	
	if(isset($_POST['getcustominput'])) {
		$sid = intval($_POST['sid']);
		$sql = $pdo->prepare("SELECT input_text FROM custominput WHERE solution_id=?");
		$sql->execute(array($sid));
		$result = $sql->fetch(PDO::FETCH_ASSOC);

		if(count($result) == 1) {
			echo $result['input_text']."\n";
		}

		exit(1);
	}

	if(isset($_POST['getprobleminfo'])) {
		$pid = intval($_POST['pid']);
		$sql = $pdo->prepare("SELECT time_limit,memory_limit,spj FROM problem where problem_id=?");
		$sql->execute(array($pid));
		$result = $sql->fetch(PDO::FETCH_ASSOC);

		if(count($result) == 1) {
			echo $result['time_limit']."\n";
			echo $result['memory_limit']."\n";
			echo $result['spj']."\n";
		}

		exit(1);
	}
?>