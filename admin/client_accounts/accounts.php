<?php 
//12/17/24 Works, ready for system formatting of buttons, ready for Action Items to have a View.
require 'assets/includes/admin_config.php';
include_once '../assets/includes/components.php';
// Retrieve the GET request parameters (if specified)
$pagination_page = isset($_GET['pagination_page']) ? $_GET['pagination_page'] : 1;
$search = isset($_GET['search_query']) ? $_GET['search_query'] : '';
// Filters parameters
$access_level  = isset($_GET['access_level']) ? $_GET['access_level'] : '';
// Filters parameters added
$status = isset($_GET['status']) ? $_GET['status'] : '';
$last_seen = isset($_GET['last_seen']) ? $_GET['last_seen'] : '';
// Order by column
$order = isset($_GET['order']) && $_GET['order'] == 'DESC' ? 'DESC' : 'ASC';
// Add/remove columns to the whitelist array
$order_by_whitelist = ['status','last_seen','full_name','email','activation_code','registered','access_level'];
$order_by = isset($_GET['order_by']) && in_array($_GET['order_by'], $order_by_whitelist) ? $_GET['order_by'] : 'full_name';
// Number of results per pagination pagination_page
$results_per_pagination_page = 20;
// Accounts array
$accounts = [];
// Declare query param variables
$param1 = ($pagination_page - 1) * $results_per_pagination_page;
$param2 = $results_per_pagination_page;
$param3 = '%' . $search . '%';
// SQL where clause
$where = '';
$where .= $search ? 'WHERE (a.full_name LIKE :search OR a.document_path LIKE :search OR a.phone LIKE :search or a.username LIKE :search OR a.email LIKE :search) ' : '';
// Add filters
//Access Level filter
if ($access_level) {
    $where .= ($where ? 'AND ' : 'WHERE ') . 'a.access_level = :access_level ';
}

// Last seen filter
if ($last_seen == 'today') {
    $where .= ($where ? 'AND ' : 'WHERE ') . 'a.last_seen > date_sub("' . date('Y-m-d H:i:s') . '", interval 1 day) '; 
} else if ($last_seen == 'yesterday') {
    $where .= ($where ? 'AND ' : 'WHERE ') . 'a.last_seen > date_sub("' . date('Y-m-d H:i:s') . '", interval 2 day) AND last_seen < date_sub("' . date('Y-m-d H:i:s') . '", interval 1 day) '; 
} else if ($last_seen == 'week') {
    $where .= ($where ? 'AND ' : 'WHERE ') . 'a.last_seen > date_sub("' . date('Y-m-d H:i:s') . '", interval 1 week) '; 
} else if ($last_seen == 'month') {
    $where .= ($where ? 'AND ' : 'WHERE ') . 'a.last_seen > date_sub("' . date('Y-m-d H:i:s') . '", interval 1 month) '; 
} else if ($last_seen == 'year') {
    $where .= ($where ? 'AND ' : 'WHERE ') . 'a.last_seen > date_sub("' . date('Y-m-d H:i:s') . '", interval 1 year) '; 
} else if ($last_seen == 'inactive') {
    $where .= ($where ? 'AND ' : 'WHERE ') . 'a.last_seen < date_sub("' . date('Y-m-d H:i:s') . '", interval 1 month) '; 
}
// Status filter
if ($status == 'Activated') {
    $where .= ($where ? 'AND ' : 'WHERE ') . 'a.activation_code = "activated" '; 
} else if ($status == 'Deactivated') {
    $where .= ($where ? 'AND ' : 'WHERE ') . 'a.activation_code = "deactivated" '; 
} else if ($status == 'Pending Activation') {
    $where .= ($where ? 'AND ' : 'WHERE ') . 'a.activation_code != "activated" AND a.activation_code != "deactivated" '; 
} else if ($status == 'Approved') {
    $where .= ($where ? 'AND ' : 'WHERE ') . 'a.approved = "approved" '; 
} else if ($status == 'Pending Approval') {
    $where .= ($where ? 'AND ' : 'WHERE ') . 'a.approved = "pending" '; 
}

