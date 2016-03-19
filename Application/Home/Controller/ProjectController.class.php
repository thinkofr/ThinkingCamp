<?php
namespace Home\Controller;

use Think\Controller;
use Think\Upload;
use Home\Event\ErrorEvent;
use Home\Event\UserEvent;
use Org\Util\Date;
use Think\Crypt\Driver\Think;
use Home\Event\UtilityEvent;

// Main page logic handle
class ProjectController extends Controller
{
	public function index($showFavoriteOnly = 0)
    {
    	if(null == session ( 'currentalias' ))
    		$this->redirect('User/index');

		$errEvent = new ErrorEvent ();
		$ret = $errEvent->setErrorEvent ();

		//prepare condition for only show favorite
		$currentUser = session ( 'currentalias' );

		$favorite = M ('Favorite');
			$favoriteList =  $favorite->where("useralias='".$currentUser."'")->select ();
		if ($favoriteList === false)
		{
			$errno = 4000; // mysql error
			$errmsg = "mysql select favorite list error";
			$ret = $errEvent->setErrorEvent ( $errno, $errmsg );
			exit ( json_encode ( $ret ) );
		};

		$project = M ( 'Project' );
    $projectList = array();
		if ($showFavoriteOnly == 0)
		{
			$projectList = $project->order('createtime desc' )->select ();
		}
		else
		{
			//when login user has some favorite
			if(count($favoriteList) >0 )
			{
				$favoriteProjectIdList = array();
				foreach($favoriteList as $favoriteItem)
				{
					array_push($favoriteProjectIdList, $favoriteItem['projectid']);
				}
				$searchCond2['id'] = array('in', $favoriteProjectIdList );
				$projectList = $project->where ( $searchCond2 )->order('createtime desc' )->select ();
			}
		}

		if ($projectList === false)
		{
			$errno = 4000; // mysql error
			$errmsg = "mysql select project list error";
			$ret = $errEvent->setErrorEvent ( $errno, $errmsg );
			exit ( json_encode ( $ret ) );
		}

		$user = M ( 'User' );
		$userlist = $user->select ();
		if ($userlist === false)
		{
			$errno = 4000; // mysql error
			$errmsg = "mysql select user list error";
			$ret = $errEvent->setErrorEvent ( $errno, $errmsg );
			exit ( json_encode ( $ret ) );
		}

		for($i = 0; $i < count ( $projectList ); $i ++)
		{
			foreach ( $userlist as $user )
			{
				if (strcmp ( $projectList [$i] ['owneralias'], $user ['alias'] ) === 0)
				{
					$projectList [$i] ['ownername'] = $user ['name'];
					$projectList [$i] ['ldaplink'] = $user ['ldaplink'];
					$projectList [$i] ['photolink'] = $user ['photolink'];
					break;
				}
			}

			$createdDateTime = date('D M d Y',strtotime($projectList [$i] ['createtime']));
			$projectList [$i] ['createddate'] = $createdDateTime;

			$updatedDateTime = date('D M d Y',strtotime($projectList [$i] ['lastactivetime']));
			$projectList [$i] ['updateddate'] = $updatedDateTime;
		}
	  $idealist = $project->where("status='Idea'")->select();
		$ideacount = count($idealist);
		$egglist = $project->where("status='Egg'")->select();
		$eggcount = count($egglist);
		$eagletlist = $project->where("status='Eaglet'")->select();
		$eagletcount = count($eagletlist);
		$eaglelist = $project->where("status='Eagle'")->select();
		$eaglecount = count($eaglelist);

		//advisorlist
		$advisor = M ('Advisorgroup');
		$advisorlist = $advisor->select();

		if ($advisorlist === false)
		{
			$errno = 4000; // mysql error
			$errmsg = "mysql select user list error";
			$ret = $errEvent->setErrorEvent ( $errno, $errmsg );
			exit ( json_encode ( $ret ) );
		}

		for($i = 0; $i < count ( $advisorlist ); $i ++){
	 		foreach ( $userlist as $u ) {
			  if ($advisorlist [$i] ['alias'] == $u ['alias']) {
		 			$advisorlist [$i] ['username'] = $u ['name'];
		 			$advisorlist [$i] ['photolink'] = $u ['photolink'];
		 			$advisorlist [$i] ['ldaplink'] = $u ['ldaplink'];
			 	}
		 	}
		}

    for($i = 0; $i < count( $projectList ); $i ++){
			foreach ( $favoriteList as $favoriteItem){
				if($projectList[$i]['id'] === $favoriteItem['projectid']){
					$projectList [$i] ['isFavorite'] = true;
				}
			}
		}

		//get project tags
		for($i = 0; $i < count( $projectList ); $i ++)
		{
			$projectList[$i]['tags'] = $this->getTagsbyProjectId($projectList[$i][id]);
		}


         




         $this->assign('taglist', $taglist);
		$this->assign('showfavoriteonly', $showFavoriteOnly);
		$this->assign ( 'projectlist', $projectList );
    $this->assign('advisorlist',$advisorlist);
		$this->assign('isAdmin', session('isAdmin'));
		$this->assign('isAdvisor', session('isAdvisor'));
	  $this->assign('ideacount',$ideacount);
		$this->assign('eggcount',$eggcount);
		$this->assign('eagletcount',$eagletcount);
		$this->assign('eaglecount',$eaglecount);
		$this->display ( 'projectlistpage' );

		$int = session('isFirstIn') +1;
		session('isFirstIn',$int);
	}

	//add innovation project, team and tags
	public function publishnewidea()
	{
		// TODO: id relative problem;
		if(null === session ( 'currentalias' ))
		{
			$this->redirect('User/index');
		}
		if(!isset($_POST['submit']))
		{
			$this->display('newprojectpage');
			exit();
		}

		// create a team when publish a project handle team id not added success
		$errEvent = new ErrorEvent ();
		$ret = $errEvent->setErrorEvent ();

		$project = D ( 'Project' );
		$ret = $project->relation('Team')->create();

		$data = array ();
		$data['title'] = $_POST ['title'];
		$data['slogan'] = $_POST ['slogan'];
		$data['background'] = $_POST ['background'];
		$data['idea'] = $_POST ['idea'];
		$data['benefit'] = $_POST ['benefit'];
		$data['status'] = 'Idea';
		$now = time();
		$date =  date('Y-m-d H:i:s', $now);
		$data['createtime'] = $date;
		$data['lastactivetime'] = $date;
    $data['maturityscore'] = $_POST ['maturityscore'];
    $data['innovationscore'] = $_POST ['innovationscore'];
    $data['businessscore'] = $_POST ['businessscore'];

		$data ['owneralias'] = (null !== session ( 'currentalias' )) ? (session ( 'currentalias' )) : "";
		//create a team when create the project
		$data ['Team'] = array (
			'description' => 'description for ' . $_POST ['title']
		);

		//TODO:ajax to catch the error, because it is a partial update
		$result = $project->relation ( 'Team' )->add ( $data );
		if ($result === false) {
			$errno = 4000; // mysql error
			$errmsg = "mysql add project error";
			$ret = $errEvent->setErrorEvent ( $errno, $errmsg );
			exit ( json_encode ( $ret ) );
		}

		if(isset($_FILES['attachfile'])){
			$upload = new Upload();
			$info = $this->uploadconfig($upload);
			$info = $upload->upload();
			// if(!$info)
			// {
			// 	$this->error($upload->getError());
			// }
			foreach($info as $file)
			{
	      $attachedfiles = M ('Attachedfiles');
				$attachedfiles->create();
				$attachedfiles->projectid = $result;
				$attachedfiles->createtime = $date;
				$attachedfiles->originalfilename = $file['name'];
				$attachedfiles->attachedfilepath = '/Uploads/'.$file['savepath'].$file['savename'];
				$attachedfiles->add();
			}
		};

		//create team
		$team = M ("Team");
		$teamid = $team->where("projectid='".$result."'")->getField("id");

		$teamuserrelationship = M ('Teamuserrelationship');
		$teamuserrelationship->create();
		//var_dump($result);
		$teamuserrelationship->teamid = $teamid;
		$teamuserrelationship->useralias = (null !== session ( 'currentalias' )) ? (session ( 'currentalias' )) : "";
		$teamuserrelationship->add();
    $statustime = M ('Statuschangetime');
		$statustime->create();
		$statustime->projectid = $result;
		$statustime->createtime = $date;
		$statustime->toeggtime = "";
		$statustime->toeaglettime = "";
		$statustime->toeagletime = "";
		$statustime->add();

		//add tags
		// $tagsString = $_POST['tags'];
		// $this->addTagsToProject($tagsString, $result);
		$this->redirect ( 'index' );
	}


	public function deleteattachedfile($projectId=-1,$filename="")
	{
		if(null === session ( 'currentalias' ))
		{
			$this->redirect('User/index');
		}
		if($projectId === -1)
		{
			$this->redirect('Project/dashboard');
		}
		if($filename === '')
		{
			$this->redirect('Project/dashboard');
		}
		$filename = str_replace("+"," ",$filename);

		$errEvent = new ErrorEvent ();
		$ret = $errEvent->setErrorEvent ();

		$attachedfiles = M ('Attachedfiles');
		$deleteResult = $attachedfiles->where("projectid=".$projectId." AND originalfilename='".$filename."'")->delete();
		if($deleteResult === false){
      $errNo = 4000;
      $errMsg = "MySQL delete attached file failed!";
      $ret = $errEvent->setErrorEvent($errNo,$errMsg);
      exit(json_encode($ret));
    };
    $this->dashboard($projectId);
	}

	public function updateInnovationIdea()
	{
		if(null === session ( 'currentalias' ))
		{
			$this->redirect('User/index');
		}
		if(null === $_POST['id'])
		{
			$this->redirect('Project/dashboard');
		}
		$errEvent = new ErrorEvent ();
		$ret = $errEvent->setErrorEvent ();

		$projectId = $_POST['id'];
		//$tags = $_POST['tags'];
		//$data['status'] = $_POST['status'];
		$data['background'] = $_POST['background'];
		$data['idea'] = $_POST['solution'];
		$data['benefit'] = $_POST['benefit'];

    $data['maturityscore'] = $_POST['maturityscore'];
    $data['innovationscore'] = $_POST['innovationscore'];
    $data['businessscore'] = $_POST['businessscore'];

		$stringUtility = new UtilityEvent();
		$repositoryLink = trim($_POST['link']);
		if ($stringUtility->startswith($repositoryLink, 'http') === false)
		$repositoryLink = "http://".$repositoryLink;
		$data['link'] = $repositoryLink;

		$data['slogan'] = $_POST['slogan'];
		$now = time();
		$date =  date('Y-m-d H:i:s', $now);
		$data ['lastactivetime'] = $date;
		//update project table
		$project = M ('Project');
		$updateResult = $project->where("id=".$projectId)->field('background,idea,benefit,slogan,link,lastactivetime,businessscore,innovationscore,maturityscore')->save($data);
    if($updateResult === false)
    {
    	$errNo = 4000;
    	$errMsg = "MySQL update project failed!";
    	$ret = $errEvent->setErrorEvent($errNo,$errMsg);
    	exit(json_encode($ret));
    }
		exit ( json_encode ( $ret ) );
		//update tag table
		//$this->addTagsToProject($tags, $projectId);
	}

