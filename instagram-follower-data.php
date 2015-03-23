<?php


set_time_limit(0); 							//script will run for an infinite amount of time
ini_set('default_socket_timeout', 300);		//server settings
session_start(); 							//starts new or resume existing session


/*-------- CONFIGURE THESE --- Instagram API KEYS --------*/	
define("clientID"       ,	 'your-client-id'); 			//associated your developer account with this program
define("clientSecret"   ,	 'your-client-secret'); 			//password
define("redirectURI"    ,	 'your-redirect-uri'  ); 		//after users choose whether to let you use access account or not (must match the one you registered)

  
//Connect with Instagram
function connectToInstagram($url){
	$ch = curl_init();						//used to transfer data with a url
	
	curl_setopt_array($ch, array(			//sets options for a curl transfer
		CURLOPT_URL => $url,				//the url
		CURLOPT_RETURNTRANSFER => true,		//return the results if successful
		CURLOPT_SSL_VERIFYPEER => false,	//we dont need to verify any certificates
		CURLOPT_SSL_VERIFYHOST => 2			//we wont verify host
	));

	$result = curl_exec($ch);				//executue the transfer
	curl_close($ch);						//close the curl session
	return $result;							//returns all the data we gathered
}


//Get user code and save info to session variables
if($_GET['code']){
	$code = $_GET['code'];
	$url = "https://api.instagram.com/oauth/access_token";
	$access_token_settings = array(
			'client_id'                =>     clientID,
			'client_secret'            =>     clientSecret,
			'grant_type'               =>     'authorization_code',
			'redirect_uri'             =>     redirectURI,
			'code'                     =>     $code
	);
	$curl = curl_init($url);    									//we need to transfer some data
	curl_setopt($curl,CURLOPT_POST,true);   						//using POST
	curl_setopt($curl,CURLOPT_POSTFIELDS,$access_token_settings);   //use these settings
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);   				//return results as string
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);   			//don't need to verify any certificates
	$result = curl_exec($curl);   									//go get the data!
	curl_close($curl);   											//close connection to free up your resources

	$results = json_decode($result,true);
	$userName = $results['user']['username'];
	$access_token = $results['access_token']; 
	$userID = $results['user']['id'];



	// getting all of a users followers
	$url = 'https://api.instagram.com/v1/users/'. $userID .'/followed-by?access_token='. $access_token;
	$instagramInfo = connectToInstagram($url);
	$followerResults = json_decode($instagramInfo, true);

	$followerSum = 0;
	$followersData = array();
	$followersData[] = array('username'/*,'bio'*/,'numPosts','numFollowers','numFollows','latitude','longitude');

	while($url){
		foreach($followerResults['data'] as $item){
			$id = $item['id'];
			$url = 'https://api.instagram.com/v1/users/'. $id .'/?access_token='. $access_token;
			$instagramInfo = connectToInstagram($url);
			$basicResults = json_decode($instagramInfo, true);
			$numFollowers_ind = $basicResults['data']['counts']['followed_by'];
			$user = $basicResults['data']['username'];
			// $bio = $basicResults['data']['bio'];
			$posts = $basicResults['data']['counts']['media'];
			$followies = $basicResults['data']['counts']['followed_by'];
			$follows = $basicResults['data']['counts']['follows'];
			
			
			$url = 'https://api.instagram.com/v1/users/'. $id .'/media/recent?client_id='. clientID .'&count=7';
			$instagramInfo = connectToInstagram($url);
			$imgResults = json_decode($instagramInfo, true);
			$lat = $imgResults['data'][0]['location']['latitude'];
			$long = $imgResults['data'][0]['location']['longitude'];


			$followersData[] = array($user,/*$bio,*/$posts,$followies,$follows, $lat, $long);
			$followerSum = $followerSum + $numFollowers_ind;
		}
		$url = $followerResults['pagination']['next_url'];
		$instagramInfo = connectToInstagram($url);
		$followerResults = json_decode($instagramInfo, true);
	}

	print_r($followersData);




	
}else{ ?>


<!doctype html>
<html lang="en">
<head> 
  <meta http-equiv="content-type" content="text/html; charset=UTF-8" /> 
  <title>Instagram Follower Data</title> 

</head> 
<body>
	<!-- When they click this, they will be prompted to Login to Instagram -->
	<a href="https://api.instagram.com/oauth/authorize/?client_id=<?php echo clientID; ?>&redirect_uri=<?php echo redirectURI; ?>&response_type=code">Login</a>
</body>
</html>

<?

}  


?>