// Retrieve the total number of accounts
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM accounts a ' . $where);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
if ($access_level) $stmt->bindParam('access_level', $access_level, PDO::PARAM_STR);
$stmt->execute();
$total_accounts = $stmt->fetchColumn();
// Prepare accounts query
$stmt = $pdo->prepare('SELECT a.* FROM accounts a ' . $where . ' ORDER BY ' . $order_by . ' ' . $order . ' LIMIT :start_results,:num_results');
$stmt->bindParam('start_results', $param1, PDO::PARAM_INT);
$stmt->bindParam('num_results', $param2, PDO::PARAM_INT);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
if ($access_level) $stmt->bindParam('access_level', $access_level, PDO::PARAM_STR);
$stmt->execute();
// Retrieve query results
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Delete account
if (isset($_GET['delete'])) {
    // Delete the account
    $stmt = $pdo->prepare('DELETE FROM accounts WHERE id = ?');
    $stmt->execute([ $_GET['delete'] ]);
    header('Location: accounts.php?success_msg=3');
    exit;
}
// deactivate (also remove remember me code)
if (isset($_GET['deactivate'])) {
    // Update the account
    $stmt = $pdo->prepare('UPDATE accounts SET activation_code = "deactivated", rememberme = "" WHERE id = ?');
    $stmt->execute([ $_GET['deactivate'] ]);
    header('Location: accounts.php?success_msg=2');
    exit;
}
// activate
if (isset($_GET['activate'])) {
    // Update the account
    $stmt = $pdo->prepare('UPDATE accounts SET activation_code = "activated" WHERE id = ?');
    $stmt->execute([ $_GET['activate'] ]);
    header('Location: accounts.php?success_msg=2');
    exit;
}
// approve
if (isset($_GET['approve'])) {
    // Update the account
    $stmt = $pdo->prepare('UPDATE accounts SET approved = "approved" WHERE id = ?');
    $stmt->execute([ $_GET['approve'] ]);
    header('Location: accounts.php?success_msg=2');
    exit;
}

// Handle success messages
if (isset($_GET['success_msg'])) {
    if ($_GET['success_msg'] == 1) {
        $success_msg = 'Account created successfully!';
    }
    if ($_GET['success_msg'] == 2) {
        $success_msg = 'Account updated successfully!';
    }
    if ($_GET['success_msg'] == 3) {
        $success_msg = 'Account deleted successfully!';
    }
    if ($_GET['success_msg'] == 4) {
        $success_msg = 'Account(s) imported successfully! ' . $_GET['imported'] . ' account(s) were imported.';
    }
}
// Create URL
$url = 'accounts.php?search_query=' . $search . '&status=' . $status . '&last_seen=' . $last_seen .  '&access_level=' . $access_level;?>
<?=template_admin_header('Accounts', 'accounts', 'view')?>

<?=generate_breadcrumbs([
    ['label' => 'Accounts']
])?>

<div class="content-title">
    <div class="icon alt"><?=svg_icon_user()?></div>
    <div class="txt">
        <h2>Client Accounts</h2>
        <p class="subtitle">Manage user accounts and access levels</p>
    </div>
</div>

<?php if (isset($success_msg)): ?>
<div class="msg success">
    <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM369 209L241 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L335 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/></svg>
    <p><?=$success_msg?></p>
    <svg class="close" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg>
</div>
<?php endif; ?>