	private function uploadconfig($upload)
	{
		$upload->maxsize = 3200000 ; //字节的3M
		$upload->exts = array('zipx', 'zip', 'doc', 'docx','ppt','pptx','txt', 'jpg', 'jpeg','gif','png', 'pdf','xlsx', '.xls') ;
		$upload->savePath = './';
	}

	public function dashboard($id=-1)
	{
		if(null == session ( 'currentalias' ))
		{
			$this->redirect('User/index');
		}
		if($id === -1)
		{
			$this->redirect('Project/index');
		}
		$errEvent = new ErrorEvent ();
		$ret = $errEvent->setErrorEvent ();

    $project = M ( 'Project' );
    $projectlist = array();
		$projectlist = $project->order('title ' )->select ();

		$data = $project->where ( "id='" . $id . "'" )->find ();
		if ($data === false) {
			$errno = 4000; // mysql error
			$errmsg = "mysql find project by id error";
			$ret = $errEvent->setErrorEvent ( $errno, $errmsg );
			exit ( json_encode ( $ret ) );
		}

		$owneralias = $data ['owneralias'];
		//add one attribute to authority control
		if($owneralias === session("currentalias"))
		{
			$isOwer = true;
		}
		$user = M ( 'User' );
		$owner = $user->where ( "alias='" . $owneralias . "'" )->find ();
		if ($owner === false) {
			$errno = 4000; // mysql error
			$errmsg = "mysql find owner in user error";
			$ret = $errEvent->setErrorEvent ( $errno, $errmsg );
			exit ( json_encode ( $ret ) );
		}

		$team = M ( 'Team' );
		$teamid = $team->where ( "projectid='" . $id . "'" )->getField ( 'id' );
		if ($teamid === false) {
			$errno = 4000; // mysql error
			$errmsg = "mysql get field in team error";
			$ret = $errEvent->setErrorEvent ( $errno, $errmsg );
			exit ( json_encode ( $ret ) );
		}
		$userAlias=session ( 'currentalias' );

    $love = M ('Favorite');
		$findExistedFavorite = $love->where("useralias='".$userAlias."' AND projectid=".$id)->find();
    $lovelist=array();
    $lovelist= $love->where( "projectid='".$id."'")->select();
    $user = M ( 'User' );
		$userlist = $user->select ();

    for($i = 0; $i < count ( $lovelist ); $i ++) {
			 foreach ( $userlist as $u ) {
			 	if ($lovelist [$i] ['useralias'] == $u ['alias']) {
				 	$lovelist [$i] ['username'] = $u ['name'];
				  $lovelist [$i] ['photolink'] = $u ['photolink'];
					$lovelist [$i] ['ldaplink'] = $u ['ldaplink'];
				}
			}
			//$postlist[$i]['content'] = htmlspecialchars($postlist[$i]['content']);
		}

    if($findExistedFavorite !== null)
		{
			$isFavorite=true;
		}
	  if($findExistedFavorite == null)
		{
			$isFavorite=false;
		}

		$projecttodisplay = new \stdClass ();
		$projecttodisplay->id = $id;
		$projecttodisplay->status = $data ['status'];
		$projecttodisplay->title = htmlspecialchars($data ['title']);
		$projecttodisplay->tags = htmlspecialchars($this->getTagsbyProjectId($id));
		$projecttodisplay->slogan = htmlspecialchars($data ['slogan']);
		$projecttodisplay->background = htmlspecialchars($data ['background']);
		$projecttodisplay->idea = htmlspecialchars($data ['idea']);
		$projecttodisplay->link = htmlspecialchars($data['link']);
		$projecttodisplay->benefit = htmlspecialchars($data ['benefit']);
		$projecttodisplay->attachedfilepath = $data ['attachedfilepath'];
		$projecttodisplay->originalfilename = $data ['originalfilename'];
		$projecttodisplay->name = $owner ['name'];
		$projecttodisplay->photolink = $owner ['photolink'];
		$projecttodisplay->ldaplink = $owner ['ldaplink'];
		$projecttodisplay->email = $owner ['email'];
		$projecttodisplay->teamid = $teamid;
		$projecttodisplay->screenshotimage = $data['imagesavepath'];
		$projecttodisplay->screenshotvideo = $data['videosavepath'];
		$projecttodisplay->isFavorite = $isFavorite;

		$projecttodisplay->maturityscore = $data ['maturityscore'];
		$projecttodisplay->innovationscore = $data ['innovationscore'];
		$projecttodisplay->businessscore = $data ['businessscore'];

		$this->assign ( 'projecttodisplay', $projecttodisplay );

		$attachedfiles = M( 'Attachedfiles');
		$attachedfilelist = array();
		$attachedfilelist = $attachedfiles->where( "projectid='".$id."'")->select();

    $existfilecount = count($attachedfilelist);
		if ($attachedfilelist === false) {
			$errno = 4000; // mysql error
			$errmsg = "mysql select attached files list error";
			$ret = $errEvent->setErrorEvent ( $errno, $errmsg );
			exit ( json_encode ( $ret ) );
		}

		$userteamrelation = M ( 'Teamuserrelationship' );
		$teammemberaliaslist = $userteamrelation->where ( "teamid='" . $teamid . "'" )->field ( 'useralias' )->select ();
		if ($teammemberaliaslist === false) {
			$errno = 4000; // mysql error
			$errmsg = "mysql select teammember alias list error";
			$ret = $errEvent->setErrorEvent ( $errno, $errmsg );
			exit ( json_encode ( $ret ) );
		}

		// echo "alias: <br />";
		// var_dump($teammemberaliaslist);die;

		$user = M ( 'User' );
		$userlist = $user->select ();
		if ($userlist === false) {
			$errno = 4000; // mysql error
			$errmsg = "mysql select user list error";
			$ret = $errEvent->setErrorEvent ( $errno, $errmsg );
			exit ( json_encode ( $ret ) );
		}

		$memberlist = array ();
		foreach ( $teammemberaliaslist as $member ) {
			foreach ( $userlist as $info ) {
				if (strcmp ( $info ['alias'], $member ['useralias'] ) === 0) {
					array_push ( $memberlist, $info );
					continue;
				}
			}
		}
		//get statitics
		$post = M ('Post');
		$postList = $post->where ( "projectid='" . $id . "'" )->select();
		$postCount = count($postList);

		$adminpost = M ('Adminpost');
		$adminpostList = $adminpost->where ( "projectid='" . $id . "'" )->select();
		$adminpostCount = count($adminpostList);

		$favorit = M('Favorite');
		$favoriteList = $favorit->where( "projectid='" . $id . "'")->select();
		$favoritCount = count($favoriteList);

		$memberCount = count($memberlist);

    $link=M('Relevantlink');
    $linklist =  $link->where ( "projectid='" . $id . "'" )->select();

    for($i = 0; $i < count ( $linklist ); $i ++) {
		  foreach ( $projectlist as $po ) {
			  if ($linklist [$i] ['linkid'] == $po ['id']) {
					  $linklist [$i] ['linkedtitle'] = $po ['title'];
				  	$linklist [$i] ['linkedstatus'] = $po ['status'];
				  	$linklist [$i] ['linkedowneralias'] = $po ['owneralias'];
				}
			}
			//$postlist[$i]['content'] = htmlspecialchars($postlist[$i]['content']);
		}

    for($i = 0; $i < count ( $linklist ); $i ++) {
		  foreach ( $userlist as $u ) {
				if ($linklist [$i] ['linkedowneralias'] == $u ['alias']) {
				  	$linklist [$i] ['linkedowner'] = $u ['name'];
				}
			}
			//$postlist[$i]['content'] = htmlspecialchars($postlist[$i]['content']);
		}

		$advisortag = M ( 'Advisortag' );
		$advisortaglist = $advisortag->select();

		$tag=M('Adprojecttagmapping');
		$taglist = $tag->where( "projectid='" . $id . "'")->select();

    // $tagList = $tagLists->where( "projectid='" . $id . "'")->select();

		$this->assign('advisortaglist',$advisortaglist);
    $this->assign('taglist',$taglist);
		session_start();//根据当前SESSION生成随机数
		$dashboardcode = mt_rand(0,1000000);
		$_SESSION['dashboardcode'] = $dashboardcode;

    $this->assign('attachedfilelist',$attachedfilelist);

		$this->assign('dashboardcode', $dashboardcode);
		$this->assign('postcount', $postCount);
		$this->assign('adminpostcount', $adminpostCount);
		$this->assign('favoritecount',$favoritCount);
		$this->assign('membercount', $memberCount);
		$this->assign ( 'linklist', $linklist );
		$this->assign ( 'memberlist', $memberlist );
		$this->assign ( 'projectlist', $projectlist );
    $this->assign ( 'existfilecount', $existfilecount );
    $this->assign ( 'lovelist', $lovelist );
	  $this->assign( 'isowner', $isOwer);
		$this->assign( 'isAdmin', session('isAdmin'));
		$this->assign( 'isAdvisor', session('isAdvisor'));
		$this->display ( 'dashboardpage' );
	}

