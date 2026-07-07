<?php

session_start();

if(!isset($_SESSION['user']))
{
    header("Location: login.php");
    exit;
}

include 'db.php';
require_once 'auth.php';
require_permission('clients');

if(isset($_POST['save']))
{
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    
    $image_name = '';
    if(isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK && $_FILES['image']['size'] > 0) {
        $image_data = file_get_contents($_FILES['image']['tmp_name']);
        $image_type = $_FILES['image']['type'];
        $image_name = 'data:' . $image_type . ';base64,' . base64_encode($image_data);
    }

    mysqli_query($conn,"
    INSERT INTO clients(name,email,phone,image)
    VALUES('$name','$email','$phone','$image_name')
    ");
    $new_client_id = mysqli_insert_id($conn);
    $action = "Added New Client";
    $desc = "Client $name was added to the system.";
    mysqli_query($conn, "INSERT INTO client_activity_log (client_id, action, description) VALUES ('$new_client_id', '$action', '$desc')");

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
            <h1>Clients</h1>
            <p>Manage your client database</p>
        </div>
    </div>
    <div class="topbar-right">
        <div class="topbar-search">
            <i class="bi bi-search"></i>
            <input type="text" placeholder="Search clients..." id="clientSearch" onkeyup="filterTable()">
        </div>
    </div>
</div>

<!-- Content -->
<div class="content-wrapper">

    <!-- Add Client Form -->
    <div class="page-card mb-4">
        <div class="page-card-header">
            <h2><i class="bi bi-person-plus-fill"></i> Add New Client</h2>
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
                                   placeholder="Enter client name"
                                   required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-section">
                            <label class="form-label">Email Address</label>
                            <input type="email"
                                   name="email"
                                   class="form-control"
                                   placeholder="Enter email address"
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
                                   placeholder="Enter phone number"
                                   required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-section">
                            <label class="form-label">Upload Logo (Optional)</label>
                            <input type="file"
                                   name="image"
                                   class="form-control"
                                   accept="image/*">
                        </div>
                    </div>
                </div>
                <button type="submit" name="save" class="btn-brand">
                    <i class="bi bi-plus-circle-fill"></i>
                    Save Client
                </button>
            </form>
        </div>
    </div>

    <!-- Clients List -->
    <div class="page-card">
        <div class="page-card-header">
            <h2><i class="bi bi-people-fill"></i> Clients List</h2>
        </div>
        <div class="page-card-body" style="padding: 0;">
            <div class="table-wrapper">
                <table class="modern-table" id="clientsTable">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Tag</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $result = mysqli_query($conn,"SELECT * FROM clients ORDER BY id DESC");
                    $sr = 1;

                    while($row = mysqli_fetch_assoc($result))
                    {
                    ?>
                    <tr>
                        <td style="font-weight: 600; color: var(--gray-500);">#<?php echo $sr++; ?></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <?php 
                                $has_img = false;
                                $img_src = '';
                                if(!empty($row['image'])) { 
                                    if(strpos($row['image'], 'data:image') === 0) {
                                        // Check if base64 might be truncated from old varchar(255) column
                                        if(strlen($row['image']) > 255) {
                                            $has_img = true;
                                            $img_src = $row['image'];
                                        }
                                    } else if(file_exists('assets/clients/'.$row['image'])) {
                                        $has_img = true;
                                        $img_src = 'assets/clients/'.$row['image'];
                                    }
                                }
                                if($has_img) {
                                ?>
                                <img src="<?php echo $img_src; ?>" style="width: 34px; height: 34px; border-radius: 50%; object-fit: cover; flex-shrink: 0; box-shadow: var(--shadow-sm); border: 2px solid white;">
                                <?php } else { ?>
                                <div style="width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, var(--teal-600), var(--navy-600)); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 13px; flex-shrink: 0; box-shadow: var(--shadow-sm); border: 2px solid white;">
                                    <?php echo strtoupper(substr($row['name'], 0, 1)); ?>
                                </div>
                                <?php } ?>
                                <span style="font-weight: 600; color: var(--navy-800);"><?php echo $row['name']; ?></span>
                            </div>
                        </td>
                        <td><?php echo $row['email']; ?></td>
                        <td><?php echo $row['phone']; ?></td>
                        <td>
                            <?php 
                            $tag = $row['tag'] ?? 'Active';
                            $badgeClass = '';
                            if($tag == 'VIP') $badgeClass = 'background: var(--purple-100); color: var(--purple-700);';
                            elseif($tag == 'Active') $badgeClass = 'background: var(--green-100); color: var(--green-700);';
                            elseif($tag == 'Pending') $badgeClass = 'background: var(--orange-100); color: var(--orange-700);';
                            else $badgeClass = 'background: var(--gray-100); color: var(--gray-700);';
                            ?>
                            <span style="padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; <?php echo $badgeClass; ?>">
                                <?php echo htmlspecialchars($tag); ?>
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 6px;">
                                <a class="action-btn" style="background: var(--teal-50); color: var(--teal-600);"
                                   href="view_client.php?id=<?php echo $row['id']; ?>"
                                   title="View Profile">
                                    <i class="bi bi-person-lines-fill"></i>
                                </a>
                                <a class="action-btn edit"
                                   href="edit_client.php?id=<?php echo $row['id']; ?>"
                                   title="Edit">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                <a class="action-btn delete"
                                   href="delete_client.php?id=<?php echo $row['id']; ?>"
                                   title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this client?')">
                                    <i class="bi bi-trash-fill"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php include 'footer.php'; ?>

<script>
function filterTable() {
    const query = document.getElementById('clientSearch').value.toLowerCase();
    const rows = document.querySelectorAll('#clientsTable tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(query) ? '' : 'none';
    });
}
</script>
