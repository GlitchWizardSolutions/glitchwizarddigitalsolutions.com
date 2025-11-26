<?php
//removed remember me 9/6/24
//12/17/24 Works, ready for system formatting of form.
require 'assets/includes/admin_config.php';
include_once '../assets/includes/components.php';
// Default input account values
$account = [
    'username' => '',
    'password' => '',
    'email' => '',
    'activation_code' => 'activated',
    'role' => 'Member',
    'access_level' => 'Guest',
    'full_name' => '',
    'phone'     => '',
    'address_street'  => '',
    'address_city'    => '',
    'address_state'   => '',
    'address_zip'   => '',
    'address_country' => '',
    'document_path' => 'Welcome',
    'registered' => date('Y-m-d\TH:i:s'),
    'last_seen' => date('Y-m-d\TH:i:s'),
    'approved' => 'approved',
    'method' => 'password',
    'social_email' => '',
    'blog_user' => '1'
];
// If editing an account
if (isset($_GET['id'])) {
    // Get the account from the database
    $stmt = $pdo->prepare('SELECT * FROM accounts WHERE id = ?');
    $stmt->execute([ $_GET['id'] ]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    // ID param exists, edit an existing account
    $page = 'Edit';
    if (isset($_POST['submit'])) {
        // Check to see if username already exists
        $stmt = $pdo->prepare('SELECT id FROM accounts WHERE username = ? AND username != ?');
        $stmt->execute([ $_POST['username'], $account['username'] ]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            $error_msg = 'Username already exists!';
        }
        // Check to see if email already exists
        $stmt = $pdo->prepare('SELECT id FROM accounts WHERE email = ? AND email != ?');
        $stmt->execute([ $_POST['email'], $account['email'] ]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            $error_msg = 'Email already exists!';
        }
        // Update the account
        if (!isset($error_msg)) {
            $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : $account['password'];
            $stmt = $pdo->prepare('UPDATE accounts SET 
            username = ?, 
            password = ?, 
            email = ?, 
            activation_code = ?, 
            role = ?, 
            access_level = ?,
            full_name = ?, 
            phone = ?, 
            address_street= ?, 
            address_city= ?, 
            address_state= ?, 
            address_zip= ?, 
            address_country= ?, 
            document_path =?, 
            registered = ?, 
            last_seen = ?, 
            approved= ?, 
            method= ?, 
            social_email= ?,
            blog_user= ? 
            WHERE id = ?');
            $stmt->execute([
               $_POST['username'], 
               $password, 
               $_POST['email'],
               $_POST['activation_code'], 
               $_POST['role'],  
               $_POST['access_level'], 
               $_POST['full_name'], 
               $_POST['phone'], 
               $_POST['address_street'],
               $_POST['address_city'], 
               $_POST['address_state'],
               $_POST['address_zip'], 
               $_POST['address_country'],
               $_POST['document_path'],
               $_POST['registered'],
               $_POST['last_seen'], 
               $_POST['approved'],
               $_POST['method'],
               $_POST['social_email'],
               $_POST['blog_user'],
               $_GET['id'] ]);
            header('Location: accounts.php?success_msg=2');
            exit;
        } else {
            // Update the account variables
            $account = [
                'username'          => $_POST['username'],
                'password'          => $_POST['password'],
                'email'             => $_POST['email'],
                'activation_code'   => $_POST['activation_code'],
                'role'              => $_POST['role'],
                'access_level'      => $_POST['access_level'], 
                'full_name'         => $_POST['full_name'],
                'phone'             => $_POST['phone'],
                'address_street'    => $_POST['address_street'],
                'address_city'      => $_POST['address_city'],
                'address_state'     => $_POST['address_state'],
                'address_zip'       => $_POST['address_zip'],
                'address_country'   => $_POST['address_country'],
                'document_path'     => $_POST['document_path'],
                'registered'        => $_POST['registered'],
                'last_seen'         => $_POST['last_seen'],
                'approved'          => $_POST['approved'],
                'method'            => $_POST['method'],
                'social_email'      => $_POST['social_email'],
                'blog_user'         => $_POST['blog_user']
            ];
        }
    }
    if (isset($_POST['delete'])) {
        // Redirect and delete the account
        header('Location: accounts.php?delete=' . $_GET['id']);
        exit;
    }
} else {
    // Create a new account
    $page = 'Create';
    if (isset($_POST['submit'])) {
        // Check to see if username already exists
        $stmt = $pdo->prepare('SELECT id FROM accounts WHERE username = ?');
        $stmt->execute([ $_POST['username'] ]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            $error_msg = 'Username already exists!';
        }
        // Check to see if email already exists
        $stmt = $pdo->prepare('SELECT id FROM accounts WHERE email = ?');
        $stmt->execute([ $_POST['email'] ]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            $error_msg = 'Email already exists!';
        }
        // Insert the account
        if (!isset($error_msg)) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT IGNORE INTO accounts (
                username,
                password,
                email,
                activation_code,
                role,
                access_level,
                full_name,
                phone, 
                address_street, 
                address_city, 
                address_state,
                address_zip, 
                address_country, 
                document_path,
                registered,
                last_seen, 
                approved, 
                method, 
                social_email,
                blog_user) VALUES (?, ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([ 
               $_POST['username'], 
               $password, 
               $_POST['email'],
               $_POST['activation_code'], 
               $_POST['role'],  
               $_POST['access_level'], 
               $_POST['full_name'], 
               $_POST['phone'], 
               $_POST['address_street'],
               $_POST['address_city'], 
               $_POST['address_state'],
               $_POST['address_zip'], 
               $_POST['address_country'],
               $_POST['document_path'],
               $_POST['registered'],
               $_POST['last_seen'], 
               $_POST['approved'],
               $_POST['method'],
               $_POST['social_email'],
               $_POST['blog_user']]);
            header('Location: accounts.php?success_msg=1');
            exit;
        } else {
            // Update the account variables
            $account = [
                'username'          => $_POST['username'],
                'password'          => $_POST['password'],
                'email'             => $_POST['email'],
                'activation_code'   => $_POST['activation_code'],
                'role'              => $_POST['role'],
                'access_level'      => $_POST['access_level'], 
                'full_name'         => $_POST['full_name'],
                'phone'             => $_POST['phone'],
                'address_street'    => $_POST['address_street'],
                'address_city'      => $_POST['address_city'],
                'address_state'     => $_POST['address_state'],
                'address_zip'       => $_POST['address_zip'],
                'address_country'   => $_POST['address_country'],
                'document_path'     => $_POST['document_path'],
                'registered'        => $_POST['registered'],
                'last_seen'         => $_POST['last_seen'],
                'approved'          => $_POST['approved'],
                'method'            => $_POST['method'],
                'social_email'      => $_POST['social_email'],
                'blog_user'      => $_POST['blog_user']
            ];
        }
    }
}
?>
<?=template_admin_header($page . ' Account', 'accounts', 'manage')?>

<?=generate_breadcrumbs([
    ['label' => 'Client Accounts', 'url' => 'accounts.php'],
    ['label' => $page . ' Account']
])?>

<div class="content-title mb-3">
    <div class="title">
       <i class="fa-solid fa-user-pen"></i>
        <div class="txt">
            <h2><?=$page?> Account</h2>
            <p>Manage client account information and access</p>
        </div>
    </div>
</div>

<?php if (isset($error_msg)): ?>
<div class="mar-top-4">
    <div class="msg error">
        <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zm0-384c13.3 0 24 10.7 24 24V264c0 13.3-10.7 24-24 24s-24-10.7-24-24V152c0-13.3 10.7-24 24-24zM224 352a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z"/></svg>
        <p><?=$error_msg?></p>
        <svg class="close" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg>
    </div>
</div>
<?php endif; ?>

<form action="" method="post" enctype="multipart/form-data">
    <div class="form-professional">
        
        <!-- Account Information Section -->
        <div class="form-section">
            <h3 class="section-title">Account Information</h3>
            
            <div class="form-group">
                <label for="full_name">Full Name <span class="required">*</span></label>
                <input type="text" id="full_name" name="full_name" placeholder="Enter full name" value="<?=$account['full_name']?>" required>
            </div>
            
            <div class="form-group">
                <label for="document_path">Account File Access</label>
                <input type="text" id="document_path" name="document_path" placeholder="Firstname_Lastname (or 'Welcome' for default)" value="<?=$account['document_path']?>" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="username">Username <span class="required">*</span></label>
                    <input type="text" id="username" name="username" placeholder="Enter username" value="<?=$account['username']?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email <span class="required">*</span></label>
                    <input type="text" id="email" name="email" placeholder="Enter email address" value="<?=$account['email']?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password"><?=$page == 'Edit' ? 'New ' : ''?>Password<?=$page == 'Edit' ? ' (leave blank to keep current)' : ' <span class="required">*</span>'?></label>
                <input type="text" id="password" name="password" placeholder="Enter password" value=""<?=$page == 'Edit' ? '' : ' required'?>>
            </div>
        </div>

        <!-- Access & Permissions Section -->
        <div class="form-section">
            <h3 class="section-title">Access & Permissions</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role">
                        <?php foreach ($roles_list as $role): ?>
                        <option value="<?=$role?>"<?=$role==$account['role']?' selected':''?>><?=$role?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="access_level">Access Level</label>
                    <select id="access_level" name="access_level">
                        <?php foreach ($access_list as $access_level): ?>
                        <option value="<?=$access_level?>"<?=$access_level==$account['access_level']?' selected':''?>><?=$access_level?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="activation_code">Activation Status</label>
                    <?php 
                    if($account['activation_code']=='activated') {
                        $val='activated';
                    }else if($account['activation_code']=='deactivated'){
                        $val='deactivated';
                    }else{
                        $val='';
                    }
                    ?>
                    <select id="activation_code" name="activation_code">
                        <option value=""<?=$val==$account['activation_code']?' selected':''?>>N/A</option>
                        <option value="deactivated"<?=$val==$account['activation_code']?' selected':''?>>Deactivated</option>
                        <option value="activated"<?=$val==$account['activation_code']?' selected':''?>>Activated</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="approved">Approval Status</label>
                    <?php 
                    if($account['approved']=='approved') {
                        $val='approved';
                    }else if($account['approved']==''){
                        $val='';
                    }else{
                        $val='pending';
                    }
                    ?>
                    <select id="approved" name="approved">
                        <option value="approved"<?=$val==$account['approved']?' selected':''?>>Approved</option>
                        <option value="pending"<?=$val==$account['approved']?' selected':''?>>Pending</option>
                        <option value=""<?=$val==$account['approved']?' selected':''?>>N/A</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="method">Login Method</label>
                    <?php 
                    if($account['method']=='microsoft') {
                        $val='microsoft';
                    }else if($account['method']=='google'){
                        $val='google';
                    }else{
                        $val='password';
                    }
                    ?>
                    <select id="method" name="method">
                        <option value="password"<?=$val==$account['method']?' selected':''?>>Password</option>
                        <option value="google"<?=$val==$account['method']?' selected':''?>>Google</option>
                        <option value="microsoft"<?=$val==$account['method']?' selected':''?>>Microsoft</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="social_email">Social Email</label>
                    <input type="text" id="social_email" name="social_email" placeholder="Email from OAuth provider" value="<?=$account['social_email']?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="blog_user">Blog Access</label>
                <?php 
                if($account['blog_user']==1) {
                    $val=1;
                }else{
                    $val=0;
                }
                ?>
                <select id="blog_user" name="blog_user">
                    <option value="1"<?=$val==$account['blog_user']?' selected':''?>>Yes</option>
                    <option value="0"<?=$val==$account['blog_user']?' selected':''?>>No</option>
                </select>
            </div>
        </div>

        <!-- Contact Information Section -->
        <div class="form-section">
            <h3 class="section-title">Contact Information</h3>
            
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" placeholder="(000) 000-0000" value="<?=$account['phone']?>">
            </div>
            
            <div class="form-group">
                <label for="address_street">Street Address</label>
                <input type="text" id="address_street" name="address_street" placeholder="Street address" value="<?=$account['address_street']?>">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="address_city">City</label>
                    <input type="text" id="address_city" name="address_city" placeholder="City" value="<?=$account['address_city']?>">
                </div>
                
                <div class="form-group">
                    <label for="address_state">State</label>
                    <input type="text" id="address_state" name="address_state" placeholder="State" value="<?=$account['address_state']?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="address_zip">Zipcode</label>
                    <input type="text" id="address_zip" name="address_zip" placeholder="Zipcode" value="<?=$account['address_zip']?>">
                </div>
                
                <div class="form-group">
                    <label for="address_country">Country</label>
                    <input type="text" id="address_country" name="address_country" placeholder="Country" value="<?=$account['address_country']?>">
                </div>
            </div>
        </div>

        <!-- Account Activity Section -->
        <div class="form-section">
            <h3 class="section-title">Account Activity</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="registered">Registered Date <span class="required">*</span></label>
                    <input id="registered" type="datetime-local" name="registered" value="<?=date('Y-m-d\TH:i:s', strtotime($account['registered']))?>" required>
                </div>
                
                <div class="form-group">
                    <label for="last_seen">Last Seen</label>
                    <input id="last_seen" type="datetime-local" name="last_seen" value="<?=date('Y-m-d\TH:i:s', strtotime($account['last_seen']))?>">
                </div>
            </div>
        </div>

        <div class="form-actions">
            <a href="accounts.php" class="btn btn-secondary">Cancel</a>
            <?php if ($page == 'Edit'): ?>
            <input type="submit" name="delete" value="Delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this account?')">
            <?php endif; ?>
            <input type="submit" name="submit" value="<?=$page == 'Edit' ? 'Update' : 'Create'?> Account" class="btn btn-success">
        </div>

    </div>
</form>

<?=template_admin_footer()?>