		/**
	 * 系统邮件发送函数
	 * @param string $to    接收邮件者邮箱
	 * @param string $name  接收邮件者名称
	 * @param string $subject 邮件主题
	 * @param string $body    邮件内容
	 * @param string $attachment 附件列表
	 * @return boolean
	 */
  public function editsendmail($projectId=-1)
  {
	  $errEvent = new ErrorEvent ();
	  $ret = $errEvent->setErrorEvent ();

	  $project = M ( 'Project' );
	  $owneralias = $project->where ( "id='" . $projectId . "'" )->getField ( 'owneralias' );
	  $title = $project->where ( "id='" . $projectId . "'" )->getField ( 'title' );

	  $team = M ( 'Team' );
	  $teamid = $team->where ( "projectid='" . $projectId . "'" )->getField ( 'id' );
	  if ($teamid === false) {
		  $errno = 4000; // mysql error
		  $errmsg = "mysql get field in team error";
		  $ret = $errEvent->setErrorEvent ( $errno, $errmsg );
		  exit ( json_encode ( $ret ) );
	  };

	  $userteamrelation = M ( 'Teamuserrelationship' );
	  $teammemberaliaslist = $userteamrelation->where ( "teamid='" . $teamid . "'" )->field ( 'useralias' )->select ();
	  if ($teammemberaliaslist === false) {
	    $errno = 4000; // mysql error
		  $errmsg = "mysql select teammember alias list error";
		  $ret = $errEvent->setErrorEvent ( $errno, $errmsg );
		  exit ( json_encode ( $ret ) );
	  };

	  $user = M ( 'User' );
	  $userlist = $user->select ();
		$ownername = $user->where( "alias ='".$owneralias."'" )->getField('name');
	  if ($userlist === false) {
   		$errno = 4000; // mysql error
		  $errmsg = "mysql select user list error";
		  $ret = $errEvent->setErrorEvent ( $errno, $errmsg );
		  exit ( json_encode ( $ret ) );
	  }

	  $membermaillist = array ();
	  foreach ( $teammemberaliaslist as $member ) {
		  foreach ( $userlist as $info ) {
			  if (strcmp ( $info ['alias'], $member ['useralias'] ) === 0) {
				  $membermaillist[$info['email']] = $info['name'];
				  continue;
			  }
		  }
	  };

		$advisor = M ('Advisorgroup');
		$advisorlist = $advisor->select();
		if ($advisorlist === false)
		{
			$errno = 4000; // mysql error
			$errmsg = "mysql select advisor list error";
			$ret = $errEvent->setErrorEvent ( $errno, $errmsg );
			exit ( json_encode ( $ret ) );
		}

		$advisormaillist = array();
		foreach ($advisorlist as $advisor){
			$advisormaillist[$advisor['email']] = $advisor['name'];
			continue;
		}

		$toList = $advisormaillist;
		$ccist = $membermaillist;
		$subject = 'Innovation Project Update Notification';
		$body = 'Dear all,<br/><br/>'.
						'&nbsp&nbsp Innovation project <b>'.$title.'</b> by <b>'.$ownername.'</b> has updates.<br>'.
						'&nbsp&nbsp Check it from: https://iaminnovator.bgc.ems.slb.com &nbsp<br/>'.
						'&nbsp&nbsp After you log in, you can type its id:<b>'.$projectId.'</b> into the search bar of the homepage to find it. <br/><br/>'.
						'&nbsp&nbsp This mail is sent automatically, <b><span style="color:red;">please DON\'T REPLY!</span></b><br/><br/>'.
						'Best Regards,<br/>'.
						'BGC Innovation Incubation Team';
		//$attachment = null;
    vendor('PHPMailer.class#phpmailer'); //从PHPMailer目录导class.phpmailer.php类文件
		vendor('PHPMailer.class#smtp');
    $mail             = new \Vendor\PHPMailer(); //PHPMailer对象
    $mail->CharSet    = 'UTF-8'; //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
    $mail->IsSMTP();  // 设定使用SMTP服务
    $mail->SMTPDebug  = 0;                     // 关闭SMTP调试功能
                                               // 1 = errors and messages
                                               // 2 = messages only
    $mail->SMTPAuth   = false;                  // 启用 SMTP 验证功能
    $mail->SMTPSecure = '';                 // 使用安全协议
    $mail->Host       = 'smtp.mail.slb.com';       // SMTP 服务器
    $mail->Port       = '25';  // SMTP服务器的端口号
    //$mail->Username   = 'lma11@slb.com';  // SMTP服务器用户名
    //$mail->Password   = '';  // SMTP服务器密码
    $mail->SetFrom('InnovationIncubator@slb.com', 'Innovation Incubator');
    //$replyEmail       = $config['REPLY_EMAIL']?$config['REPLY_EMAIL']:$config['FROM_EMAIL'];
    //$replyName        = $config['REPLY_NAME']?$config['REPLY_NAME']:$config['FROM_NAME'];
    //$mail->AddReplyTo($replyEmail, $replyName);
    $mail->Subject    = $subject;
    $mail->MsgHTML($body);
	  foreach($toList as $to => $name)
		{
		  $mail->AddAddress($to, $name);
		};
		foreach($ccList as $to => $name)
		{
		 	$mail->AddCC($to, $name);
		};
    // if(is_array($attachment)){ // 添加附件
    //     foreach ($attachment as $file){
    //         is_file($file) && $mail->AddAttachment($file);
    //     }
    // }
		if ( $mail->Send() ){
			var_dump("Email sent sucessfully! ");
			var_dump("Please use the back button of browser to go back!");
			return true;
		}
		else{
			var_dump("Email sent failed! ");
			echo($mail->ErrorInfo);
			return $mail->ErrorInfo ;
		};
    $this->dashboard($projectId);
  }

	/**
	 * add member to team, if relationship already existed, then just return sucess
	 * -else add to teamuserelation table
	 *      -check if user existed in table, if existed then add directly, if not go ldap to check
	 * @access
	 * @param
	 * @return mixed:
	 */
	public function addmember($teamid = -1)
	{
		if(null === session ( 'currentalias' ))
		{
			$this->redirect('User/index');
		}
		if($teamid === -1)
		{
			$this->redirect('Project/dashboard');
		}
		if(!isset($_POST['projectId']))
		{
			$this->redirect('Project/dashboard');
		}
		$errEvent = new ErrorEvent ();
		$ret = $errEvent->setErrorEvent ();

		$memberalias = strtolower ( $_POST ['alias'] );
		$projectId = $_POST['projectId'];
		$teamuserrelationship = M ( "Teamuserrelationship" );
		$relationexisted = $teamuserrelationship->where ( "teamid='" . $teamid . "' AND useralias='" . $memberalias . "'" )->find ();
		if($relationexisted === false)
		{
			$errno = 4000; // mysql error
			$errmsg = "mysql find user in team error";
			$ret = $errEvent->setErrorEvent ( $errno, $errmsg );
			exit ( json_encode ( $ret ) );
		}
		if ($relationexisted !== null)
		{
			$ret['errno'] = 01;
			$ret['errmsg'] = $_POST ['alias']." already in the team";
			exit(json_encode($ret));
		}

		$user = M ( 'User' );
		$findResult = $user->where ( "alias='" . $memberalias . "'" )->find ();
		if ($findResult === false) {
			$errno = 4000; // mysql error
			$errmsg = "mysql find user error";
			$ret = $errEvent->setErrorEvent ( $errno, $errmsg );
			exit ( json_encode ( $ret ) );
		}
		if( $findResult == null)
		{
			$userEvent = new UserEvent();
			$addresult = $userEvent->adduser ( $memberalias );
			if ($addresult !== true) {
				exit(json_encode($addresult));
			}
		}

		$teamuserrelationship->teamid = $teamid;
		$teamuserrelationship->useralias = $memberalias;
		$addrelationresult = $teamuserrelationship->add ();
		if (!$addrelationresult)
		{
			$errno = 4000; // mysql error
			$errmsg = "mysql add team member error";
			$ret = $errEvent->setErrorEvent ( $errno, $errmsg );
			exit ( json_encode ( $ret ) );
		}

		$addedUser = $user->where ( "alias='" . $memberalias . "'" )->find ();
		$ret['name'] =	$addedUser['name'];
		$ret['photolink'] =  $addedUser['photolink'];

		$this->updateActiveTime($projectId);
		exit ( json_encode ( $ret ) );
	}

  public function addlink($projectid = -1)
  {
		if(null === session ( 'currentalias' ))
		{
			$this->redirect('User/index');
		}
		$errEvent = new ErrorEvent ();
		$ret = $errEvent->setErrorEvent ();

		$linkedId = $_POST['linkedId'];

    $link = D ( 'Relevantlink' );
		if($linkedId===$projectid){
      var_dump("you cannot add yourself");
		}
		else{
      $data = array ();
		  $data ['linkid'] = $_POST ['linkedId'];
	  	$data ['projectid'] = $projectid;

		  $result = $link->add ( $data );

      $link2 = D ( 'Relevantlink' );
	  	$data2 = array ();
	  	$data2 ['linkid'] = $projectid;
	  	$data2 ['projectid'] = $_POST ['linkedId'];
		  $result = $link2->add ( $data2 );
		}
		if (! $result)
			echo "Add post failed";
		//update active time
	    $this->dashboard($projectid);
	}

	public function addeditsummary($projectid = -1)
	{
		if(null === session ( 'currentalias' ))
		{
			$this->redirect('User/index');
		}
		if($projectid == -1)
		{
			$this->redirect('Project/dashboard');
		}
		$errEvent = new ErrorEvent ();
		$ret = $errEvent->setErrorEvent ();

        $editoralias=session ( 'currentalias' );



	    $editsummary = D ( 'Editsummary' );
		$data = array ();
		$data ['summary'] = $_POST ['content'];
		$now = time();
		$date = date('Y-m-d H:i:s', $now);
		$data ['time'] = $date;
		$data ['projectid'] = $projectid;

		$project = D ( 'Project' );
		$status = $project->where( "id =".$projectid )->getField('status');
		$data ['status'] = $status;
		$data ['alias'] = 'owner';

    session_start();
    if(isset($_POST['originator'])) {
    	if($_POST['originator'] == $_SESSION['dashboardcode'])
			{
        $result = $editsummary->add ( $data );
      }
    }
		if (! $result)
			echo "Add post failed";
		//update active time
		$this->updateActiveTime($projectid);
	  $this->dashboard($projectid);
	}

	public function addcommentsummary($projectid = -1)
	{
		if(null === session ( 'currentalias' ))
		{
			$this->redirect('User/index');
		}
		if($projectid == -1)
		{
			$this->redirect('Project/dashboard');
		}
		$errEvent = new ErrorEvent ();
		$ret = $errEvent->setErrorEvent ();

	  $editsummary = D ( 'Editsummary' );
		$data = array ();
		$data ['summary'] = $_POST ['content'];
		$now = time();
		$date = date('Y-m-d H:i:s', $now);
		$data ['time'] = $date;
		$data ['projectid'] = $projectid;
		$data ['alias'] = session ( 'currentalias' );

		$project = D ( 'Project' );
		$status = $project->where( "id =".$projectid )->getField('status');
		$data ['status'] = $status;

    session_start();
    if(isset($_POST['summarycode'])) {
    	if($_POST['summarycode'] == $_SESSION['summarycode'])
			{
        $result = $editsummary->add ( $data );
      }
    }
		if (! $result)
			echo "Add post failed";
		//update active time
		$this->updateActiveTime($projectid);
	  $this->advisorboard($projectid);
	}

