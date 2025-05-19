  <?php
  // Include required files
  include '../../includes/database.php';

  // Initialize variables
  $firstname = $lastname = $username = $role = "";
  $errors = array();
  $success_message = "";

  // Check if the form is submitted
  if (isset($_POST['register'])) {
      // Sanitize and validate form inputs
      $name = mysqli_real_escape_string($conn, $_POST['firstname'] ?? '');
      $name = mysqli_real_escape_string($conn, $_POST['lastname'] ?? '');
      $username = mysqli_real_escape_string($conn, $_POST['username'] ?? '');
      $password = mysqli_real_escape_string($conn, $_POST['password'] ?? '');
      $confirmPassword = mysqli_real_escape_string($conn, $_POST['confirmPassword'] ?? '');

      // Check if username already exists
      $stmt = mysqli_prepare($conn, "SELECT username FROM user WHERE username = ?");
      mysqli_stmt_bind_param($stmt, "s", $username); // Fixed parameter - was using $emailAddress
      mysqli_stmt_execute($stmt);
      $result = mysqli_stmt_get_result($stmt);
      
      if (mysqli_num_rows($result) > 0) {
          $errors[] = "Username already exists";
      }
      
      // Password validation
      if (strlen($password) < 8) {
          $errors[] = "Password must be at least 8 characters";
      }
      
      if ($password !== $confirmPassword) {
          $errors[] = "Passwords do not match";
      }
      
      // If no errors, proceed with registration
      if (empty($errors)) {
          // Hash password using password_hash
          $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
          
          $stmt = mysqli_prepare($conn, "INSERT INTO user (firstname, lastname, username, password) VALUES (?, ?, ?, ?)");
          mysqli_stmt_bind_param($stmt, "ssss", $firstname, $lastname, $username, $hashedPassword);
          $result = mysqli_stmt_execute($stmt);
          
          if ($result) {
              $success_message = "Registration completed successfully!";
              // Reset form fields after successful registration
              $name = $username = $role = "";
          } else {
              $errors[] = "Registration failed: " . mysqli_error($conn);
          }
      }
  }

  // Then your HTML form follows
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <link href="img/logo/leaf.png" rel="icon">
    <title>Account Settings</title>
    <link href="../../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../../vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="css/ruang-admin.min.css" rel="stylesheet">
  </head>
  <body id="page-top">
    <div id="wrapper">
      <!-- Sidebar -->
      <?php include "includes/sidebar.php";?>
      <!-- Sidebar -->
      <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
          <!-- TopBar -->
          <?php include "includes/topbar.php";?>
          <!-- Topbar -->
          <!-- Container Fluid-->
          <div class="container-fluid" id="container-wrapper">
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
              <h1 class="h3 mb-0 text-gray-800">Register Admin</h1>
              <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="./">Home</a></li>
                <li class="breadcrumb-item"><a href="addAdmin.php">Admin Management</a></li>
                <li class="breadcrumb-item active" aria-current="page">Register Admin</li>
              </ol>
            </div>

            <div class="row">
              <div class="col-lg-12">
                <!-- Form Basic -->
                <div class="card mb-4">
                  <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Register</h6>
                  </div>
                  <div class="card-body">
                    <!-- Display Success Message -->
                    <?php if (!empty($success_message)): ?>
                      <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                        </button>
                      </div>
                    <?php endif; ?>
                    
                    <!-- Display Error Messages -->
                    <?php if (!empty($errors)): ?>
                      <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0">
                          <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                          <?php endforeach; ?>
                        </ul>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                        </button>
                      </div>
                    <?php endif; ?>
                    
                    <form method="post">
                      <div class="form-group row">
                        <label for="firstname" class="col-sm-1 col-form-label">First Name:</label>
                        <div class="col-sm-4">
                          <input type="text" class="form-control" id="firstname" name="firstname" placeholder="firstname" value="<?php echo htmlspecialchars($firstname); ?>" required>
                        </div>

                      <div class="form-group row">
                        <label for="lastname" class="col-sm-1 col-form-label">Last Name:</label>
                        <div class="col-sm-4">
                          <input type="text" class="form-control" id="lastname" name="lastname" placeholder="lastname" value="<?php echo htmlspecialchars($lastname); ?>" required>
                        </div>

                        <label for="username" class="col-sm-1 col-form-label">Username:</label>
                        <div class="col-sm-4">
                          <input type="text" class="form-control" id="username" name="username" placeholder="Username" value="<?php echo htmlspecialchars($username); ?>" required>
                        </div>
                      </div>
                      
                      <div class="form-group row">
                        <label for="role" class="col-sm-1 col-form-label">Role:</label>
                        <div class="col-sm-4">
                          <select class="form-control" id="role" name="role" required>
                            <option value="">Select Role</option>
                            <option value="admin" <?php echo ($role == 'admin') ? 'selected' : ''; ?>>Admin</option>
                            <option value="chef" <?php echo ($role == 'chef') ? 'selected' : ''; ?>>Chef</option>
                          </select>
                        </div>
                      </div>

                      <div class="form-group row">
                        <label for="password" class="col-sm-1 col-form-label">Password:</label>
                        <div class="col-sm-4">
                          <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                          <small class="form-text text-muted">Password must be at least 8 characters</small>
                        </div>

                        <label for="confirmPassword" class="col-sm-1 col-form-label">Confirm Password:</label>
                        <div class="col-sm-4">
                          <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" placeholder="Confirm Password" required>
                        </div>
                      </div>

                      <div class="form-group row">
                        <div class="col-sm-10">
                          <a href="addAdmin.php" class="btn btn-danger">Cancel</a>
                          <button type="submit" name="register" class="btn btn-primary">Register</button>
                        </div>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!---Container Fluid-->
        </div>
        <!-- Footer -->
        <!-- Footer -->
      </div>
    </div>

    <!-- Scroll to top -->
    <a class="scroll-to-top rounded" href="#page-top">
      <i class="fas fa-angle-up"></i>
    </a>

    <!-- Bootstrap core JavaScript-->
    <script src="../../vendor/jquery/jquery.min.js"></script>
    <script src="../../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="../../vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/ruang-admin.min.js"></script>
  </body>
  </html>