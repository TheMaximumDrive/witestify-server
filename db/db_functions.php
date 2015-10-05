<?php
header("Content-Type: text/html; charset=utf-8");

class db_functions {	
	private $conn;
 
    //put your code here
    // constructor
    function __construct() {		
		require_once 'db_connect.php';
		$db = db_connect::getInstance();
		$this->conn = $db->getConnection();
    }
 
    // destructor
    function __destruct() {
         
    }
 
    /**
     * Storing new user
     * returns user details
     */
    public function storeUser($name, $email, $password) {		
        $uuid = uniqid('', true);
        $hash = $this->hashSSHA($password);
        $encrypted_password = $hash["encrypted"]; // encrypted password
        $salt = $hash["salt"]; // salt
		$stmt = $this->conn->prepare("INSERT INTO users(unique_id, name, email, encrypted_password, salt, created_at) VALUES(?, ?, ?, ?, ?, NOW())");
		$stmt->bind_param("sssss", $uuid, $name, $email, $encrypted_password, $salt);
		$result = $stmt->execute();
        // check for successful store
        if ($result) {
			$stmt->close();
            // get user details 
            $uid = $this->conn->insert_id; // last inserted id
            $sql = "SELECT * FROM users WHERE uid = $uid";
			$result = $this->conn->query($sql);
            // return user details
            return $result->fetch_array(MYSQLI_ASSOC);
        } else {
            return false;
        }
    }
 
    /**
     * Get user by name and password
     */
    public function getUserByNameAndPassword($name, $password) {
		$sql = "SELECT * FROM users WHERE name = '$name'";
		$result = $this->conn->query($sql);
		
		if ($result->num_rows > 0) {
			$result = $result->fetch_assoc();
			$salt = $result['salt'];
			$encrypted_password = $result['encrypted_password'];
			$hash = $this->checkhashSSHA($salt, $password);
			// check for password equality
			if ($encrypted_password == $hash) {
				// user authentication details are correct
				return $result;
			}
        } else {
            // user not found
            return false;
        }
    }
	
	/**
	 * Change password of an user
	 * @param name, oldPassword, newPassword
	 * returns true if old password is correct and update is successful, otherwise false
	 */
	public function updateUserPassword($name, $oldPassword, $newPassword) {
		$sql = "SELECT * FROM users WHERE name = '$name'";
		$result = $this->conn->query($sql);
		
		if ($result->num_rows > 0) {
			$result = $result->fetch_assoc();
			$salt = $result['salt'];
			$encrypted_password = $result['encrypted_password'];
			$hash = $this->checkhashSSHA($salt, $oldPassword);
			// check for password equality
			if ($encrypted_password == $hash) {
				// user authentication details are correct
				$hash = $this->hashSSHA($newPassword);
				$encrypted_password = $hash["encrypted"]; // encrypted password
				$salt = $hash["salt"]; // salt
				
				$stmt = $this->conn->prepare("UPDATE users SET encrypted_password=?, salt=? WHERE name=?");
				$stmt->bind_param("sss", $encrypted_password, $salt, $name);
				$result = $stmt->execute();
				$stmt->close();
				// check for successful update
				if ($result) {
					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
        } else {
            // user not found
            return false;
        }
	}
	
	/**
	 * Load entries of an user
	 */
	public function loadEntries($name, $sortType) {
		if ($sortType == "SORT_BY_MOST_RECENT") {
			$sql = "SELECT * FROM videos WHERE user = '$name' ORDER BY timestamp DESC";
		} else if ($sortType == "SORT_BY_TITLE") {
			$sql = "SELECT * FROM videos WHERE user = '$name' ORDER BY title";
		} else if ($sortType == "SORT_BY_LONGEST") {
			$sql = "SELECT * FROM videos WHERE user = '$name' ORDER BY duration DESC";
		} else if ($sortType == "SORT_BY_SHORTEST") {
			$sql = "SELECT * FROM videos WHERE user = '$name' ORDER BY duration";
		}
		$result = $this->conn->query($sql);
		
		return $result;
    }
	
	/**
     * insert new video entry
     * returns true if insert is successful, otherwise false
     */
    public function addEntry($name, $title, $location, $timestamp, $duration, $keyframe, $url) {		
		// prepare and bind
		$stmt = $this->conn->prepare("INSERT INTO videos (user, title, location, timestamp, duration, keyframe, url) VALUES (?, ?, ?, ?, ?, ?, ?)");
		$stmt->bind_param("sssssss", $name, $title, $location, $timestamp, $duration, $keyframe, $url);
		$result = $stmt->execute();
		$stmt->close();
        // check for successful store
        if ($result) {
			return true;
        } else {
            return false;
        }
    }
	
	/**
	 * Update video title
	 * @param name, oldTitle, newTitle
	 * returns true, if update was successful, otherwise false
	 */
	public function updateVideoTitle($name, $oldTitle, $newTitle) {
		$stmt = $this->conn->prepare("UPDATE videos SET title=? WHERE user=? AND title=?");
		$stmt->bind_param("sss", $newTitle, $name, $oldTitle);
		$result = $stmt->execute();
		$stmt->close();
		// check for successful update
        if ($result) {
			return true;
        } else {
            return false;
        }
	}
 
    /**
     * Check user is existed or not
     */
    public function isUserExisting($name) {
        $sql = "SELECT name from users WHERE name = '$name'";
		$result = $this->conn->query($sql);
        if ($result->num_rows > 0) {
            // user already exists
            return true;
        } else {
            // user does not exists
            return false;
        }
    }
	
	/**
	 *
	 */
	public function isEmailExisting($email) {
		$sql = "SELECT email from users WHERE email = '$email'";
		$result = $this->conn->query($sql);
        if ($result->num_rows > 0) {
            // email already exists
            return true;
        } else {
            // email does not exist
            return false;
        }
	}
 
    /**
     * Encrypting password
     * @param password
     * returns salt and encrypted password
     */
    public function hashSSHA($password) {
 
        $salt = sha1(rand());
        $salt = substr($salt, 0, 10);
        $encrypted = base64_encode(sha1($password . $salt, true) . $salt);
        $hash = array("salt" => $salt, "encrypted" => $encrypted);
        return $hash;
    }
 
    /**
     * Decrypting password
     * @param salt, password
     * returns hash string
     */
    public function checkhashSSHA($salt, $password) {
 
        $hash = base64_encode(sha1($password . $salt, true) . $salt);
 
        return $hash;
    } 
}
 
?>