	public function discussarea($projectid = -1)
	{
		// display title infomation
		if(null === session ( 'currentalias' ))
		{
			$this->redirect('User/index');
		}
		if($projectid === -1)
		{
			$this->redirect('Project/index');
		}
	    session_start();//根据当前SESSION生成随机数
	    $postcode = mt_rand(0,1000000);
	    $_SESSION['postcode'] = $postcode;

		$project = M ( 'Project' );
		$projectdiscussed = $project->where ( "id='" . $projectid . "'" )->find ();

		if (! $projectdiscussed) {
			echo 'no project ' . $projectid;
			return false;
		}
		$this->assign ( 'projectdiscussed', $projectdiscussed );

		// display post
		$post = M ( 'Post' );
		$postlist = $post->where ( "projectid='" . $projectid . "'" )->order(' time asc' )->select ();
		$user = M ( 'User' );
		$userlist = $user->select ();
		// username and photolink for ($i=0;$i<count($projectList);$i++)
		for($i = 0; $i < count ( $postlist ); $i ++) {
			foreach ( $userlist as $u ) {
				if ($postlist [$i] ['postuseralias'] == $u ['alias']) {
					$postlist [$i] ['username'] = $u ['name'];
					$postlist [$i] ['photolink'] = $u ['photolink'];
				}
			}
			$postlist[$i]['content'] = htmlspecialchars($postlist[$i]['content']);
		}
	  $currentUser = session('currentalias');
		$this->assign ( 'currentUser', $currentUser);
		$this->assign ( 'postlist', $postlist );
		$this->assign('postcode',$discusscode);
		$this->assign ( 'isAdmin', session('isAdmin'));
		$this->display ( 'discussarea' );
	}

	public function timelingarea($projectid = -1) {
		// display title infomation
		if(null === session ( 'currentalias' ))
		{
			$this->redirect('User/index');
		}
		if($projectid === -1)
		{
			$this->redirect('Project/index');
		}


		$project = M ( 'Project' );
		$projectdiscussed = $project->where ( "id='" . $projectid . "'" )->find ();

    $status=M('Statuschangetime');
    $statusdiscussed = $status->where ( "projectid='" . $projectid . "'" )->find ();

    $data = array ();
    $data['createtime']=$projectdiscussed['createtime'];
    $data['toeggtime']=$statusdiscussed['toeggtime'];
    $data['toeaglettime']=$statusdiscussed['toeaglettime'];
    $data['toeagletime']=$statusdiscussed['toeagletime'];


    $creat['Birthday']=date('M d, Y',strtotime($data['createtime']));
    if( $data['toeggtime']!=0){$creat['ToEgg']=date('M d, Y',strtotime($data['toeggtime']));};
    if( $data['toeaglettime']!=0){$creat['ToEaglet']=date('M d, Y',strtotime($data['toeaglettime']));};
    if( $data['toeagletime']!=0){$creat['ToEagle']=date('M d, Y',strtotime($data['toeagletime']));};

    $summary = M ( 'Editsummary' );
    $summaryList = $summary->where ( "projectid='" . $projectid . "'" )->order('time' )->select ();

		$user = M ( 'User' );
		$userlist = $user->select ();
		if ($userlist === false)
		{
			$errno = 4000; // mysql error
			$errmsg = "mysql select user list error";
			$ret = $errEvent->setErrorEvent ( $errno, $errmsg );
			exit ( json_encode ( $ret ) );
		}

		for($i = 0; $i < count ( $summaryList ); $i ++)
		{
			$summaryList[$i]['time']=date('M d, Y',strtotime($summaryList[$i]['time']));
			if ($summaryList [$i] ['status'] == 'Idea') {
				$summaryList [$i] ['des'] = 'Birthday';
			}
      if ($summaryList [$i] ['status'] == 'Egg') {
				$summaryList [$i] ['des'] = 'ToEgg';
			}
      if ($summaryList [$i] ['status'] == 'Eaglet') {
				$summaryList [$i] ['des'] = 'ToEaglet';
			}
      if ($summaryList [$i] ['status'] == 'Eagle') {
				$summaryList [$i] ['des'] = 'ToEagle';
			}

			foreach ( $userlist as $user )
			{
				if (strcmp ( $summaryList [$i] ['alias'], $user ['alias'] ) === 0)
				{
					$summaryList [$i] ['editorname'] = $user ['name'];
					$summaryList [$i] ['ldaplink'] = $user ['ldaplink'];
					$summaryList [$i] ['photolink'] = $user ['photolink'];
					break;
				}
        if ($summaryList [$i] ['alias'] == 'owner') {
					$summaryList [$i] ['editorname'] = 'owner';
			  }
			}
		}
		if (! $projectdiscussed) {
			echo 'no project ' . $projectid;
			return false;
		}
		$this->assign ( 'projectdiscussed', $projectdiscussed );
		$this->assign ( 'statusdiscussed', $statusdiscussed );
    $this->assign ( 'summaryList', $summaryList );
		// display post

	  $currentUser = session('currentalias');
		$this->assign ( 'currentUser', $currentUser);

		$this->assign ( 'createdate', $creat);

		$this->display ( 'timeline' );
	}

	public function advisorboard($projectid = -1)
	{
		// display title infomation
		if(null === session ( 'currentalias' ))
		{
			$this->redirect('User/index');
		}
		if($projectid === -1)
		{
			$this->redirect('Project/index');
		}
    session_start();//根据当前SESSION生成随机数
    $advisorboardcode = mt_rand(0,1000000);
    $_SESSION['advisorboardcode'] = $advisorboardcode;


 $adpostcode = mt_rand(0,1000000);
    $_SESSION['adpostcode'] = $adpostcode;
$summarycode = mt_rand(0,1000000);
    $_SESSION['summarycode'] = $summarycode;

		$project = M ( 'Project' );
		$projectdiscussed = $project->where ( "id='" . $projectid . "'" )->find ();

		if (! $projectdiscussed) {
			echo 'no project ' . $projectid;
			return false;
		}
		$this->assign ( 'projectdiscussed', $projectdiscussed );

		// display post
    $currentUser = session ( 'currentalias' );

    $admin_score = M ( 'Adminscore' );
    $personadminscore=$admin_score->where ( "projectid='" . $projectid . "' AND postadminalias='" . $currentUser . "'" )->find();
		$adminscorelist = $admin_score->where ( "projectid='" . $projectid . "'")->order(' time asc' )->select ();
		$user = M ( 'User' );
		$userlist = $user->select ();
		// username and photolink for ($i=0;$i<count($projectList);$i++)
		for($i = 0; $i < count ( $adminscorelist ); $i ++) {
			$SUM[$i]=$adminscorelist [$i] ['adminmaturityscore']+$adminscorelist [$i] ['admininnovationscore']+$adminscorelist [$i] ['adminbusinessscore'];
			//$postlist[$i]['content'] = htmlspecialchars($postlist[$i]['content']);
		}
    $scoresum=array_sum($SUM);
    $average=array_sum($SUM)/count ( $SUM );
    $average=(string)$average;

		$marknumb=count ( $SUM );
    $data = array ();
		$data ['adminaveragescore'] = $average;
		$data ['marknumb'] = $marknumb;
		$data ['admintotalscore'] =$scoresum;
		$saveResult = $project->where("id=".$projectid)->field('adminaveragescore,marknumb,admintotalscore')->save($data);

		for($i = 0; $i < count ( $adminscorelist ); $i ++) {
			foreach ( $userlist as $u ) {
				if ($adminscorelist [$i] ['postadminalias'] == $u ['alias']) {
					$adminscorelist [$i] ['username'] = $u ['name'];
					$adminscorelist [$i] ['photolink'] = $u ['photolink'];
				}
			}
			//$postlist[$i]['content'] = htmlspecialchars($postlist[$i]['content']);
		}

        $project1 = M ( 'Project' );
		$postaverage = $project1->where ( "id='" . $projectid . "'" )->getField("adminaveragescore");



		$post = M ( 'Adminpost' );
		$targetpost= $post->where ( "projectid='" . $projectid . "' AND postadminalias='" . $currentUser . "'" )->order(' time asc' )->select ();

		$postlist = $post->where ( "projectid='" . $projectid . "'" )->order(' time asc' )->select ();
		$user = M ( 'User' );
		$userlist = $user->select ();
		// username and photolink for ($i=0;$i<count($projectList);$i++)
		for($i = 0; $i < count ( $postlist ); $i ++) {
			foreach ( $userlist as $u ) {
				if ($postlist [$i] ['postadminalias'] == $u ['alias']) {
					$postlist [$i] ['username'] = $u ['name'];
					$postlist [$i] ['photolink'] = $u ['photolink'];
				}
			}
			$postlist[$i]['content'] = htmlspecialchars($postlist[$i]['content']);
		}

    $scoretodisplay = new \stdClass ();
		$scoretodisplay->id = $projectid;
		$scoretodisplay->maturity_score = $personadminscore ['adminmaturityscore'];
		$scoretodisplay->innovation_score = $personadminscore ['admininnovationscore'];
		$scoretodisplay->business_score = $personadminscore ['adminbusinessscore'];
    $scoretodisplay->aver_score =$average;
    $this->assign ( 'advisorboardcode', $advisorboardcode);
 $this->assign ( 'adpostcode', $adpostcode);
  $this->assign ( 'summarycode', $summarycode);
    
    $this->assign ( 'currentUser', $currentUser);
    $this->assign ( 'targetpost', $targetpost );
    $this->assign ( 'postaverage',  $postaverage );


		$this->assign ( 'scoretodisplay', $scoretodisplay );
    $this->assign ( 'adminscorelist', $adminscorelist );
		$this->assign ( 'postlist', $postlist );
		$this->assign ( 'isAdvisor', session('isAdvisor'));
		$this->assign ( 'isAdmin', session('isAdmin'));
		$this->display ( 'advisorboard' );
	}

