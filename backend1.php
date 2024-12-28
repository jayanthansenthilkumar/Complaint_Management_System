<?php
include "db.php";

session_start(); // Ensure the session is started


//requirements approved
if (isset($_POST['approve_user'])) {
    $customer_id = mysqli_real_escape_string($conn, $_POST['user_id']);

    mysqli_begin_transaction($conn);

    // First query: Update the status in complaints_detail table
    $query = "UPDATE complaints_detail SET status='8' WHERE id='$customer_id'";
    $query_run = mysqli_query($conn, $query);

    // Second query: Delete from comments table
    $comment = "DELETE FROM comments WHERE problem_id = '$customer_id'";
    $comment_run = mysqli_query($conn, $comment);

    // Check if both queries ran successfully
    if ($query_run && $comment_run) {
        // Commit transaction if both succeeded
        mysqli_commit($conn);
        echo json_encode(['status' => 200]);
    } else {
        $res = [
            'status' => 500,
            'message' => 'Details Not Deleted'
        ];
        echo json_encode($res);
        return;
    }
}

//requirements rejected

if (isset($_POST['save_reason'])) {
    try {
        // Sanitize input values
        $reason = mysqli_real_escape_string($conn, $_POST['reason']);
        $customer_id = mysqli_real_escape_string($conn, $_POST['problem_id']);

        // Start the transaction
        mysqli_begin_transaction($conn);

        // First query: Update the status in complaints_detail table
        $query = "UPDATE complaints_detail SET status='19' WHERE id='$customer_id'";
        $query_run = mysqli_query($conn, $query);

        // Second query: Update the feedback column
        $comment = "UPDATE complaints_detail SET feedback='$reason' WHERE id='$customer_id'";
        $comment_run = mysqli_query($conn, $comment);

        // Third query: Delete from comments table
        $delete_query = "DELETE FROM comments WHERE problem_id='$customer_id'";
        $delete_run = mysqli_query($conn, $delete_query);

        // Check if all queries ran successfully
        if ($query_run && $comment_run && $delete_run) {
            // Commit transaction if all succeeded
            mysqli_commit($conn);
            echo json_encode(['status' => 200,]);
        } else {
            // Rollback if any query fails
            mysqli_rollback($conn);
            throw new Exception('Query Failed: ' . mysqli_error($conn));
        }
    } catch (Exception $e) {
        // Return error response in case of exception
        $res = [
            'status' => 500,
            'message' => 'Error: ' . $e->getMessage()
        ];
        echo json_encode($res);
    }
}

//comments query to give by user

if (isset($_POST['edit_user'])) {
    $customer_id = mysqli_real_escape_string($conn, $_POST['user_id']);

    $query = "SELECT * FROM manager WHERE task_id='$customer_id'";
    $query_run = mysqli_query($conn, $query);

    $User_data = mysqli_fetch_array($query_run);
    $query_date = $User_data['query_date'];
    $current_date = date('Y-m-d');

    // Calculate the difference in days between current date and query date
    $date_diff = (strtotime($current_date) - strtotime($query_date)) / (60 * 60 * 24);

    if($date_diff < 5 && !empty($User_data['comment_query'])){
        $readonly = true;
    }// Check if the reply is still empty and 5 days have passed
    else{
        $readonly = false; // Make it editable if conditions are met
    }

    if ($query_run) {
        $res = [
            'status' => 200,
            'message' => 'details Fetch Successfully by id',
            'data' => $User_data,
            'readonly' => $readonly,
            'date_diff' => $date_diff
        ];
        echo json_encode($res);
        return;
    } else {
        $res = [
            'status' => 500,
            'message' => 'Details Not Deleted'
        ];
        echo json_encode($res);
        return;
    }
}

//query save user
if (isset($_POST['save_edituser'])) {
    $customer_id = mysqli_real_escape_string($conn, $_POST['task_id']);
    $query = mysqli_real_escape_string($conn, $_POST['comment_query']);
    $reply = mysqli_real_escape_string($conn, $_POST['comment_reply']);

    $query = "UPDATE manager SET comment_query='$query',query_date=NOW() WHERE task_id='$customer_id'";
    $query_run = mysqli_query($conn, $query);

    if ($query_run) {
        $res = [
            'status' => 200,
            'message' => 'details Updated Successfully'
        ];
        echo json_encode($res);
        return;
    } else {
        $res = [
            'status' => 500,
            'message' => 'Details Not Deleted'
        ];
        echo json_encode($res);
        return;
    }
}

//get image
if (isset($_POST['get_image'])) {
    $user_id = $_POST['user_id'];

    // Query to fetch the image based on user ID
    $query = "SELECT id, images FROM complaints_detail WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode(['status' => 200, 'data' => $row]);
    } else {
        echo json_encode(['status' => 500, 'message' => 'Image not found']);
    }

    $stmt->close();
    $conn->close();
}