<div class="content-header responsive-flex-column pad-top-5">
    <a href="account.php" class="btn btn-success">
        <svg class="icon-left" width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 80c0-17.7-14.3-32-32-32s-32 14.3-32 32V224H48c-17.7 0-32 14.3-32 32s14.3 32 32 32H192V432c0 17.7 14.3 32 32 32s32-14.3 32-32V288H400c17.7 0 32-14.3 32-32s-14.3-32-32-32H256V80z"/></svg>
        Create Account
    </a>
    <form action="" method="get">
        <input type="hidden" name="page" value="accounts">
        <div class="filters">
            <a href="#">
                <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M0 416c0 17.7 14.3 32 32 32l54.7 0c12.3 28.3 40.5 48 73.3 48s61-19.7 73.3-48L480 448c17.7 0 32-14.3 32-32s-14.3-32-32-32l-246.7 0c-12.3-28.3-40.5-48-73.3-48s-61 19.7-73.3 48L32 384c-17.7 0-32 14.3-32 32zm128 0a32 32 0 1 1 64 0 32 32 0 1 1 -64 0zM320 256a32 32 0 1 1 64 0 32 32 0 1 1 -64 0zm32-80c-32.8 0-61 19.7-73.3 48L32 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l246.7 0c12.3 28.3 40.5 48 73.3 48s61-19.7 73.3-48l54.7 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-54.7 0c-12.3-28.3-40.5-48-73.3-48zM192 128a32 32 0 1 1 0-64 32 32 0 1 1 0 64zm73.3-64C253 35.7 224.8 16 192 16s-61 19.7-73.3 48L32 64C14.3 64 0 78.3 0 96s14.3 32 32 32l86.7 0c12.3 28.3 40.5 48 73.3 48s61-19.7 73.3-48L480 128c17.7 0 32-14.3 32-32s-14.3-32-32-32L265.3 64z"/></svg>
                Filters
            </a>
            <div class="list">
                <label for="access_level">Access Level</label>
                <select name="access_level" id="access_level">
                    <option value=""<?=$access_level==''?' selected':''?>>All</option>
                    <option value="Guest"<?=$access_level=='Guest'?' selected':''?>>Guest</option>
                    <option value="Onboarding"<?=$access_level=='Onboarding'?' selected':''?>>Onboarding</option>
                    <option value="Branding"<?=$access_level=='Branding'?' selected':''?>>Branding</option>
                    <option value="Legal"<?=$access_level=='Legal'?' selected':''?>>Legal</option>
                    <option value="Development"<?=$access_level=='Guest'?' selected':''?>>Development</option>
                    <option value="Production"<?=$access_level=='Production'?' selected':''?>>Production</option>
                    <option value="Hosting"<?=$access_level=='Hosting'?' selected':''?>>Hosting</option>
                    <option value="Services"<?=$access_level=='Services'?' selected':''?>>Services</option>
                    <option value="Master"<?=$access_level=='Master'?' selected':''?>>Master</option>
                    <option value="Admin"<?=$access_level=='Admin'?' selected':''?>>Admin/Manager</option>
                </select>
             
                <label for="last_seen">Last Seen</label>
                <select name="last_seen" id="last_seen">
                    <option value=""<?=$last_seen==''?' selected':''?>>All</option>
                    <option value="today"<?=$last_seen=='today'?' selected':''?>>Today</option>
                    <option value="yesterday"<?=$last_seen=='yesterday'?' selected':''?>>Yesterday</option>
                    <option value="week"<?=$last_seen=='week'?' selected':''?>>This Week</option>
                    <option value="month"<?=$last_seen=='month'?' selected':''?>>This Month</option>
                    <option value="year"<?=$last_seen=='year'?' selected':''?>>This Year</option>
                    <option value="inactive"<?=$last_seen=='inactive'?' selected':''?>>Inactive</option>
                </select>
                <label for="status">Status</label>
                <select name="status" id="status">
                    <option value=""<?=$status==''?' selected':''?>>All</option>
                    <option value="Activated"<?=$status=='Activated'?' selected':''?>>Activated</option>
                    <option value="Deactivated"<?=$status=='Deactivated'?' selected':''?>>Deactivated</option>
                    <option value="Pending Activation"<?=$status=='Pending Activation'?' selected':''?>>Pending Activation</option>
                    <option value="Approved"<?=$status=='Approved'?' selected':''?>>Approved</option>
                    <option value="Pending Approval"<?=$status=='Pending Approval'?' selected':''?>>Pending Approval</option>
                </select>
                <button type="submit">Apply</button>
            </div>
        </div>
        <div class="search">
            <label for="search_query">
                <input id="search_query" type="text" name="search_query" placeholder="Search account..." value="<?=htmlspecialchars($search, ENT_QUOTES)?>" class="responsive-width-100">
                <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"/></svg>
            </label>
        </div>
    </form>