	public function addpost($projectid = -1)
	{
		if(null === session ( 'currentalias' ))
		{
			$this->redirect('User/index');
		}
		if($projectid === -1)
		{
			$this->redirect('Project/discussarea');
		}
		$post = D ( 'Post' );
		$data = array ();
		$data ['content'] = $_POST ['content'];
		$data ['postuseralias'] = session ( 'currentalias' );
		$now = time();
		$date =  date('Y-m-d H:i:s', $now);
		$data ['time'] = $date;
		$data ['projectid'] = $projectid;

    session_start();
    if(isset($_POST['postcode'])) {
    	if($_POST['postcode'] == $_SESSION['postcode'])
			{
        $result = $post->add ( $data );
      }
    }
		if (! $result)
			//echo "Add post failed";
		//update active time
		$this->updateActiveTime($projectid);
	  $this->discussarea($projectid);
	}

	public function deletePost($postid = -1)
	{
		if(null === session ( 'currentalias' ))
		{
			$this->redirect('User/index');
		}
		if($postid === -1)
		{
			$this->redirect('Project/dashboard');
		}
		$errEvent = new ErrorEvent ();
		$ret = $errEvent->setErrorEvent ();

		$post = M ('Post');
		$projectId = $post->where("id=".$postid)->getField('projectid');
		$deleteResult = $post->where("id=".$postid)->delete();
		if($deleteResult === false)
		{
			$errNo = 4000;
			$errMsg = "MySQL delete post failed!";
			$ret = $errEvent->setErrorEvent($errNo,$errMsg);
			exit(json_encode($ret));
		};
		$this->discussarea($projectId);
	}

	public function editPost()
	{
		if(null === session ( 'currentalias' ))
		{
			$this->redirect('User/index');
		}
		if($postid === -1)
		{
			$this->redirect('Project/dashboard');
		}
		$errEvent = new ErrorEvent ();
		$ret = $errEvent->setErrorEvent ();

    $postid=$_POST ['postid'];

		$post = M ('Post');
		$data = array ();
		$projectId = $post->where("id=".$postid)->getField('projectid');
    $data['content']=$_POST ['postcontent'];
    $now = time();
		$date =  date('Y-m-d H:i:s', $now);
		$data ['updatetime'] = $date;
		//$this->advisorboard($projectId);
		$updateResult = $post->where("id=".$postid)->field('content,updatetime')->save($data);
		if($updateResult === false)
    {
      $errNo = 4000;
      $errMsg = "MySQL update project failed!";
      $ret = $errEvent->setErrorEvent($errNo,$errMsg);
      exit(json_encode($ret));
    };
		//update tag table
		//$this->addTagsToProject($tags, $projectId);
	  $this->discussarea($projectId);
	}

	public function addadminpost($projectid = -1)
	{
		if(null === session ( 'currentalias' ))
		{
			$this->redirect('User/index');
		}
		if($projectid === -1)
		{
			$this->redirect('Project/advisorboard');
		}
		$errEvent = new ErrorEvent ();
		$ret = $errEvent->setErrorEvent ();

		$post = D ( 'Adminpost' );
		$data = array ();
		$data ['content'] = $_POST ['content'];
		$data ['postadminalias'] = session ( 'currentalias' );
    //$data['adminmaturityscore']=$_POST ['adminmaturityscore'];
    //$data['admininnovationscore']=  $_POST ['admininnovationscore'];
    //$data['adminbusinessscore']=$_POST ['adminbusinessscore'];

    $admin_score = M ( 'Adminscore' );
		$adminscorelist = $admin_score->where ( "projectid='" . $projectid . "'" )->order(' time asc' )->select ();

		$now = time();
		$date =  date('Y-m-d H:i:s', $now);
		$data ['time'] = $date;
		$data ['projectid'] = $projectid;

    session_start();
    if(isset($_POST['adpostcode'])) {
	    if($_POST['adpostcode'] == $_SESSION['adpostcode'])
			{
	      $result = $post->add ( $data );
	    }
    }
    //$result = $post->add ( $data );
		if (! $result)
		//echo "Add post failed";

		$this->updateActiveTime($projectid);
    // $this->assign('code',$code);
	  $this->advisorboard($projectid);
	  // $this->redirect('Project/advisorboard');
	}

	public function deleteAdminPost($adminpostid = -1)
	{
		if(null === session ( 'currentalias' ))
		{
			$this->redirect('User/index');
		}
		if($adminpostid === -1)
		{
			$this->redirect('Project/dashboard');
		}
		$errEvent = new ErrorEvent ();
		$ret = $errEvent->setErrorEvent ();

		$adminpost = M ('Adminpost');
		$projectId = $adminpost->where("id=".$adminpostid)->getField('projectid');

		$deleteResult = $adminpost->where("id=".$adminpostid)->delete();
		if($deleteResult === false)
		{
			$errNo = 4000;
			$errMsg = "MySQL delete admin-post failed!";
			$ret = $errEvent->setErrorEvent($errNo,$errMsg);
			exit(json_encode($ret));
		};
		$this->advisorboard($projectId);
	}

	public function editAdminPost()
	{
		if(null === session ( 'currentalias' ))
		{
			$this->redirect('User/index');
		}
		if($adminpostid === -1)
		{
			$this->redirect('Project/dashboard');
		}
		$errEvent = new ErrorEvent ();
		$ret = $errEvent->setErrorEvent ();

 	  $adminpostid=$_POST ['adminpostid'];

		$adminpost = M ('Adminpost');
		$data = array ();
		$projectId = $adminpost->where("id=".$adminpostid)->getField('projectid');
    $data['content']=$_POST ['postcontent'];
    $now = time();
		$date =  date('Y-m-d H:i:s', $now);
		$data ['updatetime'] = $date;
		//$this->advisorboard($projectId);
		$updateResult = $adminpost->where("id=".$adminpostid)->field('content,updatetime')->save($data);
		if($updateResult === false)
    {
      $errNo = 4000;
      $errMsg = "MySQL update project failed!";
      $ret = $errEvent->setErrorEvent($errNo,$errMsg);
      exit(json_encode($ret));
    };
		//update tag table
		//$this->addTagsToProject($tags, $projectId);
	  $this->advisorboard($projectId);
	}

  public function addadminascore($projectid = -1)
	{
		if(null === session ( 'currentalias' ))
		{
			$this->redirect('User/index');
		}
		if($projectid === -1)
		{
			$this->redirect('Project/advisorboard');
		}

		$post = D ( 'Adminscore' );
		$data = array ();
		//$data ['content'] = $_POST ['content'];
		$data ['postadminalias'] = session ( 'currentalias' );
    $data['adminmaturityscore']=$_POST ['adminmaturityscore'];
    $data['admininnovationscore']=  $_POST ['admininnovationscore'];
    $data['adminbusinessscore']=$_POST ['adminbusinessscore'];

		$now = time();
		$date =  date('Y-m-d H:i:s', $now);
		$data ['time'] = $date;
		$data ['projectid'] = $projectid;
        $repeate=0;
      session_start();
    if(isset($_POST['originator'])) {
	    if($_POST['originator'] == $_SESSION['advisorboardcode'])
			{
	      $result = $post->add ( $data );
	      $repeate=1;
	    }
    }


            if(!$repeate)  echo "Please submit only once ";
		

		if( (! $result)&&( $repeat))
			echo " add score failed";

		$total = $_POST ['adminmaturityscore']+$_POST ['admininnovationscore']+$_POST ['adminbusinessscore'];

		$project = M ( 'Project' );
		$data1 = array ();
		$currentscore = $project->where("id=".$projectid)->getField('admintotalscore');
	  $data1['admintotalscore'] = $total + $currentscore ;

	  $admin_score = M ( 'Adminscore' );
		$adminscorelist = $admin_score->where ( "projectid='" . $projectid . "'")->order(' time asc' )->select ();
		$adminscoreaverage=$data1['admintotalscore'] /count($adminscorelist);
    $marknumb=count($adminscorelist);
    $data1['adminaveragescore']=$adminscoreaverage;
    $data1['marknumb']=$marknumb;

	  $now1 = time();
	  $date1 =  date('Y-m-d H:i:s', $now1);
	  $data1['lastactivetime'] = $date1;

		$saveResult = $project->where("id=".$projectid)->field('admintotalscore,lastactivetime,adminaveragescore,marknumb')->save($data1);
		if($saveResult === false)
		{
			$errno = 4000; //
			$errmsg = "MySQL add star fail";
			$ret = $errEvent->setErrorEvent ( $errno, $errmsg );
			exit( json_encode($ret ));
		};
		//update active time
		$this->updateActiveTime($projectid);
	  $this->advisorboard($projectid);
	}

  public function updateadminascore($projectid = -1)
	{
		if(null === session ( 'currentalias' ))
		{
			$this->redirect('User/index');
		}
	  if($projectid === -1)
		{
			$this->redirect('Project/advisorboard');
		}
		$errEvent = new ErrorEvent ();
		$ret = $errEvent->setErrorEvent ();

    $test =  M ( 'Adminscore' );
    $data = array ();
    $currentUser = session ( 'currentalias' );
    //$data['postadminalias'] = session ( 'currentalias' );
    $data['adminmaturityscore']=$_POST ['update_ad_maturity_score'];
    $data['admininnovationscore']=  $_POST ['update_ad_innovation_score'];
    $data['adminbusinessscore']=$_POST ['update_ad_business_score'];

    $updateResult = $test->where( "projectid='" . $projectid . "' AND postadminalias='" . $currentUser . "'")->field('adminmaturityscore,admininnovationscore,adminbusinessscore')->save($data);

		$adminscorelist = $test->where ( "projectid='" . $projectid . "'")->order(' time asc' )->select ();
    for($i = 0; $i < count ( $adminscorelist ); $i ++) {
			$SUM[$i]=$adminscorelist [$i] ['adminmaturityscore']+$adminscorelist [$i] ['admininnovationscore']+$adminscorelist [$i] ['adminbusinessscore'];
			//$postlist[$i]['content'] = htmlspecialchars($postlist[$i]['content']);
		}
    $scoresum=array_sum($SUM);
    $average=array_sum($SUM)/count ( $SUM );
    $average=(string)$average;

    $project = M ( 'Project' );
		$data1 = array ();

    $marknumb=count($adminscorelist);
    $data1['adminaveragescore']= $average;
    $data1['marknumb']=count ( $SUM );

    //$personadminscore=$admin_score->where ( "projectid='" . $projectid . "' AND postadminalias='" . $currentUser . "'" )->find();
    $saveResult = $project->where("id=".$projectid)->field('adminaveragescore,marknumb')->save($data1);
		//update project table
	  if($updateResult === false)
    {
      	$errNo = 4000;
        $errMsg = "MySQL update project failed!";
        $ret = $errEvent->setErrorEvent($errNo,$errMsg);
        exit(json_encode($ret));
    };
		//update tag table
		//$this->addTagsToProject($tags, $projectId);
	  $this->advisorboard($projectid);
	}