//after images
if (isset($_POST['after_image'])) {
    $user_id = $_POST['user_id'];

    // Query to fetch the image based on user ID
    $query = "SELECT id, after_photo FROM worker_taskdet WHERE task_id= ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode(['status' => 200, 'data' => $row]);
    } else {
        echo json_encode(['status' => 500, 'message' => 'Image not found']);
    }

    $stmt->close();
    $conn->close();
}


if(isset($_POST["add"])){
    $product = $_POST['prod_name'];
    $block = $_POST['block'];
    $venue = $_POST['venue'];
    $date = $_POST['date'];
    $qnty = $_POST['quantity'];
    $desc = $_POST['desc'];
    $submit_date = date('Y-m-d');

    $faculty_id = $_SESSION['faculty_id'];


    $query = "INSERT INTO products(name,block,venue,date,quantity,description,faculty_id,raised_date) VALUES('$product','$block','$venue','$date','$qnty','$desc', '$faculty_id','$submit_date') ";
    if(mysqli_query($conn,$query)){
        $res=[
            'status'=>200,
            'message'=>"Inserted Successfully"
        ];
        echo json_encode($res);
    }
}

if(isset($_POST['letterpad'])){

    $req_id = $_POST['user_id'];
    $query = "SELECT * FROM products WHERE id = '$req_id'";
    $query1 = "SELECT faculty_id from products WHERE id='$req_id'";
    $query_run1 = mysqli_query($conn, $query1);
    $User_data2= mysqli_fetch_array($query_run1);

    $name = $User_data2['faculty_id'];

    $query2 = "SELECT * FROM faculty WHERE faculty_id='$name'";
    $query_run2 = mysqli_query($conn, $query2);


    $query_run = mysqli_query($conn, $query);
    $User_data = mysqli_fetch_array($query_run);
    $User_data1 = mysqli_fetch_array($query_run2);


    if($query_run){
        $res=[
            'status'=>200,
            'message'=>"Data fetched Successfully",
            'data'=>$User_data,
            'data1'=>$User_data1,
        ];
        echo json_encode($res);
        return;
    }
}

if(isset($_POST['infra_approve'])){

    $req_id = $_POST['user_id'];
    $query = "UPDATE products SET status = 1 WHERE id = $req_id";

    $query_run = mysqli_query($conn, $query);
    if($query_run){
        $res=[
            'status'=>200,
            'message'=>"Data fetched Successfully"
   
        ];
        echo json_encode($res);
        return;
    }
}

if(isset($_POST['hod_approve'])){

    $req_id = $_POST['user'];
    $query = "UPDATE products SET status = 2 WHERE id = $req_id";

    $query_run = mysqli_query($conn, $query);
    if($query_run){
        $res=[
            'status'=>200,
            'message'=>"Data fetched Successfully"
   
        ];
        echo json_encode($res);
        return;
    }
}

if(isset($_POST['delete']))
{
    $id = $_POST['prod_id'];
    $query = "DELETE FROM products WHERE id='$id'";
    $run = mysqli_query($conn,$query);
    
        if($run){
            $res=[
                'status'=>200,
                'message'=>"Inserted Successfully"
            ];
            echo json_encode($res);
           
        }
    
            else{
                $res=[
                    "status"=>500,
                    "message"=>"Failed to delete"
                ];
                echo json_encode($res);
            
            }
}

if(isset($_POST['verify'])){
    $id = $_POST['id'];
    $query = "UPDATE products SET letterstatus = '1' WHERE id='$id'";
    $query_run = mysqli_query($conn,$query);
    if($query_run){
        $res=[
            "status"=>200,
            "message"=>"success"
        ];
        echo json_encode($res);
    }
}

if(isset($_POST['pverify'])){
    $id = $_POST['id'];
    $query = "UPDATE products SET letterstatus = '2' WHERE id='$id'";
    $query_run = mysqli_query($conn,$query);
    if($query_run){
        $res=[
            "status"=>200,
            "message"=>"success"
        ];
        echo json_encode($res);
    }
}


if (isset($_POST['facultydetails'])) {
    $fac_id = $_POST['fac_id'];
    $query1 = "SELECT * FROM facultys WHERE id='$fac_id'";

    $query_run1 = mysqli_query($conn,$query1);
    $fac_data = mysqli_fetch_array($query_run1);
    if ($query_run1) {
        $res = [
            'status' => 200,
            'message' => 'details Fetch Successfully by id',
            'data1'=>$fac_data,
        ];
        echo json_encode($res);
        return;
    } else {
        $res = [
            'status' => 500,
            'message' => 'Details Not Deleted'
        ];
        echo json_encode($res);
        return;
    }
}

?>