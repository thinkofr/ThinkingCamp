<?php
namespace Home\Event;

class  UserEvent{
	/**
	 * add user
	 * @access  
	 * @param 
	 * @return mixed: if user already existed in DB, or user not existed in DB but legal user return true; 
	 * if not, go to LDAP, search the user, if user existed in LDAP, add user ; if not existed in LDAP return 
	 * if normal login user, call this method won't appear not legal user,
	 * Not legal user only happened when add team member 
	 */
	public function adduser($alias) {
		$ret = array ();
		$ret ['errno'] = 0;
		$ret ['errmsg'] = "";
		$ret ['data'] = array ();
		
		$currentUser = D ( 'User' );
		$result = $currentUser->where ( "alias='" . $alias . "'" )->select ();
		if (count ( $result )) {
			return true;
		}
	
		$serverforsearch = "ldap.slb.com";
		$port = 389;
		$dn = "O=SLB,C=AN";
		$search = "alias=*" . $alias . "*";
	
		$dsforsearch = ldap_connect ( $serverforsearch, $port );
	
		if ($dsforsearch === false) {
			$ret ['errno'] = 2000; // 2000 Could not connect to LDAP server
			$ret ['errmsg'] = "Could not connect to LDAP server";
			//exit(json_encode($ret));
			return $ret;
		}
	
		$sr = ldap_search ( $dsforsearch, $dn, $search );
		$info = ldap_get_entries ( $dsforsearch, $sr );
	
		if ($info ['count'] >= 1) {
				
			foreach ( $info as $user ) {
				$cmpStr1 = strtolower ( $user ['alias'] [0] );
				$cmpStr2 = strtolower ( $alias );
				if (strcmp($cmpStr1, $cmpStr2) === 0) {
					$currentUser->create ();
					$currentUser->alias = strtolower ( $user ['alias'] [0] );
					$currentUser->email = $user ['mail'] [0];
					$currentUser->name = $user ['cn'] [0];
					$currentUser->photolink = "http://directory.slb.com/misc/pictures/" . $user ['employeenumber'] [0] . ".jpg";
					$currentUser->ldaplink = "http://directory.slb.com/query.cgi?alias=" . $user ['alias'] [0];
					$addResult = $currentUser->add ();
					if ($addResult !== false) {
						return true;
					}
					$ret ['errno'] = 4000; // 4000 mysql error
					$ret ['errmsg'] = "mysql add error";
					return $ret;
				}
			}
		}
	
		$ret['errno'] = 5000;
		$ret['errmsg'] = "alias not existed in LDAP";
		return $ret;
	}
	
	public function verifyIfUserinBGC($alias)
	{
		$ret = array ();
		$ret ['errno'] = 0;
		$ret ['errmsg'] = "";
		$ret ['data'] = array ();
		
		$serverforsearch = "ldap.slb.com";
		$port = 389;
		$dn = "O=SLB,C=AN";
		$search = "alias=*" . $alias . "*";
		
		$dsforsearch = ldap_connect ( $serverforsearch, $port );
		
		if ($dsforsearch === false) {
			$ret ['errno'] = 2000; // 2000 Could not connect to LDAP server
			$ret ['errmsg'] = "Could not connect to LDAP server";
			//exit(json_encode($ret));
			return $ret;
		}
		
		$sr = ldap_search ( $dsforsearch, $dn, $search );
		$info = ldap_get_entries ( $dsforsearch, $sr );
		
		if ($info ['count'] >= 1) {
			foreach ( $info as $user ) {
				$cmpStr1 = strtolower ( $user ['alias'] [0] );
				$cmpStr2 = strtolower ( $alias );
				if (strcmp($cmpStr1, $cmpStr2) === 0) {
					$bgcITSupportSite = strtolower("Beijing_10F Tower B - Smith_CN2021");
					$bddcITSupportSite = strtolower("Beijing_Chuangxin Building Tsinghua Science Park_CN0005");
					$userITSupportSite = strtolower($user ['slbitbuilding'][0]);
					if(strpos($userITSupportSite, $bgcITSupportSite) !== false
						|| strpos($userITSupportSite, $bddcITSupportSite) !== false)
					return true; 
				}
			}
		}
	}
	
	public function verifyIfUserinInSpecifiedList($alias)
	{
		$specifiedUser = M('specifieduser');
		$result = $specifiedUser->where("alias='" . $alias . "'" )->select ();
		if(count($result) > 0)
			return true;
		
		return false;
	}
	
	public function isinAdmin($alias)
	{
		$adminGroup = M ('admingroup');
		$result = $adminGroup->where("alias='" . $alias . "'" )->select ();
		
		if(count($result) > 0)
			return true;
		
		return false;	
	}
	public function isinAdvisor($alias)
	{
		$advisorGroup = M ('advisorgroup');
		$result = $advisorGroup->where("alias='" . $alias . "'" )->select ();

		if(count($result) > 0)
			return true;

		return false;
	} 
	
	public function isInGroup($userAlias)
	{
		$ret = array ();
		$ret ['errno'] = 0;
		$ret ['errmsg'] = "";
		$ret ['data'] = array ();
		
		$groupAlias = "Org_Oilfield_BGC_Innovation_Committee";
		
		$serverforsearch = "ldap.slb.com";
		$port = 389;
		$dn = "O=SLB,C=AN";
		
		
		$dsforsearch = ldap_connect ( $serverforsearch, $port );
		
		if ($dsforsearch === false) {
			$ret ['errno'] = 2000; // 2000 Could not connect to LDAP server
			$ret ['errmsg'] = "Could not connect to LDAP server";
			//exit(json_encode($ret));
			return $ret;
		}
		
		$searchUserCon = "alias=*" . $userAlias . "*";
		$srUser = ldap_search($dsforsearch, $dn, $searchUserCon);
		$userEntity = ldap_get_entries($dsforsearch, $srUser);
		if(count($userEntity) > 0)
		{
			$userDN = $userEntity[0]['dn'];
		}
		
		$searchGroupCon = "alias=*" . $groupAlias . "*";
		$srGroup = ldap_search ( $dsforsearch, $dn, $searchGroupCon);
		$groupEntity = ldap_get_entries( $dsforsearch, $srGroup );
		
		if(count($groupEntity) > 0)
		{
			$memeberList = $groupEntity[0]['uniquemember'];
			if(in_array($userDN, $memeberList, CASE_LOWER))
				return true;
		}
		
	    return false;
	}
}
?>