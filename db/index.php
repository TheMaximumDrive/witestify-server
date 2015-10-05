<?php
header("Content-Type: text/html; charset=utf-8");
 
/**
 * File to handle SIGN IN and SIGN UP requests
 * Accepts GET and POST
 * 
 * Each request will be identified by TAG
 * Response will be JSON data
 
  /**
 * check for POST request 
 */
if (isset($_POST['tag']) && $_POST['tag'] != '') {
    // get tag
    $tag = $_POST['tag'];
 
    // include db handler
    require_once 'db_functions.php';
    $db = new DB_Functions();
 
    // response Array
    $response = array("tag" => $tag, "error" => FALSE);
 
    // check for tag type
	if ($tag == 'ftp_config') {
		// Request type: get Ftp config data
		require_once 'ftp_config.php';
		$response["ftp"]["url"] = FTP_URL;
		$response["ftp"]["user"] = FTP_USER;
		$response["ftp"]["password"] = FTP_PASSWORD;
		echo json_encode($response);
	} else if ($tag == 'login') {
        // Request type: check sign in data
        $name = $_POST['name'];
        $password = $_POST['password'];
 
        // check for user
        $user = $db->getUserByNameAndPassword($name, $password);
        if ($user != false) {
            // user found
            $response["error"] = FALSE;
            $response["uid"] = $user["unique_id"];
            $response["user"]["name"] = $user["name"];
            $response["user"]["email"] = $user["email"];
            $response["user"]["created_at"] = $user["created_at"];
            $response["user"]["updated_at"] = $user["updated_at"];
            echo json_encode($response);
        } else {
            // user not found
            // echo json with error = 1
            $response["error"] = TRUE;
            $response["error_msg"] = "Incorrect username or password!";
            echo json_encode($response);
        }
    } else if ($tag == 'register') {
        // Request type: add new user to the database
        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = $_POST['password'];
 
        // check if user is already exists
        if ($db->isUserExisting($name)) {
            // user already exists - error response
            $response["error"] = TRUE;
            $response["error_msg"] = "Username is already taken";
            echo json_encode($response);
        } else if ($db->isEmailExisting($email)) {
			// email already exists - error response
			$response["error"] = TRUE;
            $response["error_msg"] = "E-Mail address already exists";
            echo json_encode($response);
		} else {
            // store user
            $user = $db->storeUser($name, $email, $password);
            if ($user) {
                // user stored successfully
                $response["error"] = FALSE;
                $response["uid"] = $user["unique_id"];
                $response["user"]["name"] = $user["name"];
                $response["user"]["email"] = $user["email"];
                $response["user"]["created_at"] = $user["created_at"];
                $response["user"]["updated_at"] = $user["updated_at"];
                echo json_encode($response);
            } else {
                // user failed to store
                $response["error"] = TRUE;
                $response["error_msg"] = "Error occurred during registration";
                echo json_encode($response);
            }
        }
    } else if ($tag == 'add_entry') {
		// Request type: add new video entry to the database
        $name = $_POST['name'];
		$title = $_POST['title'];
		$location = $_POST['location'];
		$timestamp = $_POST['timestamp'];
		$duration = $_POST['duration'];
        $keyframe = $_POST['keyframe'];
		$url = $_POST['url'];
		
		// check for user
        $entry = $db->addEntry($name, $title, $location, $timestamp, $duration, $keyframe, $url);
        if ($entry) {
			// entry successfully added
			$response["error"] = FALSE;
			echo json_encode($response);
		} else {
			$response["error"] = TRUE;
			echo json_encode($response);
		}
	} else if ($tag == 'load_entries') {
		// Request type: load video entries order by sort type
        $name = $_POST['name'];
		$sortType = $_POST['sortType'];
		
		// check for user
        $result = $db->loadEntries($name, $sortType);
		
		$response["entry"] = array();
		$response["entry"] = mysqli_fetch_all($result, MYSQLI_ASSOC);
		echo json_encode($response);
	} else if ($tag == 'update_entry') {
		// Request type: update video entry values
		$name = $_POST['name'];
		$oldTitle = $_POST['oldTitle'];
		$newTitle = $_POST['newTitle'];
		
		$entry = $db->updateVideoTitle($name, $oldTitle, $newTitle);
		if ($entry) {
			//entry successfully updated
			$response["error"] = FALSE;
			echo json_encode($response);
		} else {
			$response["error"] = TRUE;
			echo json_encode($response);
		}
	} else if ($tag == 'settings') {
		// Request type: change user password
		$name = $_POST['name'];
		$oldPassword = $_POST['oldPassword'];
		$newPassword = $_POST['newPassword'];
		
		$entry = $db->updateUserPassword($name, $oldPassword, $newPassword);
		if ($entry) {
			$response["msg"] = "Password has been changed";
			echo json_encode($response);
		} else {
			$response["msg"] = "Current password input is incorrect";
			echo json_encode($response);
		}
	} else {
        // Unknown request type
        $response["error"] = TRUE;
        $response["error_msg"] = "Unknown 'tag' value. It should be either 'login' or 'register'";
        echo json_encode($response);
    }
} else {
    $response["error"] = TRUE;
    $response["error_msg"] = "Required parameter 'tag' is missing!";
    echo json_encode($response);
}
?>