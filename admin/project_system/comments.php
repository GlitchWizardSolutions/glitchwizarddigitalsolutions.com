<?php
require 'assets/includes/admin_config.php';
// Delete comment
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare('DELETE FROM project_tickets_comments WHERE id = ?');
    $stmt->execute([ $_GET['delete'] ]);
    header('Location: comments.php?success_msg=3');
    exit;
}

// Retrieve the GET request parameters (if specified)
$pagination_page = isset($_GET['pagination_page']) ? $_GET['pagination_page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';
// Order by column
$order = isset($_GET['order']) && $_GET['order'] == 'ASC' ? 'ASC' : 'DESC';
// Add/remove columns to the whitelist array
$order_by_whitelist = ['ticket_id','ticket_title','msg','full_name','created'];
$order_by = isset($_GET['order_by']) && in_array($_GET['order_by'], $order_by_whitelist) ? $_GET['order_by'] : 'created';
// Number of results per pagination page
$results_per_page = 15;
// Declare query param variables
$param1 = ($pagination_page - 1) * $results_per_page;
$param2 = $results_per_page;
$param3 = '%' . $search . '%';
// SQL where clause
$where = '';
$where .= $search ? 'WHERE (t.title LIKE :search OR t.full_name LIKE :search OR t.email LIKE :search OR t.id LIKE :search OR a.full_name LIKE :search) ' : '';
if (isset($_GET['acc_id'])) {
    $where .= $where ? ' AND t.acc_id = :acc_id ' : ' WHERE t.acc_id = :acc_id ';
} 
// Retrieve the total number of comments from the database
$stmt = $pdo->prepare('SELECT COUNT(*) AS total, tc.* FROM project_tickets_comments tc JOIN project_tickets t ON t.id = tc.ticket_id LEFT JOIN accounts a ON a.id = tc.acc_id ' . $where . ' GROUP BY tc.id ');
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
if (isset($_GET['acc_id'])) $stmt->bindParam('acc_id', $_GET['acc_id'], PDO::PARAM_INT);
$stmt->execute();
$comments_total = $stmt->fetchColumn();
// SQL query to get all comments from the "comments" table
$stmt = $pdo->prepare('SELECT tc.*, t.title AS ticket_title, t.last_comment as last_comment, a.full_name AS full_name, t.email AS ticket_email FROM project_tickets_comments tc JOIN project_tickets t ON t.id = tc.ticket_id LEFT JOIN accounts a ON a.id = tc.acc_id ' . $where . ' GROUP BY tc.id ORDER BY ' . $order_by . ' ' . $order . ' LIMIT :start_results,:num_results');
// Bind params
$stmt->bindParam('start_results', $param1, PDO::PARAM_INT);
$stmt->bindParam('num_results', $param2, PDO::PARAM_INT);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
if (isset($_GET['acc_id'])) $stmt->bindParam('acc_id', $_GET['acc_id'], PDO::PARAM_INT);
$stmt->execute();
// Retrieve query results
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Handle success messages
if (isset($_GET['success_msg'])) {
    if ($_GET['success_msg'] == 1) {
        $success_msg = 'Comment created successfully!';
    }
    if ($_GET['success_msg'] == 2) {
        $success_msg = 'Comment updated successfully!';
    }
    if ($_GET['success_msg'] == 3) {
        $success_msg = 'Comment deleted successfully!';
    }
}
// Determine the URL
$url = 'comments.php?search=' . $search;
?>

<?=template_admin_header('Project Ticket Comments', 'ticketing', 'projects')?>

<div class="content-title mb-3">
    <div class="title">
        <div class="icon alt">
            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M9,22A1,1 0 0,1 8,21V18H4A2,2 0 0,1 2,16V4C2,2.89 2.9,2 4,2H20A2,2 0 0,1 22,4V16A2,2 0 0,1 20,18H13.9L10.2,21.71C10,21.9 9.75,22 9.5,22V22H9Z" /></svg>
        </div>
        <div class="txt">
            <h2>Project Ticket Comments</h2>
            <p>View, manage, and search project comments</p>
        </div>
    </div>
</div>

<?php if (isset($success_msg)): ?>
<div class="msg success">
    <i class="fas fa-check-circle"></i>
    <p><?=$success_msg?></p>
    <i class="fas fa-times"></i>
</div>
<?php endif; ?>

<div class="content-header responsive-flex-column pad-top-5">
    <div class="btns">
        <a href="comment.php" class="btn btn-primary">+ Create Comment</a>
    </div>
    <form action="" method="get">
        <div class="search">
            <label for="search">
                <input id="search" type="text" name="search" placeholder="Search comments..." value="<?=htmlspecialchars($search, ENT_QUOTES)?>" class="responsive-width-100">
                <i class="fas fa-search"></i>
            </label>
        </div>
    </form>
</div>

<div class="content-block">
    <div class="table">
        <table>
            <thead>
                <tr>
                    
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=ticket_title'?>">Ticket Title<?php if ($order_by=='ticket_title'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=msg'?>">New Comment<?php if ($order_by=='msg'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=last_comment'?>">Latest<?php if ($order_by=='last_comment'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=created'?>">Date<?php if ($order_by=='created'): ?><i class="fas fa-level-<?=str_replace(['ASC', 'DESC'], ['up','down'], $order)?>-alt fa-xs"></i><?php endif; ?></a></td>
                    <td>Actions</td>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($comments)): ?>
                <tr>
                    <td colspan="20" style="text-align:center;">There are no comments.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                <tr>
                    <?php if($comment['last_comment']  == 0) {
                        $last_comment = 'Admin';
                    }else{
                        $last_comment = 'Member';
                    }
                    
                    ?>
                    <td><?=$comment['ticket_title']?></td>
                  
                    <td style="max-width:200px"><?=nl2br(htmlspecialchars($comment['msg'], ENT_QUOTES))?></td>
                    <td><?=$last_comment ?></td>
                    <td class="responsive-hidden"><?=date('m/d/y', strtotime($comment['created']))?></td>
                      <td class="actions">
                        <div class="table-dropdown">
                            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M8 256a56 56 0 1 1 112 0A56 56 0 1 1 8 256zm160 0a56 56 0 1 1 112 0 56 56 0 1 1 -112 0zm216-56a56 56 0 1 1 0 112 56 56 0 1 1 0-112z"/></svg>
                            <div class="table-dropdown-items">
                               
                                 <a href="view.php?id=<?=$comment['ticket_id']?>&code=<?=md5($comment['ticket_id'] . $comment['ticket_email'])?>" style="color:blue">
                                    <span class="icon">
                                      <i class="fa-regular fa-eye fa-xs"></i>
                                    </span>
                                    View
                                </a>
                                <a href="comment.php?id=<?=$comment['id']?>" style='color:orange'>
                                    <span class="icon">
                                     <i class="fa-regular fa-pen-to-square fa-xs"></i>
                                    </span>
                                    Edit
                                </a>
                                <a class="red" href="comments.php?delete=<?=$comment['id']?>"   onclick="return confirm('Are you sure you want to delete this comment?')">
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
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="pagination">
    <?php if ($pagination_page > 1): ?>
    <a href="<?=$url?>&pagination_page=<?=$pagination_page-1?>&order=<?=$order?>&order_by=<?=$order_by?>">Prev</a>
    <?php endif; ?>
    <span>Page <?=$pagination_page?> of <?=ceil($comments_total / $results_per_page) == 0 ? 1 : ceil($comments_total / $results_per_page)?></span>
    <?php if ($pagination_page * $results_per_page < $comments_total): ?>
    <a href="<?=$url?>&pagination_page=<?=$pagination_page+1?>&order=<?=$order?>&order_by=<?=$order_by?>">Next</a>
    <?php endif; ?>
</div>

<?=template_admin_footer()?>