  public function sendpostmail($projectId=-1)
	{
	  $errEvent = new ErrorEvent ();
	  $ret = $errEvent->setErrorEvent ();
	  $currentUser = session ( 'currentalias' );

	  $project = M ( 'Project' );
	  $owneralias = $project->where ( "id='" . $projectId . "'" )->getField ( 'owneralias' );
	  $title = $project->where ( "id='" . $projectId . "'" )->getField ( 'title' );

	  $team = M ( 'Team' );
	  $teamid = $team->where ( "projectid='" . $projectId . "'" )->getField ( 'id' );
	  if ($teamid === false) {
		  $errno = 4000; // mysql error
		  $errmsg = "mysql get field in team error";
		  $ret = $errEvent->setErrorEvent ( $errno, $errmsg );
		  exit ( json_encode ( $ret ) );
	  };

		$userteamrelation = M ( 'Teamuserrelationship' );
		$teammemberaliaslist = $userteamrelation->where ( "teamid='" . $teamid . "'" )->field ( 'useralias' )->select ();
		if ($teammemberaliaslist === false) {
		  $errno = 4000; // mysql error
		  $errmsg = "mysql select teammember alias list error";
		  $ret = $errEvent->setErrorEvent ( $errno, $errmsg );
		  exit ( json_encode ( $ret ) );
	  };

	  $user = M ( 'User' );
	  $userlist = $user->select ();
		$ownername = $user->where( "alias ='".$owneralias."'" )->getField('name');
	  if ($userlist === false) {
	  	$errno = 4000; // mysql error
		  $errmsg = "mysql select user list error";
		  $ret = $errEvent->setErrorEvent ( $errno, $errmsg );
		  exit ( json_encode ( $ret ) );
	  }

	  $membermaillist = array ();
		foreach ( $teammemberaliaslist as $member ) {
		  foreach ( $userlist as $info ) {
			  if (strcmp ( $info ['alias'], $member ['useralias'] ) === 0) {
				  $membermaillist[$info['email']] = $info['name'];
				  continue;
			  }
		  }
	  };

		$toList = $membermaillist;
		$subject = 'Innovation Project New Advisor Comment Notification';
		$body = 'Dear Innovators,<br/><br/>'.
						'&nbsp&nbsp Innovation project <b>'.$title.'</b> by <b>'.$ownername.'</b> has a new comment or score from advisor:<b>'.$currentUser.'</b>.'.'<br>'.
						'&nbsp&nbsp Please check it from: https://iaminnovator.bgc.ems.slb.com &nbsp<br/>'.
						'&nbsp&nbsp After you log in, you can type its id: <b>'.$projectId.'</b> into the search bar of the homepage to find it. <br/><br/>'.
						'&nbsp&nbsp This mail is sent automatically, <b><span style="color:red;">please DON\'T REPLY!</span></b><br/><br/>'.
						'Best Regards,<br/>'.
						'BGC Innovation Incubation Team';
		//$attachment = null;
	  vendor('PHPMailer.class#phpmailer'); //从PHPMailer目录导class.phpmailer.php类文件
	  vendor('PHPMailer.class#smtp');
	  $mail             = new \Vendor\PHPMailer(); //PHPMailer对象
	  $mail->CharSet    = 'UTF-8'; //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
	  $mail->IsSMTP();  // 设定使用SMTP服务
	  $mail->SMTPDebug  = 0;                     // 关闭SMTP调试功能
	                                             // 1 = errors and messages
	                                             // 2 = messages only
	  $mail->SMTPAuth   = false;                  // 启用 SMTP 验证功能
	  $mail->SMTPSecure = '';                 // 使用安全协议
	  $mail->Host       = 'smtp.mail.slb.com';       // SMTP 服务器
	  $mail->Port       = '25';  // SMTP服务器的端口号
	  // $mail->Username   = 'lma11@slb.com';  // SMTP服务器用户名
	  // $mail->Password   = '';  // SMTP服务器密码
	  $mail->SetFrom('InnovationIncubator@slb.com', 'Innovation Incubator');
	  //$replyEmail       = $config['REPLY_EMAIL']?$config['REPLY_EMAIL']:$config['FROM_EMAIL'];
	  //$replyName        = $config['REPLY_NAME']?$config['REPLY_NAME']:$config['FROM_NAME'];
	  //$mail->AddReplyTo($replyEmail, $replyName);
	  $mail->Subject    = $subject;
	  $mail->MsgHTML($body);
	  foreach($toList as $to => $name)
		{
		   $mail->AddAddress($to, $name);
		};
	  // if(is_array($attachment)){ // 添加附件
	  //     foreach ($attachment as $file){
	  //         is_file($file) && $mail->AddAttachment($file);
	  //     }
	  // }
		if ( $mail->Send() ){
			var_dump("Email sent sucessfully! ");
			var_dump("Please use the back button of browser to go back!");
			return true;
		}
		else{
			var_dump("Email sent failed! ");
			echo($mail->ErrorInfo);
			return $mail->ErrorInfo ;
		};
	}

	private function updateActiveTime($projectId)
	{
		$errEvent = new ErrorEvent ();
		$ret = $errEvent->setErrorEvent ();

		$now = time();
		$date =  date('Y-m-d H:i:s', $now);
		$data ['lastactivetime'] = $date;
		//update project table
		$project = M ('Project');
		$updateResult = $project->where("id=".$projectId)->field('lastactivetime')->save($data);
		if($updateResult === false)
		{
			$errno = 4000; // mysql error
			$errmsg = "mysql update project time error";
			$ret = $errEvent->setErrorEvent ( $errno, $errmsg );
			return $ret;
		}
		return true;
	}
	//edit status by admin
	public function editStatus()
	{
		if(!isset($_POST['projectId']) || !isset($_POST['status']))
		{
			$this->redirect('Project/index');
		}
		$errEvent = new ErrorEvent ();
		$ret = $errEvent->setErrorEvent ();

		$projectId = $_POST['projectId'];
		$status = $_POST['status'];

		$project = M ('Project');
		$currentstatus = $project->where("id=".$projectId)->getField('status');
		$owneralias = $project->where("id=".$projectId)->getField('owneralias');
		$title = $project->where("id=".$projectId)->getField('title');

		$data['status'] = $status;
	  $now = time();
	  $date =  date('Y-m-d H:i:s', $now);
	  $data ['lastactivetime'] = $date;
		$saveResult = $project->where("id=".$projectId)->field('status,lastactivetime')->save($data);

		$statustime = M ('Statuschangetime');
		if($status == "Egg")
		{
			$data ['toeggtime'] = $date;
			$data ['toeaglettime'] = "";
			$data ['toeagletime'] = "";
			$saveTime = $statustime->where("projectid=".$projectId)->field('toeggtime,toeaglettime,toeagletime')->save($data);
		}
		else if($status == "Eaglet")
		{
			$data ['toeaglettime'] = $date;
			$data ['toeagletime'] = "";
			$saveTime = $statustime->where("projectid=".$projectId)->field('toeaglettime,toeagletime')->save($data);
		}
		else if($status == "Eagle")
		{
			$data ['toeagletime'] = $date;
			$saveTime = $statustime->where("projectid=".$projectId)->field('toeagletime')->save($data);
		}
		else if($status == "Idea")
		{
			$data ['toeggtime'] = "";
			$data ['toeaglettime'] = "";
			$data ['toeagletime'] = "";
			$saveTime = $statustime->where("projectid=".$projectId)->field('toeggtime,toeaglettime,toeagletime')->save($data);
		}
    // sending email to notify team members
    //$user=M('User');
    //$owner = $user->where ( "alias='" . $owneralias . "'" )->find ();
		$team = M ( 'Team' );
	  $teamid = $team->where ( "projectid='" . $projectId . "'" )->getField ( 'id' );
	  if ($teamid === false) {
		  $errno = 4000; // mysql error
		  $errmsg = "mysql get field in team error";
		  $ret = $errEvent->setErrorEvent ( $errno, $errmsg );
		  exit ( json_encode ( $ret ) );
	  };

	  $userteamrelation = M ( 'Teamuserrelationship' );
	  $teammemberaliaslist = $userteamrelation->where ( "teamid='" . $teamid . "'" )->field ( 'useralias' )->select ();
	  if ($teammemberaliaslist === false) {
	    $errno = 4000; // mysql error
		  $errmsg = "mysql select teammember alias list error";
		  $ret = $errEvent->setErrorEvent ( $errno, $errmsg );
		  exit ( json_encode ( $ret ) );
	  };

	  $user = M ( 'User' );
	  $userlist = $user->select ();
		$ownername = $user->where( "alias ='".$owneralias."'" )->getField('name');
	  if ($userlist === false) {
   		$errno = 4000; // mysql error
		  $errmsg = "mysql select user list error";
		  $ret = $errEvent->setErrorEvent ( $errno, $errmsg );
		  exit ( json_encode ( $ret ) );
	  }

	  $membermaillist = array ();
	  foreach ( $teammemberaliaslist as $member ) {
		  foreach ( $userlist as $info ) {
			  if (strcmp ( $info ['alias'], $member ['useralias'] ) === 0) {
				  $membermaillist[$info['email']] = $info['name'];
				  continue;
			  }
		  }
	  };

		$advisor = M ('Advisorgroup');
		$advisorlist = $advisor->select();
		if ($advisorlist === false)
		{
			$errno = 4000; // mysql error
			$errmsg = "mysql select advisor list error";
			$ret = $errEvent->setErrorEvent ( $errno, $errmsg );
			exit ( json_encode ( $ret ) );
		}

		$advisormaillist = array();
    foreach ($advisorlist as $advisor){
			$advisormaillist[$advisor['email']] = $advisor['name'];
			continue;
		}

		$toList = $advisormaillist;
		$ccList = $membermaillist;
		$subject ='Innovation Project Status Changed Notification';
		$body = 'Dear all,<br/><br/>'.
						'&nbsp&nbsp Innovation project  <b>'.$title.'</b>  by  <b>'.$ownername.'</b>  has changed to  <b>'.$status.'.</b><br/>'.
						'&nbsp&nbsp Check it from: https://iaminnovator.bgc.ems.slb.com &nbsp<br/>'.
						'&nbsp&nbsp After you log in, you can type its id:<b>'.$projectId.'</b> into the search bar of the homepage to find it. <br/><br/>'.
						'&nbsp&nbsp This mail is sent automatically, <b><span style="color:red;">please DON\'T REPLY!</span></b><br/><br/>'.
						'Best Regards,<br/>'.
						'BGC Innovation Incubation Team';
		//$attachment = null;
    vendor('PHPMailer.class#phpmailer'); //从PHPMailer目录导class.phpmailer.php类文件
		vendor('PHPMailer.class#smtp');
    $mail             = new \Vendor\PHPMailer(); //PHPMailer对象
    $mail->CharSet    = 'UTF-8'; //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
    $mail->IsSMTP();  // 设定使用SMTP服务
    $mail->SMTPDebug  = 0;                     // 关闭SMTP调试功能
                                               // 1 = errors and messages
                                               // 2 = messages only
    $mail->SMTPAuth   = false;                  // 启用 SMTP 验证功能
    $mail->SMTPSecure = '';                 // 使用安全协议
    $mail->Host       = 'smtp.mail.slb.com';       // SMTP 服务器
    $mail->Port       = '25';  // SMTP服务器的端口号
    //$mail->Username   = 'lma11@slb.com';  // SMTP服务器用户名
    //$mail->Password   = '';  // SMTP服务器密码
    $mail->SetFrom('InnovationIncubator@slb.com', 'Innovation Incubator');
    //$replyEmail       = $config['REPLY_EMAIL']?$config['REPLY_EMAIL']:$config['FROM_EMAIL'];
    //$replyName        = $config['REPLY_NAME']?$config['REPLY_NAME']:$config['FROM_NAME'];
    //$mail->AddReplyTo($replyEmail, $replyName);
    $mail->Subject    = $subject;
    $mail->MsgHTML($body);
    foreach($toList as $to => $name)
		{
		  $mail->AddAddress($to, $name);
		};
		foreach($ccList as $to => $name)
		{
		  $mail->AddCC($to, $name);
		};

		return $mail->Send() ? true : $mail->ErrorInfo;
	}

