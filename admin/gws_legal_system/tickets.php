<?php
/*******************************************************************************
GWS Legal Requriment SYSTEM - tickets.php
LOCATION: /public_html/admin/gws_legal_system
DESCRIBE: Admin dashboard to view, edit, delete, import, export, create tickets
INPUTREQ: 
LOGGEDIN: REQUIRED
REQUIRED:
  SYSTEM: DATABASE,LOGIN
   ADMIN: /public_html/admin/
   PAGES: tickets_import.php,tickets_export.php,ticket.php
   FILES: 
   PARMS: 
     OUT:
DATABASE: TABLES gws_legal,gws_legal_comments,gws_legal_uploads
LOG NOTE:  
*******************************************************************************/
require 'assets/includes/admin_config.php';
// Delete ticket
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare('DELETE t, tc, tu FROM gws_legal t LEFT JOIN gws_legal_comments tc ON tc.ticket_id = t.id LEFT JOIN gws_legal_uploads tu ON tu.ticket_id = t.id WHERE t.id = ?');
    $stmt->execute([ $_GET['delete'] ]);
    header('Location: tickets.php?success_msg=3');
    exit;
}
// Approve ticket (in this application, tickets do not need approved, they are approved by default, since users can't set their ticket to public in this installation.)
if (isset($_GET['approve'])) {
    $stmt = $pdo->prepare('UPDATE gws_legal SET approved = 1 WHERE id = ?');
    $stmt->execute([ $_GET['approve'] ]);
    header('Location: tickets.php?success_msg=2');
    exit;
}
// Retrieve the GET request parameters (if specified)
$pagination_page = isset($_GET['pagination_page']) ? $_GET['pagination_page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';
// Filters
$status = isset($_GET['status']) ? $_GET['status'] : '';
 
// Order by column
$order = isset($_GET['order']) && $_GET['order'] == 'DESC' ? 'DESC' : 'ASC';
// Add/remove columns to the whitelist array
$order_by_whitelist = ['ticket_status', 'last_comment', 'created','priority','category_id','title','msg','private'];
$order_by = isset($_GET['order_by']) && in_array($_GET['order_by'], $order_by_whitelist) ? $_GET['order_by'] : 'ticket_status';
// Number of results per pagination page
$results_per_page = 15;
// Declare query param variables
$param1 = ($pagination_page - 1) * $results_per_page;
$param2 = $results_per_page;
$param3 = '%' . $search . '%';
// SQL where clause
$where = '';
$where .= $search ? 'WHERE (t.msg LIKE :search OR t.email LIKE :search OR t.title LIKE :search) ' : '';
if (isset($_GET['acc_id'])) {
    $where .= $where ? ' AND t.acc_id = :acc_id ' : ' WHERE t.acc_id = :acc_id ';
} 
if ($status) {
    $where .= $where ? ' AND t.ticket_status = :status ' : ' WHERE t.ticket_status = :status ';
}


// Retrieve the total number of tickets from the database
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM gws_legal t ' . $where);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
if (isset($_GET['acc_id'])) $stmt->bindParam('acc_id', $_GET['acc_id'], PDO::PARAM_INT);
if ($status) $stmt->bindParam('status', $status, PDO::PARAM_STR);

$stmt->execute();
$tickets_total = $stmt->fetchColumn();
// SQL query to get all tickets from the "tickets" table
$stmt = $pdo->prepare('SELECT t.*, (SELECT COUNT(tc.id) FROM gws_legal_comments tc WHERE tc.ticket_id = t.id) AS num_comments, (SELECT GROUP_CONCAT(tu.filepath) FROM gws_legal_uploads tu WHERE tu.ticket_id = t.id) AS imgs, c.title AS category, a.username AS p_full_name, a.email AS a_email FROM gws_legal t LEFT JOIN gws_legal_categories c ON c.id = t.category_id LEFT JOIN accounts a ON t.acc_id = a.id ' . $where . ' ORDER BY ' . $order_by . ' ' . $order . ' LIMIT :start_results,:num_results');
// Bind params
$stmt->bindParam('start_results', $param1, PDO::PARAM_INT);
$stmt->bindParam('num_results', $param2, PDO::PARAM_INT);
if ($search) $stmt->bindParam('search', $param3, PDO::PARAM_STR);
if (isset($_GET['acc_id'])) $stmt->bindParam('acc_id', $_GET['acc_id'], PDO::PARAM_INT);
if ($status) $stmt->bindParam('status', $status, PDO::PARAM_STR);
 
$stmt->execute();
// Retrieve query results
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle success messages
if (isset($_GET['success_msg'])) {
    if ($_GET['success_msg'] == 1) {
        $success_msg = 'Ticket created successfully!';
    }
    if ($_GET['success_msg'] == 2) {
        $success_msg = 'Ticket updated successfully!';
    }
    if ($_GET['success_msg'] == 3) {
        $success_msg = 'Ticket deleted successfully!';
    }
    if ($_GET['success_msg'] == 4) {
        $success_msg = $_GET['imported'] . ' ticket(s) imported successfully!';
    }
}
// Determine the URL
$url = 'tickets.php?search=' . $search . (isset($_GET['page_id']) ? '&page_id=' . $_GET['page_id'] : '') . (isset($_GET['acc_id']) ? '&acc_id=' . $_GET['acc_id'] : '') . (isset($_GET['status']) ? '&status=' . $_GET['status'] : '');

//Open Tickets Data
$stmt = $pdo->prepare('SELECT t.*, (SELECT count(*) FROM gws_legal_comments tc WHERE t.id = tc.ticket_id) AS msgs, c.title AS category FROM gws_legal t LEFT JOIN gws_legal_categories c ON c.id = t.category_id WHERE t.ticket_status = "open" ORDER BY t.priority DESC, t.last_comment ASC');
$stmt->execute();
$open_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

//Open Tickets Total Count
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM gws_legal WHERE ticket_status = "open"');
$stmt->execute();
$open_tickets_total = $stmt->fetchColumn();

//Paused Tickets Total Count
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM gws_legal WHERE ticket_status = "paused"');
$stmt->execute();
$paused_tickets_total = $stmt->fetchColumn();


//Resolved project_Tickets Total Count
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM gws_legal WHERE ticket_status = "resolved" AND cast(last_update as DATE) = cast(now() as DATE)');
$stmt->execute();
$today_resolved_tickets_total = $stmt->fetchColumn();
 
//Today's Tickets Data
$stmt = $pdo->prepare('SELECT t.*, (SELECT COUNT(tc.id) FROM gws_legal_comments tc WHERE tc.ticket_id = t.id) AS num_comments, (SELECT GROUP_CONCAT(tu.filepath) FROM gws_legal_uploads tu WHERE tu.ticket_id = t.id) AS imgs, c.title AS category, a.full_name AS p_full_name, a.email AS a_email FROM gws_legal t LEFT JOIN gws_legal_categories c ON c.id = t.category_id LEFT JOIN accounts a ON t.acc_id = a.id WHERE cast(t.created as DATE) = cast(now() as DATE) ORDER BY t.priority DESC, t.created DESC');
$stmt->execute();
$new_tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Awaiting Response
$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM project_tickets WHERE (ticket_status = "open" AND last_comment = "Admin")');
$stmt->execute();
$comment_awaiting_my_response = $stmt->fetchColumn();
?>
<?=template_admin_header('Legal Filings', 'ticketing', 'legal')?>
<div class="content-title">
    <div class="title">
        <div class="icon alt">
            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21 13.34C20.37 13.12 19.7 13 19 13V5H5V18.26L6 17.6L9 19.6L12 17.6L13.04 18.29C13 18.5 13 18.76 13 19C13 19.65 13.1 20.28 13.3 20.86L12 20L9 22L6 20L3 22V3H21V13.34M17 9V7H7V9H17M15 13V11H7V13H15M18 15V18H15V20H18V23H20V20H23V18H20V15H18Z" /></svg>
        </div>
        <div class="txt">
                  <h2 class="responsive-width-100">GWS Legal Req System</h2>
            <p>Manage Requirements</p>
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
        <a href="ticket.php" class="btn">Create Requirement</a>
        <a href="comments.php" class="btn">Notes</a>
        <a href="categories.php" class="btn">Categories</a>
    </div>
    <form action="" method="get">
         <input type="hidden" name="page" value="tickets">
        <div class="filters">
             <a href="#">
                <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M0 416c0 17.7 14.3 32 32 32l54.7 0c12.3 28.3 40.5 48 73.3 48s61-19.7 73.3-48L480 448c17.7 0 32-14.3 32-32s-14.3-32-32-32l-246.7 0c-12.3-28.3-40.5-48-73.3-48s-61 19.7-73.3 48L32 384c-17.7 0-32 14.3-32 32zm128 0a32 32 0 1 1 64 0 32 32 0 1 1 -64 0zM320 256a32 32 0 1 1 64 0 32 32 0 1 1 -64 0zm32-80c-32.8 0-61 19.7-73.3 48L32 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l246.7 0c12.3 28.3 40.5 48 73.3 48s61-19.7 73.3-48l54.7 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-54.7 0c-12.3-28.3-40.5-48-73.3-48zM192 128a32 32 0 1 1 0-64 32 32 0 1 1 0 64zm73.3-64C253 35.7 224.8 16 192 16s-61 19.7-73.3 48L32 64C14.3 64 0 78.3 0 96s14.3 32 32 32l86.7 0c12.3 28.3 40.5 48 73.3 48s61-19.7 73.3-48L480 128c17.7 0 32-14.3 32-32s-14.3-32-32-32L265.3 64z"/></svg>
                Filters
            </a>
            <div class="list">
                <label><input type="radio" name="status" value="open"<?=$status=='open'?' checked':''?>>&nbsp;Open</label>
                <label><input type="radio" name="status" value="closed"<?=$status=='closed'?' checked':''?>>&nbsp;Closed</label>
                <label><input type="radio" name="status" value="resolved"<?=$status=='resolved'?' checked':''?>>&nbsp;Resolved</label>
                 <label><input type="radio" name="status" value="paused"<?=$status=='paused'?' checked':''?>>&nbsp;Paused</label>
                 
                <button type="submit">Apply</button>
            </div>
        </div>
        <div class="search">
            <label for="search">
                <input id="search" type="text" name="search" placeholder="Search ticket..." value="<?=htmlspecialchars($search, ENT_QUOTES)?>" class="responsive-width-100">
                 <svg width="14" height="14" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"/></svg>
            </label>
        </div>
    </form>
</div>
<div class="filter-list"> 
  
    <?php if ($status != ''): ?>
    <div class="filter">
        <a href="<?=remove_url_param($url, 'status')?>"><svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg></a>
        Requirement Status : <?=htmlspecialchars($status, ENT_QUOTES)?>
    </div>
    <?php endif; ?>
 
    <?php if ($search != ''): ?>
    <div class="filter">
        <a href="<?=remove_url_param($url, 'search')?>"><svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free --><path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg></a>
        Search : <?=htmlspecialchars($search, ENT_QUOTES)?>
    </div>
    <?php endif; ?>   
</div>
<div class="content-block">
    <div class="table">
        <table>
            <thead>
                <tr>
                    
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=title'?>">&nbsp;&nbsp;&nbsp;Legal Document Title<?=$order_by=='title' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=ticket_status'?>">Status<?=$order_by=='ticket_status' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=category_id'?>">Category<?=$order_by=='category_id' ? $table_icons[strtolower($order)] : ''?></a></td>
                    <td class="responsive-hidden"><a href="<?=$url . '&order=' . ($order=='ASC'?'DESC':'ASC') . '&order_by=created'?>">Created<?=$order_by=='created' ? $table_icons[strtolower($order)] : ''?></a></td>
                    
                    <td>Actions</td>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tickets)): ?>
                <tr>
                    <td colspan="20" style="text-align:center;">There are no projects
                <?php else: ?>
                <?$count=0;?>
                <?php foreach ($tickets as $ticket): ?>
                
                     
                <tr>
                      
                    <td ><?=htmlspecialchars($ticket['title'], ENT_QUOTES)?></td>
                    <td><span class="<?=$ticket['ticket_status']=='resolved'?'blue':($ticket['ticket_status']=='open'?'green':'grey')?>"><?=ucwords($ticket['ticket_status'])?></span></td>
                    <td class="responsive-hidden"><span class="grey"><?=$ticket['category']?></span></td>
                    <td class="alt responsive-hidden"><?=time_elapsed_string($ticket['created'])?></td>
                     <td class="actions">
                        <div class="table-dropdown">
                            <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M8 256a56 56 0 1 1 112 0A56 56 0 1 1 8 256zm160 0a56 56 0 1 1 112 0 56 56 0 1 1 -112 0zm216-56a56 56 0 1 1 0 112 56 56 0 1 1 0-112z"/></svg>
                            <div class="table-dropdown-items">
                                 
                                <!--VIEW-->
                                <a href="view.php?id=<?=$ticket['id']?>">
                                  <span class="icon"><svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M288 32c-80.8 0-145.5 36.8-192.6 80.6C48.6 156 17.3 208 2.5 243.7c-3.3 7.9-3.3 16.7 0 24.6C17.3 304 48.6 356 95.4 399.4C142.5 443.2 207.2 480 288 480s145.5-36.8 192.6-80.6c46.8-43.5 78.1-95.4 93-131.1c3.3-7.9 3.3-16.7 0-24.6c-14.9-35.7-46.2-87.7-93-131.1C433.5 68.8 368.8 32 288 32zM144 256a144 144 0 1 1 288 0 144 144 0 1 1 -288 0zm144-64c0 35.3-28.7 64-64 64c-7.1 0-13.9-1.2-20.3-3.3c-5.5-1.8-11.9 1.6-11.7 7.4c.3 6.9 1.3 13.8 3.2 20.7c13.7 51.2 66.4 81.6 117.6 67.9s81.6-66.4 67.9-117.6c-11.1-41.5-47.8-69.4-88.6-71.1c-5.8-.2-9.2 6.1-7.4 11.7c2.1 6.4 3.3 13.2 3.3 20.3z"/></svg></span>
                                   View</a>
                                    
                                    
                                <!--EDIT-->
                                    <a href="ticket.php?id=<?=$ticket['id']?>">
                                    <span class="icon"><svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M471.6 21.7c-21.9-21.9-57.3-21.9-79.2 0L362.3 51.7l97.9 97.9 30.1-30.1c21.9-21.9 21.9-57.3 0-79.2L471.6 21.7zm-299.2 220c-6.1 6.1-10.8 13.6-13.5 21.9l-29.6 88.8c-2.9 8.6-.6 18.1 5.8 24.6s15.9 8.7 24.6 5.8l88.8-29.6c8.2-2.7 15.7-7.4 21.9-13.5L437.7 172.3 339.7 74.3 172.4 241.7zM96 64C43 64 0 107 0 160V416c0 53 43 96 96 96H352c53 0 96-43 96-96V320c0-17.7-14.3-32-32-32s-32 14.3-32 32v96c0 17.7-14.3 32-32 32H96c-17.7 0-32-14.3-32-32V160c0-17.7 14.3-32 32-32h96c17.7 0 32-14.3 32-32s-14.3-32-32-32H96z"/></svg></span>
                                    Edit</a>
                                   
                                    
                                <!--DELETE-->
                                    <a class="red" href="tickets.php?delete=<?=$ticket['id']?>" onclick="return confirm('Are you sure you want to delete this ticket?')">
                                    <span class="icon"><svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z"/></svg></span>    
                                    Delete</a>
                                   
                                    
                            <?php if ($ticket['approved'] != 1): ?>
                            
                                  <a href="tickets.php?approve=<?=$ticket['id']?>" onclick="return confirm('Are you sure you want to approve this ticket?')">
                                   <span class="icon"><svg width="12" height="12" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M20,8L12,13L4,8V6L12,11L20,6M20,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V6C22,4.89 21.1,4 20,4Z" /></svg></span>
                                   Approve</a>     
                                   
                           <?php endif; ?>
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
    <span>Page <?=$pagination_page?> of <?=ceil($tickets_total / $results_per_page) == 0 ? 1 : ceil($tickets_total / $results_per_page)?></span>
    <?php if ($pagination_page * $results_per_page < $tickets_total): ?>
    <a href="<?=$url?>&pagination_page=<?=$pagination_page+1?>&order=<?=$order?>&order_by=<?=$order_by?>">Next</a>
    <?php endif; ?>
</div>
<?=template_admin_footer()?> 