<?php
// 2024-12-09 Production.
// 2025-06-15 Reworked. VERIFIED.
include '../../client-invoices/defines.php';
// Get the total number of accounts
$stmt = $pdo->query('SELECT COUNT(*) FROM accounts');
$accounts_total = $stmt->fetchColumn();
// Get the total number of events
$stmt = $pdo->query('SELECT COUNT(*) FROM invoices');
$invoices_total = $stmt->fetchColumn();
// Get the total number of clients
$stmt = $pdo->query('SELECT COUNT(*) FROM invoice_clients');
$clients_total = $stmt->fetchColumn();
// Icons for the table headers
$table_icons = [
    'asc' => '<svg width="10" height="10" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M350 177.5c3.8-8.8 2-19-4.6-26l-136-144C204.9 2.7 198.6 0 192 0s-12.9 2.7-17.4 7.5l-136 144c-6.6 7-8.4 17.2-4.6 26s12.5 14.5 22 14.5h88l0 192c0 17.7-14.3 32-32 32H32c-17.7 0-32 14.3-32 32v32c0 17.7 14.3 32 32 32l80 0c70.7 0 128-57.3 128-128l0-192h88c9.6 0 18.2-5.7 22-14.5z"/></svg>',
    'desc' => '<svg width="10" height="10" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M350 334.5c3.8 8.8 2 19-4.6 26l-136 144c-4.5 4.8-10.8 7.5-17.4 7.5s-12.9-2.7-17.4-7.5l-136-144c-6.6-7-8.4-17.2-4.6-26s12.5-14.5 22-14.5h88l0-192c0-17.7-14.3-32-32-32H32C14.3 96 0 81.7 0 64V32C0 14.3 14.3 0 32 0l80 0c70.7 0 128 57.3 128 128l0 192h88c9.6 0 18.2 5.7 22 14.5z"/></svg>'
];
// Remove param from URL function
function remove_url_param($url, $param) {
    $url = preg_replace('/(&|\?)'.preg_quote($param).'=[^&]*$/', '', $url);
    $url = preg_replace('/(&|\?)'.preg_quote($param).'=[^&]*&/', '$1', $url);
    return $url;
}
// Get country list
function get_countries() {
    return ["United States", "United States Minor Outlying Islands", "United Kingdom", "American Samoa", "Canada", ];
    
    /*["Afghanistan", "Albania", "Algeria", "American Samoa", "Andorra", "Angola", "Anguilla", "Antarctica", "Antigua and Barbuda", "Argentina", "Armenia", "Aruba", "Australia", "Austria", "Azerbaijan", "Bahamas", "Bahrain", "Bangladesh", "Barbados", "Belarus", "Belgium", "Belize", "Benin", "Bermuda", "Bhutan", "Bolivia", "Bosnia and Herzegowina", "Botswana", "Bouvet Island", "Brazil", "British Indian Ocean Territory", "Brunei Darussalam", "Bulgaria", "Burkina Faso", "Burundi", "Cambodia", "Cameroon", "Canada", "Cape Verde", "Cayman Islands", "Central African Republic", "Chad", "Chile", "China", "Christmas Island", "Cocos (Keeling) Islands", "Colombia", "Comoros", "Congo", "Congo, the Democratic Republic of the", "Cook Islands", "Costa Rica", "Cote d'Ivoire", "Croatia (Hrvatska)", "Cuba", "Cyprus", "Czech Republic", "Denmark", "Djibouti", "Dominica", "Dominican Republic", "East Timor", "Ecuador", "Egypt", "El Salvador", "Equatorial Guinea", "Eritrea", "Estonia", "Ethiopia", "Falkland Islands (Malvinas)", "Faroe Islands", "Fiji", "Finland", "France", "France Metropolitan", "French Guiana", "French Polynesia", "French Southern Territories", "Gabon", "Gambia", "Georgia", "Germany", "Ghana", "Gibraltar", "Greece", "Greenland", "Grenada", "Guadeloupe", "Guam", "Guatemala", "Guinea", "Guinea-Bissau", "Guyana", "Haiti", "Heard and Mc Donald Islands", "Holy See (Vatican City State)", "Honduras", "Hong Kong", "Hungary", "Iceland", "India", "Indonesia", "Iran (Islamic Republic of)", "Iraq", "Ireland", "Israel", "Italy", "Jamaica", "Japan", "Jordan", "Kazakhstan", "Kenya", "Kiribati", "Korea, Democratic People's Republic of", "Korea, Republic of", "Kuwait", "Kyrgyzstan", "Lao, People's Democratic Republic", "Latvia", "Lebanon", "Lesotho", "Liberia", "Libyan Arab Jamahiriya", "Liechtenstein", "Lithuania", "Luxembourg", "Macau", "Macedonia, The Former Yugoslav Republic of", "Madagascar", "Malawi", "Malaysia", "Maldives", "Mali", "Malta", "Marshall Islands", "Martinique", "Mauritania", "Mauritius", "Mayotte", "Mexico", "Micronesia, Federated States of", "Moldova, Republic of", "Monaco", "Mongolia", "Montserrat", "Morocco", "Mozambique", "Myanmar", "Namibia", "Nauru", "Nepal", "Netherlands", "Netherlands Antilles", "New Caledonia", "New Zealand", "Nicaragua", "Niger", "Nigeria", "Niue", "Norfolk Island", "Northern Mariana Islands", "Norway", "Oman", "Pakistan", "Palau", "Panama", "Papua New Guinea", "Paraguay", "Peru", "Philippines", "Pitcairn", "Poland", "Portugal", "Puerto Rico", "Qatar", "Reunion", "Romania", "Russian Federation", "Rwanda", "Saint Kitts and Nevis", "Saint Lucia", "Saint Vincent and the Grenadines", "Samoa", "San Marino", "Sao Tome and Principe", "Saudi Arabia", "Senegal", "Seychelles", "Sierra Leone", "Singapore", "Slovakia (Slovak Republic)", "Slovenia", "Solomon Islands", "Somalia", "South Africa", "South Georgia and the South Sandwich Islands", "Spain", "Sri Lanka", "St. Helena", "St. Pierre and Miquelon", "Sudan", "Suriname", "Svalbard and Jan Mayen Islands", "Swaziland", "Sweden", "Switzerland", "Syrian Arab Republic", "Taiwan, Province of China", "Tajikistan", "Tanzania, United Republic of", "Thailand", "Togo", "Tokelau", "Tonga", "Trinidad and Tobago", "Tunisia", "Turkey", "Turkmenistan", "Turks and Caicos Islands", "Tuvalu", "Uganda", "Ukraine", "United Arab Emirates", "United Kingdom", "United States", "United States Minor Outlying Islands", "Uruguay", "Uzbekistan", "Vanuatu", "Venezuela", "Vietnam", "Virgin Islands (British)", "Virgin Islands (U.S.)", "Wallis and Futuna Islands", "Western Sahara", "Yemen", "Yugoslavia", "Zambia", "Zimbabwe"];*/
    
}
// Copy directory function
function copy_directory($source, $destination) {
    if (is_dir($source)) {
        @mkdir($destination);
        $directory = dir($source);
        while (false !== ($readdirectory = $directory->read())) {
            if ($readdirectory == '.' || $readdirectory == '..') {
                continue;
            }
            $PathDir = $source . '/' . $readdirectory;
            if (is_dir($PathDir)) {
                copy_directory($PathDir, $destination . '/' . $readdirectory);
                continue;
            }
            copy($PathDir, $destination . '/' . $readdirectory);
        }
        $directory->close();
    } else {
        copy($source, $destination);
    }
}
// Add transactions items to the database
function addItems($pdo, $invoice_number) {
    if (isset($_POST['item_id']) && is_array($_POST['item_id']) && count($_POST['item_id']) > 0) {
        // Iterate items
        $delete_list = [];
        for ($i = 0; $i < count($_POST['item_id']); $i++) {
            // If the item doesnt exist in the database
            if (!intval($_POST['item_id'][$i])) {
                // Insert new item
                $stmt = $pdo->prepare('INSERT INTO invoice_items (invoice_number, item_name, item_description, item_price, item_quantity) VALUES (?,?,?,?,?)');
                $stmt->execute([ $invoice_number, $_POST['item_name'][$i], $_POST['item_description'][$i], $_POST['item_price'][$i], $_POST['item_quantity'][$i] ]);
                $delete_list[] = $pdo->lastInsertId();
            } else {
                // Update existing item
                $stmt = $pdo->prepare('UPDATE invoice_items SET invoice_number = ?, item_name = ?, item_description = ?, item_price = ?, item_quantity = ? WHERE id = ?');
                $stmt->execute([ $invoice_number, $_POST['item_name'][$i], $_POST['item_description'][$i], $_POST['item_price'][$i], $_POST['item_quantity'][$i], $_POST['item_id'][$i] ]);
                $delete_list[] = $_POST['item_id'][$i];          
            }
        }
        // Delete item
        $in  = str_repeat('?,', count($delete_list) - 1) . '?';
        $stmt = $pdo->prepare('DELETE FROM invoice_items WHERE invoice_number = ? AND id NOT IN (' . $in . ')');
        $stmt->execute(array_merge([ $invoice_number ], $delete_list));
    } else {
        // No item exists, delete all
        $stmt = $pdo->prepare('DELETE FROM invoice_items WHERE invoice_number = ?');
        $stmt->execute([ $invoice_number ]);       
    }
}
// Create invoice PDF function
function create_invoice_pdf($invoice, $invoice_items, $client) {
    define('INVOICE', true);
    // Client address
    $client_address = [
        $client['address_street'],
        $client['address_city'],
        $client['address_state'],
        $client['address_zip'],
        $client['address_country']
    ];
    // remove any empty values
    $client_address = array_filter($client_address);
    // Get payment methods
    $payment_methods = explode(', ', $invoice['payment_methods']);
    // Include the template
    if (file_exists(base_path . 'templates/' . $invoice['invoice_template'] . '/template-pdf.php')) {
        require base_path . 'templates/' . $invoice['invoice_template'] . '/template-pdf.php';
        // Save the output to a file
        $pdf->Output(base_path . 'pdfs/' . $invoice['invoice_number'] . '.pdf', 'F');
        return true;
    } else if (file_exists(base_path . 'templates/default/template-pdf.php')) {
        require base_path . 'templates/default/template-pdf.php';
        // Save the output to a file
        $pdf->Output(base_path . 'pdfs/' . $invoice['invoice_number'] . '.pdf', 'F');
        return true;
    } 
    return false;
}
?>