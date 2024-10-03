<?php

include '../components/connect.php';

// Check if tutor_id cookie is set, otherwise redirect to login
if (isset($_COOKIE['tutor_id'])) {
   $tutor_id = $_COOKIE['tutor_id'];
} else {
   header('location:login.php');
   exit; // Ensure script stops executing after redirect
}

// Handle addition of new playlists
if (isset($_POST['add_playlist'])) {
   $title = filter_var($_POST['title'], FILTER_SANITIZE_STRING);
   $description = filter_var($_POST['description'], FILTER_SANITIZE_STRING);
   $thumb = $_FILES['thumb']['name'];
   $thumb_tmp = $_FILES['thumb']['tmp_name'];
   $thumb_folder = '../uploaded_files/' . $thumb;

   // Move the uploaded thumbnail to the designated folder
   if (move_uploaded_file($thumb_tmp, $thumb_folder)) {
      $insert_playlist = $conn->prepare("INSERT INTO `playlist` (tutor_id, title, description, thumb, date) VALUES (?, ?, ?, ?, NOW())");
      $insert_playlist->execute([$tutor_id, $title, $description, $thumb]);
      $message[] = 'Playlist added successfully!';
   } else {
      $message[] = 'Failed to upload thumbnail.';
   }
}

// Handle deletion of playlists
if (isset($_POST['delete'])) {
   $delete_id = filter_var($_POST['playlist_id'], FILTER_SANITIZE_STRING);

   // Verify if the playlist belongs to the current tutor
   $verify_playlist = $conn->prepare("SELECT * FROM `playlist` WHERE id = ? AND tutor_id = ? LIMIT 1");
   $verify_playlist->execute([$delete_id, $tutor_id]);

   if ($verify_playlist->rowCount() > 0) {
      // Fetch thumbnail for deletion
      $delete_playlist_thumb = $conn->prepare("SELECT * FROM `playlist` WHERE id = ? LIMIT 1");
      $delete_playlist_thumb->execute([$delete_id]);
      $fetch_thumb = $delete_playlist_thumb->fetch(PDO::FETCH_ASSOC);

      // Delete thumbnail from the server
      if ($fetch_thumb['thumb'] && file_exists('../uploaded_files/' . $fetch_thumb['thumb'])) {
         unlink('../uploaded_files/' . $fetch_thumb['thumb']);
      }

      // Delete related bookmarks and the playlist itself
      $delete_bookmark = $conn->prepare("DELETE FROM `bookmark` WHERE playlist_id = ?");
      $delete_bookmark->execute([$delete_id]);

      $delete_playlist = $conn->prepare("DELETE FROM `playlist` WHERE id = ?");
      $delete_playlist->execute([$delete_id]);

      $message[] = 'Playlist deleted!';
   } else {
      $message[] = 'Playlist already deleted or does not exist!';
   }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Playlists</title>

   <!-- Font Awesome CDN link -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">

   <!-- Custom CSS file link -->
   <link rel="stylesheet" href="../css/admin_style.css">
</head>

<body>

   <?php include '../components/admin_header.php'; ?>

   <section class="playlists">
      <h1 class="heading">Manage Playlists</h1>

      <div class="box-container">
         <div class="box" style="text-align: center;">
            <h3 class="title" style="margin-bottom: .5rem;">Create New Playlist</h3>
            <form action="" method="post" enctype="multipart/form-data">
               <input type="text" name="title" placeholder="Playlist Title" required>
               <textarea name="description" placeholder="Playlist Description" required></textarea>
               <input type="file" name="thumb" accept="image/*" required>
               <input type="submit" name="add_playlist" value="Add Playlist" class="btn">
            </form>
         </div>

         <?php
         // Select playlists for the current tutor
         $select_playlist = $conn->prepare("SELECT * FROM `playlist` WHERE tutor_id = ? ORDER BY date DESC");
         $select_playlist->execute([$tutor_id]);

         if ($select_playlist->rowCount() > 0) {
            while ($fetch_playlist = $select_playlist->fetch(PDO::FETCH_ASSOC)) {
               $playlist_id = $fetch_playlist['id'];
               $count_videos = $conn->prepare("SELECT * FROM `content` WHERE playlist_id = ?");
               $count_videos->execute([$playlist_id]);
               $total_videos = $count_videos->rowCount();
         ?>
               <div class="box">
                  <div class="flex">
                     <div>
                        <i class="fas fa-circle-dot"
                           style="<?= $fetch_playlist['status'] === 'active' ? 'color:limegreen' : 'color:red'; ?>"></i>
                        <span
                           style="<?= $fetch_playlist['status'] === 'active' ? 'color:limegreen' : 'color:red'; ?>"><?= $fetch_playlist['status']; ?></span>
                     </div>
                     <div>
                        <i class="fas fa-calendar"></i>
                        <span><?= $fetch_playlist['date']; ?></span>
                     </div>
                  </div>
                  <div class="thumb">
                     <span><?= $total_videos; ?></span>
                     <img src="../uploaded_files/<?= $fetch_playlist['thumb']; ?>" alt="">
                  </div>
                  <h3 class="title"><?= htmlspecialchars($fetch_playlist['title']); ?></h3>
                  <p class="description"><?= htmlspecialchars($fetch_playlist['description']); ?></p>
                  <form action="" method="post" class="flex-btn">
                     <input type="hidden" name="playlist_id" value="<?= htmlspecialchars($playlist_id); ?>">
                     <a href="update_playlist.php?get_id=<?= htmlspecialchars($playlist_id); ?>" class="option-btn">Update</a>
                     <input type="submit" value="Delete" class="delete-btn" onclick="return confirm('Delete this playlist?');"
                        name="delete">
                  </form>
                  <a href="view_playlist.php?get_id=<?= htmlspecialchars($playlist_id); ?>" class="btn">View Playlist</a>
               </div>
         <?php
            }
         } else {
            echo '<p class="empty">No playlists added yet!</p>';
         }
         ?>
      </div>
   </section>

   <?php include '../components/footer.php'; ?>

   <script src="../js/admin_script.js"></script>

   <script>
      document.querySelectorAll('.playlists .box-container .box .description').forEach(content => {
         if (content.innerHTML.length > 100) content.innerHTML = content.innerHTML.slice(0, 100) + '...';
      });
   </script>

</body>

</html>