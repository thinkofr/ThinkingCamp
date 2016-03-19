<?php
namespace Home\Controller;

use Think\Controller;
use Home\Event\ErrorEvent;
use Home\Event\UserEvent;

//control and view is one to one mapping relationships
class UserController extends Controller {
	public function index() {
		$this->display ( 'login' );
		//test
		//$userEvent = new UserEvent();
		//var_dump($userEvent->isInGroup('YLiu60'));
	}

 
public function shownew() {
             $islogin="";
        if(null !== session ( 'currentalias' )){
          $islogin=true;

        }

         $this->assign ( 'islogin', $islogin );   
		$this->display ( 'whatsnew' );

	}	
  public function toLogin(){
		$this->display("login");
	}

	public function login()
	{
		session('[start]');
		$warning = "";

		$errEvent = new ErrorEvent();
		$ret = $errEvent->setErrorEvent();
		if (! isset ( $_POST ['submit'] )) {
			$this->display ( 'login' );
			exit();
		}

		$alias = $_POST ['username'];
		$password =  $_POST ['password'];

		$server = "ldap://dir.slb.com/";
		$port = 389;
		$port = 222;
		$dn = "O=SLB,C=AN";
		$suffix = "@slb.com";

		$ds = ldap_connect ( $server, $port );

		if ($ds === false) {
			$errno = 2000; // 2000 Could not connect to LDAP server
			$errmsg = "Could not connect to LDAP server";
			$ret = $errEvent->setErrorEvent($errno, $errmsg);
			$warning = $errmsg;
			$this->assign("warning", $warning);
			$this->display("login");
			exit();
		}

		$bind = ldap_bind ( $ds, $alias . $suffix, $password );

		if (! $bind) {
			$errno = 3000; // 3000 Alias or Password is Invalid.
			$errmsg = "Alias or Password is Invalid";
			$ret = $errEvent->setErrorEvent($errno, $errmsg);
			$warning = $errmsg;
			$this->assign("warning", $warning);
			$this->display("login");
			exit();
		}

		ldap_close ( $ds );
		$aliaslower = strtolower ( $alias );
		session ( 'currentalias', $aliaslower );

		$userEvent = new UserEvent();
		//verify if user is in BGC
		$isInBGC = $userEvent->verifyIfUserinBGC($aliaslower);
	  $isInSpecifiedList = $userEvent->verifyIfUserinInSpecifiedList($aliaslower);
		if($isInBGC !== true && $isInSpecifiedList === false)
		{
			$warning = "Only user in BGC organization is allowed to visit.";
			$this->assign("warning", $warning);
			$this->display("login");
			exit();
		}

		$result = $userEvent->adduser ( $aliaslower );
		if ($result !== true) {
			$warning = $errmsg;
			$this->assign("warning", $warning);
			$this->display("login");
			exit();
		}

		$currentUser = D ( 'User' );
		$currentUserName = $currentUser->where("alias='".$aliaslower."'")->getField('name');
		session('currentname', $currentUserName);

 		if($userEvent->isinAdmin($aliaslower)=== true)
 			session('isAdmin', 'true');
    //if ($userEvent->isInGroup($aliaslower) === true)
    //	session('isAdmin', 'true');
		else session('isAdmin', 'false');

		if($userEvent->isinAdvisor($aliaslower)=== true)
			session('isAdvisor', 'true');
	  else session('isAdvisor', 'false');

		session('isFirstIn',(int)1);
		$this->redirect( 'Project/index', array (), 0, "Redirecting" );
	}

	public function logout()
	{
		session(null);
		session('[destroy]');
		$this->display("login");
	}

	public function test()
	{
		// $userEvent = new UserEvent();
		$userEvent = A ( 'User', 'Event' );
		$userEvent->test ();
		// call other controller ,we can user A method
		// $userEvent = A('User', 'Event');
	}

	public function _empty()
	{
		echo 'can not find action ' . ACTION_NAME;
	}

//End
}
?>
