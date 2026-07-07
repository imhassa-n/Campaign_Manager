<?php

session_start();

if(!isset($_SESSION['user']))
{
    header("Location: login.php");
    exit;
}

include 'db.php';

$id = $_GET['id'];

$result = mysqli_query($conn,"
SELECT * FROM clients
WHERE id='$id'
");

$client = mysqli_fetch_assoc($result);

if(isset($_POST['update']))
{
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $tag = $_POST['tag'];
    
    $image_query_part = "";
    if(isset($_FILES['image']) && $_FILES['image']['name'] != '') {
        $image_name = time() . '_' . $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], 'assets/clients/' . $image_name);
        $image_query_part = ", image='$image_name'";
    }

    mysqli_query($conn,"
    UPDATE clients
    SET
    name='$name',
    email='$email',
    phone='$phone',
    tag='$tag'
    $image_query_part
    WHERE id='$id'
    ");

    $action = "Updated Client Profile";
    $desc = "Updated details for client: $name.";
    mysqli_query($conn, "INSERT INTO client_activity_log (client_id, action, description) VALUES ('$id', '$action', '$desc')");

    header("Location: clients.php");
    exit;
}

?>

<?php include 'header.php'; ?>

<!-- Top Bar -->
<div class="topbar">
    <div class="topbar-left">
        <button class="mobile-toggle" onclick="openSidebar()">
            <i class="bi bi-list"></i>
        </button>
        <div class="topbar-title">
            <h1>Edit Client</h1>
            <p>Update client information</p>
        </div>
    </div>
    <div class="topbar-right">
        <a href="clients.php" class="btn-brand-outline">
            <i class="bi bi-arrow-left"></i>
            Back to Clients
        </a>
    </div>
</div>

<!-- Content -->
<div class="content-wrapper">

    <div class="page-card">
        <div class="page-card-header">
            <h2><i class="bi bi-pencil-square"></i> Edit Client</h2>
        </div>
        <div class="page-card-body">
            <form method="POST" enctype="multipart/form-data">

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-section">
                            <label class="form-label">Client Name</label>
                            <input type="text"
                                   name="name"
                                   class="form-control"
                                   value="<?php echo $client['name']; ?>"
                                   required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-section">
                            <label class="form-label">Email Address</label>
                            <input type="email"
                                   name="email"
                                   class="form-control"
                                   value="<?php echo $client['email']; ?>"
                                   required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-section">
                            <label class="form-label">Phone Number</label>
                            <input type="text"
                                   name="phone"
                                   class="form-control"
                                   value="<?php echo $client['phone']; ?>"
                                   required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-section">
                            <label class="form-label">Client Tag</label>
                            <select name="tag" class="form-control" required>
                                <option value="Active" <?php if(isset($client['tag']) && $client['tag'] == 'Active') echo 'selected'; ?>>Active</option>
                                <option value="VIP" <?php if(isset($client['tag']) && $client['tag'] == 'VIP') echo 'selected'; ?>>VIP</option>
                                <option value="Pending" <?php if(isset($client['tag']) && $client['tag'] == 'Pending') echo 'selected'; ?>>Pending</option>
                                <option value="Inactive" <?php if(isset($client['tag']) && $client['tag'] == 'Inactive') echo 'selected'; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-section">
                            <label class="form-label">Upload Logo</label>
                            <div style="display: flex; gap: 12px; align-items: center;">
                                <?php if(!empty($client['image']) && file_exists('assets/clients/'.$client['image'])) { ?>
                                <img src="assets/clients/<?php echo $client['image']; ?>" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover; box-shadow: var(--shadow-sm); border: 2px solid white;">
                                <?php } ?>
                                <input type="file"
                                       name="image"
                                       class="form-control"
                                       accept="image/*">
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 12px; margin-top: 8px;">
                    <button type="submit" name="update" class="btn-brand">
                        <i class="bi bi-check-circle-fill"></i>
                        Update Client
                    </button>
                    <a href="clients.php" class="btn-back">
                        <i class="bi bi-x-circle"></i>
                        Cancel
                    </a>
                </div>

            </form>
        </div>
    </div>

</div>

<?php include 'footer.php'; ?>