	//add star
	public function addFavorite()
	{
		if(!isset($_POST['projectId']) || !isset($_POST['userAlias']))
		{
			$this->redirect('Project/index');
		}
		$errEvent = new ErrorEvent ();
		$ret = $errEvent->setErrorEvent ();

		$projectId = $_POST['projectId'];
		$userAlias = $_POST['userAlias'];

		$favorite = M ('Favorite');
		$findExistedFavorite = $favorite->where("useralias='".$userAlias."' AND projectid=".$projectId)->find();

		if($findExistedFavorite === false)
		{
			$errno = 4000; // mysql error
			$errmsg = "mysql add favorite fail error";
			$ret = $errEvent->setErrorEvent ( $errno, $errmsg );
			exit( json_encode($ret ) );
		}

		if($findExistedFavorite !== null)
		{
			$errno = 0001;
			$errmsg = "Favorite already added";
			$ret = $errEvent->setErrorEvent ( $errno, $errmsg );
			exit( json_encode($ret ) );
		}

		$favorite->create();
		$favorite->useralias = $userAlias;
		$favorite->projectid = $projectId;

		$favorite->add();
		//add project favorite count
		$project = M ('Project');
		$count = $project->where("id=".$projectId)->getField('favoritecount');

	  $data['favoritecount'] = $count + 1;
	  $now = time();
	  $date =  date('Y-m-d H:i:s', $now);
	  $data ['lastactivetime'] = $date;

		$saveResult = $project->where("id=".$projectId)->field('favoritecount,lastactivetime')->save($data);
		if($saveResult === false)
		{
			$errno = 4000; //
			$errmsg = "MySQL add star fail";
			$ret = $errEvent->setErrorEvent ( $errno, $errmsg );
			exit( json_encode($ret ));
		}
		exit( json_encode($ret ) );
	}

	//remove star
	public function deleteFavorite()
	{
		if(!isset($_POST['projectId']) || !isset($_POST['userAlias']))
		{
			$this->redirect('Project/index');
		}

		$errEvent = new ErrorEvent ();
		$ret = $errEvent->setErrorEvent ();

		$projectId = $_POST['projectId'];
		$userAlias = $_POST['userAlias'];

		$favorite = M ('Favorite');
		$deleteFavorite = $favorite->where("useralias='".$userAlias."' AND projectid=".$projectId)->delete();

		//issue with mysql
		if($deleteFavorite === false)
		{
			$errno = 4000; // mysql error
			$errmsg = "mysql add favorite fail error";
			$ret = $errEvent->setErrorEvent ( $errno, $errmsg );
			exit( json_encode($ret ) );
		}

		//not existed in database
		if($deleteFavorite <= null)
		{
			$errno = 0001; //
			$errmsg = "Star not existed in database";
			$ret = $errEvent->setErrorEvent ( $errno, $errmsg );
			exit( json_encode($ret ));
		}

		//decrease project favorite count
		$project = M ('Project');
		$count = $project->where("id=".$projectId)->getField('favoritecount');
	  $data['favoritecount'] = $count - 1;
	  $now = time();
	  $date =  date('Y-m-d H:i:s', $now);
	  $data ['lastactivetime'] = $date;
		$saveResult = $project->where("id=".$projectId)->field('favoritecount,lastactivetime')->save($data);
    if($saveResult === false)
    {
    	$errno = 4000; //
      $errmsg = "MySQL delete star fail";
      $ret = $errEvent->setErrorEvent ( $errno, $errmsg );
      exit( json_encode($ret ));
    }
    exit( json_encode($ret));
	}

	// add tag to project update two tables
	private function addTagsToProject($tagsString="PHP;JavaScript", $projectId=126)
	{
		$tagArray = explode(";", trim($tagsString));
		foreach ($tagArray as $tagItem)
		{
			$this->addTagMapping($tagItem, $projectId);
		}
	}

	private function addTagMapping($newTagName='JQuery', $projectId=126)
	{
		$tagName = trim($newTagName);
		$tag = M ('Tag');
		$findTagResult = $tag->where("name='".$tagName."'")->find();
		//var_dump($findTagResult);
		if($findTagResult !== false && $findTagResult === null)
		{
			$this->addTag($tagName);
		}

		$project = M('Project');
		$findProject = $project->where("id=".$projectId)->find();
		if($findProject === null || $findProject === false)
			return false;

		$projectTagMapping = M('Projecttagmapping');
		//var_dump("tagname='".$tagName."' AND projectid =".$projectId);
		$findMappingResult = $projectTagMapping->where("tagname='".$tagName."' AND projectid =".$projectId)->find();
		//var_dump($findMappingResult);
		if($findMappingResult === null )
		{
			$newMapping['projectid'] = $projectId;
			$newMapping['tagname'] = $tagName;
			$projectTagMapping->create($newMapping);
			$projectTagMapping->add($newMapping);
		}
	}
    public function addnewAdvisorTagsToProject()
	{
		$tagsString=$_POST['tagName'];
		$projectId=$_POST['projectId'];
       
		
    if($tagsString=='0'){ return; }



		$tagArray = explode(",", trim($tagsString));
		foreach ($tagArray as $tagItem)
		{
			$this->addAdvisorTagMapping($tagItem, $projectId);
		}
	} 
  public function addAdvisorTagsToProject()
	{
		$tagsString=$_POST['tagName'];
		$projectId=$_POST['projectId'];
    $advisorProjectTagMapping = M('Adprojecttagmapping');
		$deletetag = $advisorProjectTagMapping->where(" projectid =".$projectId)->delete();

		if($deletepost === false)
		{
			$errNo = 4000;
			$errMsg = "MySQL delete-post failed!";
			$ret = $errEvent->setErrorEvent($errNo,$errMsg);
			exit(json_encode($ret));
		};
    if($tagsString=='0'){ return; }

		$tagArray = explode(",", trim($tagsString));
		foreach ($tagArray as $tagItem)
		{
			$this->addAdvisorTagMapping($tagItem, $projectId);
		}
	}

	private function addAdvisorTagMapping($tagName='JQuery', $projectId=126)
	{
		$project = M('Project');
		$findProject = $project->where("id=".$projectId)->find();
		if($findProject === null || $findProject === false)
			return false;
		$advisorProjectTagMapping = M('Adprojecttagmapping');
		//var_dump("tagname='".$tagName."' AND projectid =".$projectId);
		$findMappingResult = $advisorProjectTagMapping->where("tagname='".$tagName."' AND projectid =".$projectId)->find();
		//var_dump($findMappingResult);
		if($findMappingResult === null )
		{
			$newMapping['projectid'] = $projectId;
			$newMapping['tagname'] = $tagName;
			$advisorProjectTagMapping->create($newMapping);
			$advisorProjectTagMapping->add($newMapping);
		}
		 $this->dashboard($projectId);
	}

	private function addTag($tagName)
	{
		$tag = M ('Tag');
		$findTagResult = $tag->where("name='".$tagName."'")->find();
		if($findTagResult === null)
		{
			$data['name'] = $tagName;
			$tag->create($data);
			$tag->add($data);
		}
	}

	public function getTagsbyProjectId($projectId=126)
	{
		$projectTagMapping = M('Adprojecttagmapping');
		$projectTags = $projectTagMapping->where("projectid=".$projectId)->select();

		return $projectTags;
		//todo if no tags, return empty array;
	}

	public function getAllTags()
	{

	}

	public function deleteTagMapping()
	{    $projectId=$_POST['projectId'];


		$advisorProjectTagMapping = M('Adprojecttagmapping');
		$deletetag = $advisorProjectTagMapping->where(" projectid =".$projectId)->delete();

		if($deletepost === false)
		{
			$errNo = 4000;
			$errMsg = "MySQL delete-post failed!";
			$ret = $errEvent->setErrorEvent($errNo,$errMsg);
			exit(json_encode($ret));
		};
	}

	//TODO
	public function editTag()
	{

	}

