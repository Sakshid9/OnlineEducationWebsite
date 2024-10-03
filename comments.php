<?php
include 'components/connect.php';

if (isset($_COOKIE['user_id'])) {
   $user_id = $_COOKIE['user_id'];
} else {
   header('location:home.php');
   exit;
}

if (isset($_POST['delete_comment'])) {
   $delete_id = filter_var($_POST['comment_id'], FILTER_SANITIZE_STRING);

   $verify_comment = $conn->prepare("SELECT * FROM `comments` WHERE id = ?");
   $verify_comment->execute([$delete_id]);

   if ($verify_comment->rowCount() > 0) {
      $delete_comment = $conn->prepare("DELETE FROM `comments` WHERE id = ?");
      $delete_comment->execute([$delete_id]);
      $message[] = 'Comment deleted successfully!';
   } else {
      $message[] = 'Comment already deleted or does not exist!';
   }
}

if (isset($_POST['update_now'])) {
   $update_id = filter_var($_POST['update_id'], FILTER_SANITIZE_STRING);
   $update_box = filter_var($_POST['update_box'], FILTER_SANITIZE_STRING);

   $verify_comment = $conn->prepare("SELECT * FROM `comments` WHERE id = ?");
   $verify_comment->execute([$update_id]);

   if ($verify_comment->rowCount() > 0) {
      $update_comment = $conn->prepare("UPDATE `comments` SET comment = ? WHERE id = ?");
      $update_comment->execute([$update_box, $update_id]);
      $message[] = 'Comment edited successfully!';
   } else {
      $message[] = 'Comment was not found!';
   }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>User Comments</title>

   <!-- Font Awesome CDN Link -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
   <!-- Custom CSS File Link -->
   <link rel="stylesheet" href="css/style.css">
</head>

<body>

   <?php include 'components/user_header.php'; ?>

   <?php
   // Display messages
   if (!empty($message)) {
      foreach ($message as $msg) {
         echo "<div class='message'>{$msg}</div>"; // Display message
      }
   }

   if (isset($_POST['edit_comment'])) {
      $edit_id = filter_var($_POST['comment_id'], FILTER_SANITIZE_STRING);
      $verify_comment = $conn->prepare("SELECT * FROM `comments` WHERE id = ? LIMIT 1");
      $verify_comment->execute([$edit_id]);

      if ($verify_comment->rowCount() > 0) {
         $fetch_edit_comment = $verify_comment->fetch(PDO::FETCH_ASSOC);
   ?>
         <section class="edit-comment">
            <h1 class="heading">Edit Comment</h1>
            <form action="" method="post">
               <input type="hidden" name="update_id" value="<?= $fetch_edit_comment['id']; ?>">
               <textarea name="update_box" class="box" maxlength="1000" required placeholder="Please enter your comment"
                  cols="30" rows="10"><?= $fetch_edit_comment['comment']; ?></textarea>
               <div class="flex">
                  <a href="comments.php" class="inline-option-btn">Cancel Edit</a>
                  <input type="submit" value="Update Now" name="update_now" class="inline-btn">
               </div>
            </form>
         </section>
   <?php
      } else {
         $message[] = 'Comment was not found!';
      }
   }
   ?>

   <section class="comments">
      <h1 class="heading">Your Comments</h1>
      <div class="show-comments">
         <?php
         $select_comments = $conn->prepare("SELECT * FROM `comments` WHERE user_id = ?");
         $select_comments->execute([$user_id]);

         if ($select_comments->rowCount() > 0) {
            while ($fetch_comment = $select_comments->fetch(PDO::FETCH_ASSOC)) {
               $select_content = $conn->prepare("SELECT * FROM `content` WHERE id = ?");
               $select_content->execute([$fetch_comment['content_id']]);
               $fetch_content = $select_content->fetch(PDO::FETCH_ASSOC);
         ?>
               <div class="box">
                  <div class="content">
                     <span><?= $fetch_comment['date']; ?></span>
                     <p> - <?= $fetch_content['title']; ?> - </p>
                     <a href="watch_video.php?get_id=<?= $fetch_content['id']; ?>">View Content</a>
                  </div>
                  <p class="text"><?= $fetch_comment['comment']; ?></p>
                  <?php if ($fetch_comment['user_id'] == $user_id) { ?>
                     <form action="" method="post" class="flex-btn">
                        <input type="hidden" name="comment_id" value="<?= $fetch_comment['id']; ?>">
                        <button type="submit" name="edit_comment" class="inline-option-btn">Edit Comment</button>
                        <button type="submit" name="delete_comment" class="inline-delete-btn"
                           onclick="return confirm('Delete this comment?');">Delete Comment</button>
                     </form>
                  <?php } ?>
               </div>
         <?php
            }
         } else {
            echo '<p class="empty">No comments added yet!</p>';
         }
         ?>
      </div>
   </section>

   <?php include 'components/footer.php'; ?>
   <script src="js/script.js"></script>
</body>

</html>