<?php
require "show.php";
$query = mysql_query("select * from 'glitchwizarddigi_onthego' . 'not_important'");
while (($row = mysql_fetch_assoc($query)) !== false) {
    echo $row ['resource'], '<br>';
}