</div>

<div class="filter-list">
  
    <?php if ($access_level != ''): ?>
    <div class="filter">
        <a href="<?=remove_url_param($url, 'access_level')?>"><svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg></a>
        Access Level : <?=htmlspecialchars($access_level, ENT_QUOTES)?>
    </div>
    <?php endif; ?>
 
    <?php if ($last_seen != ''): ?>
    <div class="filter">
        <a href="<?=remove_url_param($url, 'last_seen')?>"><svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free --><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg></a>
        Last Seen : <?=htmlspecialchars($last_seen, ENT_QUOTES)?>
    </div>
    <?php endif; ?>
    <?php if ($status != ''): ?>
    <div class="filter">
        <a href="<?=remove_url_param($url, 'status')?>"><svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free --><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg></a>
        Status : <?=htmlspecialchars($status, ENT_QUOTES)?>
    </div>
    <?php endif; ?>
    <?php if ($search != ''): ?>
    <div class="filter">
        <a href="<?=remove_url_param($url, 'search_query')?>"><svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free --><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg></a>
        Search : <?=htmlspecialchars($search, ENT_QUOTES)?>
    </div>
    <?php endif; ?>   
</div>

<div class="content-block">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td colspan="2"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=id'?>">#<?=$order_by=='id' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td colspan="2"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=full_name'?>">Name<?=$order_by=='full_name' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=last_seen'?>">Active<?=$order_by=='last_seen' ? $table_icons[strtolower($order)] : ''?></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=access_level'?>">Access<?=$order_by=='access_level' ? $table_icons[strtolower($order)] : ''?></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=email'?>">Email<?=$order_by=='email' ? $table_icons[strtolower($order)] : ''?></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=activation_code'?>">Status<?=$order_by=='activation_code' ? $table_icons[strtolower($order)] : ''?></td>

                    <td class="align-center">Action</td>
                </tr>
            </thead>
            <tbody>
                <?php if (!$accounts): ?>
                <tr>
                    <td colspan="20" class="no-results">There are no accounts.</td>
                </tr>
                <?php endif; ?>
                <?php foreach ($accounts as $account): ?>
                <tr>
                    <td><?=$account['id']?></td>
                    <td class="img">
                        <div class="profile-img">
                            <span style="background-color:<?=color_from_string($account['username'])?>"><?=strtoupper(substr($account['username'], 0, 1))?></span>
                       <?php if ($account['last_seen'] > date('Y-m-d H:i:s', strtotime('-15 minutes'))): ?>
                            <i class="online" title="Online"></i>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td colspan="2"><?=htmlspecialchars($account['full_name'], ENT_QUOTES)?></td>
                    <td class="responsive-hidden" title="<?=$account['last_seen']?>"><?=time_elapsed_string($account['last_seen'])?></td>
                    <td class="responsive-hidden"><?=$account['access_level']?></td>
                    <td class="responsive-hidden"><?=htmlspecialchars($account['email'], ENT_QUOTES)?></td>
                     <td class="responsive-hidden">
                        <?php if (!$account['approved']): ?>
                        <span class="orange">Pending Approval</span>
                        <?php elseif ($account['activation_code'] == 'activated'): ?>
                        <span class="green">Activated</span>
                        <?php elseif ($account['activation_code'] == 'deactivated'): ?>
                        <span class="red">Deactivated</span>
                        <?php else: ?>
                        <span class="grey" title="<?=$account['activation_code']?>">Pending Activation</span>
                        <?php endif; ?>
                    </td>
                    <td class="actions">
                        <div class="table-dropdown">
                            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M8 256a56 56 0 1 1 112 0A56 56 0 1 1 8 256zm160 0a56 56 0 1 1 112 0 56 56 0 1 1 -112 0zm216-56a56 56 0 1 1 0 112 56 56 0 1 1 0-112z"/></svg>
                            <div class="table-dropdown-items">
                                 <a href="client-view.php?id=<?=$account['id']?>" style="color:blue">
                                    <span class="icon">
                                      <i class="fa-regular fa-eye fa-xs"></i>
                                    </span>
                                    View
                                </a>
                                <a href="account.php?id=<?=$account['id']?>"style='color:orange'>
                                    <span class="icon">
                                     <i class="fa-regular fa-pen-to-square fa-xs"></i>
                                    </span>
                                    Edit
                                </a>
                                 <?php if (!$account['approved']): ?>
                                <a class="green" href="accounts.php?approve=<?=$account['id']?>" onclick="return confirm('Are you sure you want to approve this account?')">
                                    <span class="icon">
                                        <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M96 128a128 128 0 1 1 256 0A128 128 0 1 1 96 128zM0 482.3C0 383.8 79.8 304 178.3 304h91.4C368.2 304 448 383.8 448 482.3c0 16.4-13.3 29.7-29.7 29.7H29.7C13.3 512 0 498.7 0 482.3zM625 177L497 305c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L591 143c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/></svg>
                                    </span>    
                                    Approve
                                </a>
                                <?php endif; ?>
                                <?php if ($account['activation_code'] != 'activated'): ?>
                                <a class="green" href="accounts.php?activate=<?=$account['id']?>" onclick="return confirm('Are you sure you want to activate this account?')">
                                    <span class="icon">
                                        <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M96 128a128 128 0 1 1 256 0A128 128 0 1 1 96 128zM0 482.3C0 383.8 79.8 304 178.3 304h91.4C368.2 304 448 383.8 448 482.3c0 16.4-13.3 29.7-29.7 29.7H29.7C13.3 512 0 498.7 0 482.3zM625 177L497 305c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L591 143c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/></svg>
                                    </span>    
                                    Activate
                                </a>
                                <?php endif; ?>
                                <?php if ($account['activation_code'] != 'deactivated'): ?>
                                <a class="red" href="accounts.php?deactivate=<?=$account['id']?>" onclick="return confirm('Are you sure you want to deactivate this account? They will no longer be able to log in.')">
                                    <span class="icon">
                                        <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M38.8 5.1C28.4-3.1 13.3-1.2 5.1 9.2S-1.2 34.7 9.2 42.9l592 464c10.4 8.2 25.5 6.3 33.7-4.1s6.3-25.5-4.1-33.7L353.3 251.6C407.9 237 448 187.2 448 128C448 57.3 390.7 0 320 0C250.2 0 193.5 55.8 192 125.2L38.8 5.1zM264.3 304.3C170.5 309.4 96 387.2 96 482.3c0 16.4 13.3 29.7 29.7 29.7H514.3c3.9 0 7.6-.7 11-2.1l-261-205.6z"/></svg>
                                    </span>    
                                    Deactivate
                                </a>
                                <?php endif; ?>
                                <a class="red" href="accounts.php?delete=<?=$account['id']?>" onclick="return confirm('Are you sure you want to delete this account?')">
                                    <span class="icon">
                                        <svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z"/></svg>
                                    </span>    
                                    Delete
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="pagination">
    <?php if ($pagination_page > 1): ?>
    <a href="<?=$url?>&pagination_page=<?=$pagination_page-1?>&order=<?=$order?>&order_by=<?=$order_by?>">Prev</a>
    <?php endif; ?>
    <span>Page <?=$pagination_page?> of <?=ceil($total_accounts / $results_per_pagination_page) == 0 ? 1 : ceil($total_accounts / $results_per_pagination_page)?></span>
    <?php if ($pagination_page * $results_per_pagination_page < $total_accounts): ?>
    <a href="<?=$url?>&pagination_page=<?=$pagination_page+1?>&order=<?=$order?>&order_by=<?=$order_by?>">Next</a>
    <?php endif; ?>
</div>

<?=template_admin_footer()?>