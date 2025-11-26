<?php
require 'assets/includes/admin_config.php';
include_once '../assets/includes/components.php';
// Prepare roles query
$roles = $pdo->query('SELECT role, COUNT(*) as total FROM accounts GROUP BY role')->fetchAll(PDO::FETCH_KEY_PAIR);
foreach ($roles_list as $rl) {
    if (!isset($roles[$rl])) $roles[$rl] = 0;
}
$roles_active = $pdo->query('SELECT role, COUNT(*) as total FROM accounts WHERE last_seen > date_sub(now(), interval 1 month) GROUP BY role')->fetchAll(PDO::FETCH_KEY_PAIR);
$roles_inactive = $pdo->query('SELECT role, COUNT(*) as total FROM accounts WHERE last_seen < date_sub(now(), interval 1 month) GROUP BY role')->fetchAll(PDO::FETCH_KEY_PAIR);

// Prepare access level query
$access_level = $pdo->query('SELECT access_level, COUNT(*) as total FROM accounts GROUP BY access_level')->fetchAll(PDO::FETCH_KEY_PAIR);
foreach ($access_list as $al) {
    if (!isset($access_level[$al])) $access_level[$al] = 0;
}
$access_level_active = $pdo->query('SELECT access_level, COUNT(*) as total FROM accounts WHERE last_seen > date_sub(now(), interval 1 month) GROUP BY access_level')->fetchAll(PDO::FETCH_KEY_PAIR);
$access_level_inactive = $pdo->query('SELECT access_level, COUNT(*) as total FROM accounts WHERE last_seen < date_sub(now(), interval 1 month) GROUP BY access_level')->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<?=template_admin_header('Account Access & Roles', 'accounts', 'roles')?>

<?=generate_breadcrumbs([
    ['label' => 'Client Accounts', 'url' => 'accounts.php'],
    ['label' => 'Roles & Access Levels']
])?>

<div class="content-title mb-3">
    <div class="title">
       <i class="fa-solid fa-user-shield"></i>
        <div class="txt">
            <h2>Roles & Access Levels</h2>
            <p>View account distribution by role and access level</p>
        </div>
    </div>
</div>
<br>
    <h3>Account User Roles</h3>
<div class="content-block">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td colspan='1'>Role</td>
                    <td colspan='1'>Total Accounts</td>
                    <td colspan='1'>Active Accounts</td>
                    <td colspan='1'>Inactive Accounts</td>
                </tr>
            </thead>
            <tbody>
                <?php if (!$roles): ?>
                <tr>
                    <td colspan="8" style="text-align:center;">There are no roles</td>
                </tr>
                <?php endif; ?>
                <?php foreach ($roles as $k => $v): ?>
                <tr>
                    <td colspan='1'><?=$k?></td>
                    <td colspan='1'><a href="accounts.php?role=<?=$k?>" class="link1"><?=number_format($v)?></a></td>
                    <td colspan='1'><a href="accounts.php?role=<?=$k?>&status=active" class="link1"><?=number_format(isset($roles_active[$k]) ? $roles_active[$k] : 0)?></a></td>
                    <td colspan='1'><a href="accounts.php?role=<?=$k?>&status=inactive" class="link1"><?=number_format(isset($roles_inactive[$k]) ? $roles_inactive[$k] : 0)?></a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<br>
     <h3>Account User Access Levels</h3>
<div class="content-block">
    <div class="table">
        <table>
            <thead>
                <tr>
                    <td>Access</td>
                    <td>Total Accounts</td>
                    <td>Active Accounts</td>
                    <td>Inactive Accounts</td>
                </tr>
            </thead>
            <tbody>
                <?php if (!$access_level): ?>
                <tr>
                    <td colspan="8" style="text-align:center;">There are no access levels</td>
                </tr>
                <?php endif; ?>
                <?php foreach ($access_level as $k => $v): ?>
                <tr>
                    <td colspan='1'><?=$k?></td>
                    <td colspan='1'><a href="accounts.php?access_level=<?=$k?>" class="link1"><?=number_format($v)?></a></td>
                    <td colspan='1'><a href="accounts.php?access_level=<?=$k?>&status=active" class="link1"><?=number_format(isset($access_level_active[$k]) ? $access_level_active[$k] : 0)?></a></td>
                    <td><a href="accounts.php?access_level=<?=$k?>&status=inactive" class="link1"><?=number_format(isset($access_level_inactive[$k]) ? $access_level_inactive[$k] : 0)?></a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<style>
td {
    text-align: center;
}
</style>
<?=template_admin_footer()?>