<?php

if (isset($_FILES['image']['name'])) {
			$response["error"] = FALSE;
			$response["file_path"] = "success";
			echo json_encode($response);
} else {
			$response["error"] = TRUE;
			echo json_encode($response);
}

?>