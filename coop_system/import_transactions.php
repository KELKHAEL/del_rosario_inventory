<?php
session_start();
include 'db.php';
require 'vendor/autoload.php'; 
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date; 

// --- AUTO-UPGRADE DATABASE SCHEMA ---
$checkCols = $conn->query("SHOW COLUMNS FROM transactions LIKE 'member_id'");
if($checkCols->num_rows == 0) {
    $conn->query("ALTER TABLE transactions ADD COLUMN member_id INT(11) NULL AFTER transaction_id");
    $conn->query("ALTER TABLE transactions ADD COLUMN items_details TEXT NULL AFTER amount");
    $conn->query("ALTER TABLE transactions ADD COLUMN invoice_no VARCHAR(100) NULL AFTER items_details");
    $conn->query("ALTER TABLE transactions ADD COLUMN payment_status VARCHAR(50) NULL AFTER invoice_no");
    $conn->query("ALTER TABLE transactions ADD COLUMN downpayment DECIMAL(10,2) NULL AFTER payment_status");
    $conn->query("ALTER TABLE transactions ADD COLUMN remaining_balance DECIMAL(10,2) NULL AFTER downpayment");
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['excel_file'])) {
    
    $fileTmpPath = $_FILES['excel_file']['tmp_name'];
    $spreadsheet = IOFactory::load($fileTmpPath);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();

    // 1. HEADER ALIASES
    $header_aliases = [
        'date'         => ['dateoftransaction', 'date', 'transactiondate'],
        'member_name'  => ['paccmembername', 'membername', 'name', 'customer'],
        'qty'          => ['quantity', 'qty'],
        'item_desc'    => ['itemdescription', 'description', 'item', 'items'],
        'price'        => ['sellingprice', 'price', 'unitprice'],
        'item_amount'  => ['amountofitem', 'itemamount'],
        'total_amount' => ['totalamount', 'total', 'amount'],
        'payment_date' => ['dateofpayment', 'paymentdate'],
        'downpayment'  => ['downpaymentamount', 'downpayment', 'dp'],
        'invoice'      => ['invoice', 'invoiceno', 'receipt'],
        'balance'      => ['remainingbalance', 'balance', 'remaining'],
        'status'       => ['paymentstatus', 'status']
    ];

    // 2. HELPER FUNCTIONS
    function parseDate($input) {
        $input = trim((string)$input);
        if (empty($input) || $input == '-') return null;
        if (is_numeric($input)) return date('Y-m-d', Date::excelToTimestamp((float)$input));
        $time = strtotime($input);
        if ($time !== false) return date('Y-m-d', $time);
        return null;
    }

    function cleanNumber($input) {
        $val = preg_replace('/[^0-9\.\-]/', '', (string)$input);
        return $val === '' ? 0 : (float)$val;
    }

    // Line builder for single rows
    function buildItemLine($qty, $desc, $price, $amt) {
        $q = trim((string)$qty);
        $d = trim((string)$desc);
        $p = trim((string)$price);
        $a = trim((string)$amt);
        
        if($q === '' && $d === '' && $p === '' && $a === '') return null;
        
        $part = [];
        if($q !== '') $part[] = $q . "x";
        if($d !== '') $part[] = $d;
        if($p !== '') $part[] = "@ ₱" . str_replace('₱', '', $p);
        if($a !== '') $part[] = "= ₱" . str_replace('₱', '', $a);
        
        return implode(' ', $part);
    }

    // 3. DETECT HEADERS
    $excel_map = [];
    $start_row = 1;
    for ($i = 0; $i < min(10, count($rows)); $i++) {
        $is_header = false;
        foreach($rows[$i] as $col_idx => $col_name) {
            $clean_col = preg_replace('/[^a-z0-9]/', '', strtolower((string)$col_name));
            if (!empty($clean_col)) {
                foreach ($header_aliases as $sys_field => $aliases) {
                    if (in_array($clean_col, $aliases)) {
                        $excel_map[$sys_field] = $col_idx;
                        if ($sys_field === 'member_name' || $sys_field === 'date') $is_header = true;
                        break;
                    }
                }
            }
        }
        if ($is_header) {
            $start_row = $i + 1; break;
        }
    }

    function getVal($row, $map, $field) {
        return isset($map[$field]) && isset($row[$map[$field]]) ? trim((string)$row[$map[$field]]) : '';
    }

    // 4. MULTI-ROW GROUPING ENGINE
    $transactions_to_save = [];
    $current_idx = -1;

    for ($i = $start_row; $i < count($rows); $i++) {
        $row = $rows[$i];
        if (!is_array($row)) continue;

        $cell_name = getVal($row, $excel_map, 'member_name');
        $cell_date = getVal($row, $excel_map, 'date');

        // If the row has a name or date, it is a NEW transaction block
        if (!empty($cell_name) || !empty($cell_date)) {
            $current_idx++;
            $transactions_to_save[$current_idx] = [
                'date'         => parseDate($cell_date) ?: date('Y-m-d'),
                'member_name'  => $cell_name,
                'total_amount' => cleanNumber(getVal($row, $excel_map, 'total_amount')),
                'downpayment'  => cleanNumber(getVal($row, $excel_map, 'downpayment')),
                'invoice'      => getVal($row, $excel_map, 'invoice'),
                'balance'      => cleanNumber(getVal($row, $excel_map, 'balance')),
                'status'       => strtoupper(getVal($row, $excel_map, 'status')),
                'items'        => [] // We will push items into this array
            ];
        }

        // Always extract item details for the current block (even on blank sub-rows)
        if ($current_idx >= 0) {
            $qty   = getVal($row, $excel_map, 'qty');
            $desc  = getVal($row, $excel_map, 'item_desc');
            $price = getVal($row, $excel_map, 'price');
            $amt   = getVal($row, $excel_map, 'item_amount');
            
            $item_line = buildItemLine($qty, $desc, $price, $amt);
            if ($item_line !== null) {
                $transactions_to_save[$current_idx]['items'][] = $item_line;
            }
        }
    }

    // 5. CACHE ALL MEMBERS FOR FAST FUZZY MATCHING
    $all_stmt = $conn->query("SELECT member_id, last_name, first_name, middle_name FROM members");
    $all_members = [];
    while($db_row = $all_stmt->fetch_assoc()) {
        $db_full = strtoupper($db_row['last_name'] . $db_row['first_name'] . $db_row['middle_name']);
        $db_normalized = preg_replace('/[^A-Z0-9]/', '', $db_full); // strips spaces/commas
        $all_members[] = [
            'id'         => $db_row['member_id'],
            'normalized' => $db_normalized
        ];
    }

    // 6. SAVE TO DATABASE
    foreach ($transactions_to_save as $txn) {
        if (empty($txn['member_name'])) continue;

        // Merge all items into a single beautiful receipt string separated by newlines
        $items_str = implode("\n", $txn['items']);

        // --- THE ULTIMATE FUZZY MATCHING ALGORITHM ---
        $member_id = null;
        $excel_normalized = strtoupper(preg_replace('/[^A-Z0-9]/', '', $txn['member_name']));
        $highest_sim = 0;
        $best_match_id = null;
        
        foreach ($all_members as $m) {
            if ($excel_normalized === $m['normalized']) {
                $best_match_id = $m['id'];
                $highest_sim = 100;
                break;
            }
            // Compares spelling accuracy (e.g. Amarana vs Amrana)
            similar_text($excel_normalized, $m['normalized'], $percent);
            if ($percent > $highest_sim) {
                $highest_sim = $percent;
                $best_match_id = $m['id'];
            }
        }
        
        // If the math determines they are at least a 70% match, securely link the member ID!
        if ($highest_sim >= 70 && $best_match_id !== null) {
            $member_id = $best_match_id;
        }

        $t_type = "PURCHASE";
        if (stripos($items_str, 'share') !== false || stripos($txn['member_name'], 'share') !== false) {
            $t_type = "SHARE";
        }

        // Avoid Duplicates
        $check = $conn->prepare("SELECT transaction_id FROM transactions WHERE transaction_date = ? AND member_name = ? AND invoice_no = ?");
        $check->bind_param("sss", $txn['date'], $txn['member_name'], $txn['invoice']);
        $check->execute();
        $c_res = $check->get_result();

        if ($c_res->num_rows > 0) {
            $tid = $c_res->fetch_assoc()['transaction_id'];
            $upd = $conn->prepare("UPDATE transactions SET member_id=?, items_details=?, payment_status=?, downpayment=?, remaining_balance=?, amount=? WHERE transaction_id=?");
            $upd->bind_param("issdddi", $member_id, $items_str, $txn['status'], $txn['downpayment'], $txn['balance'], $txn['total_amount'], $tid);
            $upd->execute();
        } else {
            $ins = $conn->prepare("INSERT INTO transactions (transaction_date, member_id, member_name, transaction_type, amount, items_details, invoice_no, payment_status, downpayment, remaining_balance) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $ins->bind_param("sissssssdd", $txn['date'], $member_id, $txn['member_name'], $t_type, $txn['total_amount'], $items_str, $txn['invoice'], $txn['status'], $txn['downpayment'], $txn['balance']);
            $ins->execute();
        }
    }

    $_SESSION['alert_title'] = "Transactions Uploaded";
    $_SESSION['alert_message'] = "The Excel file was parsed! All multi-row items were grouped together and securely linked to the correct members using Fuzzy Matching.";
    $_SESSION['alert_type'] = "success";
    
    header("Location: transactions.php");
    exit();
}
?>