	public function uploadImage($projectId=-1)
	{
		$errEvent = new ErrorEvent ();
		$ret = $errEvent->setErrorEvent ();

		$upload = new Upload();
		$upload->maxsize = 25165824 ; //字节的24M
		$upload->exts = array( 'jpg','JPG', 'jpeg','JPEG','gif','GIF','png','PNG','mov','MOV','mp4','MP4','avi','AVI') ;
		$upload->savePath = '/Image/';

		$info = $upload->upload();
		if(!$info){
			var_dump($upload->getError());
			$this->error($upload->getError());
		}
		else{
			foreach($info as $file){
				$string=strrev($file['name']);
				$array=explode('.', $string);
				$extension=strrev(strtolower($array[0]));
				if($extension=='jpg'||$extension=='jpeg'||$extension=='png'||$extension=='gif')
				{
					$data['imagesavepath'] = '/Uploads'.$file['savepath'].$file['savename'];
					$data['imageoriginalname'] = $file['name'];
				}
				else if($extension=='mov'||$extension=='mp4'||$extension=='avi')
				{
					$data['videosavepath'] = '/Uploads'.$file['savepath'].$file['savename'];
					$data['videooriginalname'] = $file['name'];
				}
				else
				{
					var_dump("Not suppport ".$extension);
				};
				break;
			}
		  $project = M('Project');
		  $project->where("id='".$projectId."'")->field('imagesavepath,imageoriginalname')->save($data);
		  $project->where("id='".$projectId."'")->field('videosavepath,videooriginalname')->save($data);

		  $updateTimeResult = $this->updateActiveTime($projectId);
		  if($updateTimeResult === true)
		    $this->dashboard($projectId);
		}
	}

  public function uploadFile($projectId=-1)
	{
		$errEvent = new ErrorEvent ();
		$ret = $errEvent->setErrorEvent ();

		$now = time();
		$date =  date('Y-m-d H:i:s', $now);
		if(isset($_FILES['attachfile'])){
			$upload = new Upload();
			$info = $this->uploadconfig($upload);
			$info = $upload->upload();
			if(!$info){
				var_dump($upload->getError());
				$this->error($upload->getError());
			}
			else{
				foreach($info as $file){
		      $attachedfiles = M ('Attachedfiles');
					$attachedfiles->create();
					$attachedfiles->projectid = $projectId;
					$attachedfiles->createtime = $date;
					$attachedfiles->originalfilename = $file['name'];
					$attachedfiles->attachedfilepath = '/Uploads/'.$file['savepath'].$file['savename'];



					  session_start();
    if(isset($_POST['originator'])) {
    	if($_POST['originator'] == $_SESSION['dashboardcode'])
			{
        $attachedfiles->add();
      }
    }
					
				}
			}
		};
		$project = M('Project');
		$updateTimeResult = $this->updateActiveTime($projectId);
		if($updateTimeResult === true)
		    $this->dashboard($projectId);
	}

	//obselete
	private function achievementexhibition($projectid = 59)
	{
		// get all the features and display it
		$project = M('Project');
		$projectDisplay = $project->where ( "id='" . $projectid . "'" )->find();
		$feature = M ( 'Feature' );
		$featurelist = $feature->where ( "projectid='" . $projectid . "'" )->select ();

		$this->assign ( 'projectDisplay', $projectDisplay );
		$this->assign ( 'featurelist', $featurelist );
		$this->display ( 'achievementexhibition' );
	}
	//obselete
	private function addfeature($projectid = 59)
	{
		$errEvent = new ErrorEvent ();
		$ret = $errEvent->setErrorEvent ();

		$feature = D ( 'Feature' );
		$data = array ();
		$data ['name'] = $_POST ['name'];
		$data ['description'] = $_POST ['description'];
		$data ['process'] = $_POST ['process'];
		$data ['projectid'] = $projectid;

		$result = $feature->add ( $data );
		if (! $result)
		{
			$errno = 4000; // mysql error
			$errmsg = "mysql add feature error";
			$ret = $errEvent->setErrorEvent ( $errno, $errmsg );
			exit ( json_encode ( $ret ) );

		};
		//update active time
		exit( json_encode($ret ) );
	}
	private function addvideo($projectid = 59)
	{
		// TODO size limitation
		//update active time
	}
	//obselete
	private function editfeature($featureid = 5)
	{
		$errEvent = new ErrorEvent ();
		$ret = $errEvent->setErrorEvent ();

		$feature = D ( 'Feature' );
		$editfeature = $feature->where ( "id='" . $featureid . "'" )->find ();

		if (! $editfeature)
		{
			$errno = 4000; // mysql error
			$errmsg = "mysql edit feature fail to save error";
			$ret = $errEvent->setErrorEvent ( $errno, $errmsg );
			exit ( json_encode ( $ret ) );
		};

		$editfeature ['name'] = $_POST ['name'];
		$editfeature ['description'] = $_POST ['description'];
		$editfeature ['process'] = $_POST ['process'];

		$feature->save ( $editfeature );

		//TODO:update active time
		exit( json_encode($ret ) );
	}

	//obselete
	private function deletefeature($featureid = 5)
	{
		$errEvent = new ErrorEvent ();
		$ret = $errEvent->setErrorEvent ();

		$feature = D ( 'Feature' );

		$deleteresult = $feature->delete ( $featureid);
		if (! $deleteresult || (deleteresult === 0))
		{
			$errno = 4000; // mysql error
			$errmsg = "mysql delete feature fail error";
			$ret = $errEvent->setErrorEvent ( $errno, $errmsg );
			exit ( json_encode ( $ret ) );
		};

		//TODO:update active time

		exit( json_encode($ret ) );
	}

	// obsolete, move to template side
	private function search()
	{
		$searchkey = array ();
		// $searchkey['title'] = array('like', '%dsa%');
		$searchkey ['title'] = array (
				'like',
				'%' . $_POST ['searchcontent'] . '%'
		);

		$this->index ( $searchkey );
	}

  // delete project
	public function deleteProject($projectId=-1)
	{
		if($projectId === -1)
		{
			$this->redirect('Project/dashboard');
		};
		$errEvent = new ErrorEvent ();
		$ret = $errEvent->setErrorEvent ();

		$project = M ('Project');
		$deleteproject = $project->where("id=".$projectId)->delete();
		if($deleteproject === false)
		{
			$errNo = 4000;
			$errMsg = "MySQL delete project failed!";
			$ret = $errEvent->setErrorEvent($errNo,$errMsg);
			exit(json_encode($ret));
		};

		$team = M ('Team');
		$teamid = $team->where("projectid=".$projectId)->getField('id');
		$deleteteam = $team->where("projectid=".$projectId)->delete();
		if($deleteteam === false)
		{
			$errNo = 4000;
			$errMsg = "MySQL delete team failed!";
			$ret = $errEvent->setErrorEvent($errNo,$errMsg);
			exit(json_encode($ret));
		};

		$teamuserrelationship = M ('Teamuserrelationship');
		$deleteturelation = $teamuserrelationship->where("teamid=".$teamid)->delete();
		if($deleteturelation === false)
		{
			$errNo = 4000;
			$errMsg = "MySQL delete team-user-relationship failed!";
			$ret = $errEvent->setErrorEvent($errNo,$errMsg);
			exit(json_encode($ret));
		};

		$statuschangetime = M('Statuschangetime');
		$deletestatuscht = $statuschangetime->where("projectid=".$projectId)->delete();
		if($deletestatuscht === false)
		{
			$errNo = 4000;
			$errMsg = "MySQL delete status-change-time failed!";
			$ret = $errEvent->setErrorEvent($errNo,$errMsg);
			exit(json_encode($ret));
		};

		$relevantlink = M ('Relevantlink');
		$deleterelevantlink = $relevantlink->where("projectid=".$projectId)->delete();
		$deleteprojectlinked = $relevantlink->where("linkid=".$projectId)->delete();
		if($deleterelevantlink === false)
		{
			$errNo = 4000;
			$errMsg = "MySQL delete relevant-link failed!";
			$ret = $errEvent->setErrorEvent($errNo,$errMsg);
			exit(json_encode($ret));
		};
		if($deleteprojectlinked === false)
		{
			$errNo = 4000;
			$errMsg = "MySQL delete project-linked failed!";
			$ret = $errEvent->setErrorEvent($errNo,$errMsg);
			exit(json_encode($ret));
		};

		$projecttagmapping = M ('Projecttagmapping');
		$deleteprojecttagmap = $projecttagmapping->where("projectid=".$projectId)->delete();
		if($deleteprojecttagmap === false)
		{
			$errNo = 4000;
			$errMsg = "MySQL delete project-tag-mapping failed!";
			$ret = $errEvent->setErrorEvent($errNo,$errMsg);
			exit(json_encode($ret));
		};

		$post = M('Post');
		$deletepost = $post->where("projectid=".$projectId)->delete();
		if($deletepost === false)
		{
			$errNo = 4000;
			$errMsg = "MySQL delete-post failed!";
			$ret = $errEvent->setErrorEvent($errNo,$errMsg);
			exit(json_encode($ret));
		};

		$adminpost = M('Adminpost');
		$deleteadminpost = $adminpost->where("projectid=".$projectId)->delete();
		if($deleteadminpost === false)
		{
			$errNo = 4000;
			$errMsg = "MySQL delete admin-post failed!";
			$ret = $errEvent->setErrorEvent($errNo,$errMsg);
			exit(json_encode($ret));
		};

		$adminscore = M('Adminscore');
		$deleteadminscore = $adminscore->where("projectid=".$projectId)->delete();
		if($deleteadminscore === false)
		{
			$errNo = 4000;
			$errMsg = "MySQL delete admin-score failed!";
			$ret = $errEvent->setErrorEvent($errNo,$errMsg);
			exit(json_encode($ret));
		};

		$favorite = M('Favorite');
		$deletefavorite = $favorite->where("projectid=".$projectId)->delete();
		if($deletefavorite === false)
		{
			$errNo = 4000;
			$errMsg = "MySQL delete favorite failed!";
			$ret = $errEvent->setErrorEvent($errNo,$errMsg);
			exit(json_encode($ret));
		};

		// $adminfavorite = M('Adminfavorite');
		// $deleteadminfavorite = $adminfavorite->where("projectid=".$projectId)->delete();
		// if($deleteadminfavorite === false)
		// {
		// 	$errNo = 4000;
		// 	$errMsg = "MySQL delete admin-favorite failed!";
		// 	$ret = $errEvent->setErrorEvent($errNo,$errMsg);
		// 	exit(json_encode($ret));
		// };

		$attachedfiles = M('Attachedfiles');
		$deleteattachedfiles = $attachedfiles->where("projectid=".$projectId)->delete();
	  if($deleteattachedfiles === false)
 		{
    	$errNo = 4000;
    	$errMsg = "MySQL delete attached-files failed!";
    	$ret = $errEvent->setErrorEvent($errNo,$errMsg);
     	exit(json_encode($ret));
	  };
		$this->redirect('Project/index');
		return;
	}

//End
}
?>
