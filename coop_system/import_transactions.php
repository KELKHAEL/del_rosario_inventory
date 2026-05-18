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

    // NEW SPLIT NAME LOGIC (Automatically detects middle name if > 2 words)
    function splitName($fullName) {
        $cleanName = preg_replace('/\s+/', ' ', trim($fullName)); 
        $last = ''; $first = ''; $middle = '';
        
        if (strpos($cleanName, ',') !== false) {
            $parts = explode(',', $cleanName, 2);
            $last = trim($parts[0]);
            $f_parts = explode(' ', trim($parts[1]));
            if (count($f_parts) >= 3) {
                $middle = array_pop($f_parts); // Plucks the last word to be the middle name
            }
            $first = implode(' ', $f_parts);
        } else {
            $parts = explode(' ', $cleanName);
            $last = count($parts) > 1 ? array_pop($parts) : $cleanName;
            if (count($parts) >= 3) {
                $middle = array_pop($parts);
            }
            $first = implode(' ', $parts);
        }
        return [strtoupper($last), strtoupper($first), strtoupper($middle)];
    }

    // Smart Multi-line cell parser
    function buildItemDetails($qty_raw, $desc_raw, $price_raw, $amt_raw) {
        $qtys = explode("\n", trim((string)$qty_raw));
        $descs = explode("\n", trim((string)$desc_raw));
        $prices = explode("\n", trim((string)$price_raw));
        $amts = explode("\n", trim((string)$amt_raw));

        $max_lines = max(count($qtys), count($descs), count($prices), count($amts));
        $lines = [];
        for($j = 0; $j < $max_lines; $j++) {
            $q = isset($qtys[$j]) ? trim($qtys[$j]) : '';
            $d = isset($descs[$j]) ? trim($descs[$j]) : '';
            $p = isset($prices[$j]) ? trim($prices[$j]) : '';
            $a = isset($amts[$j]) ? trim($amts[$j]) : '';
            
            $part = [];
            if($q !== '') $part[] = $q . "x";
            if($d !== '') $part[] = $d;
            if($p !== '') $part[] = "@ ₱" . str_replace('₱', '', $p);
            if($a !== '') $part[] = "= ₱" . str_replace('₱', '', $a);
            
            if(!empty($part)) {
                $lines[] = implode(' ', $part);
            }
        }
        return implode("\n", $lines);
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

    // 4. PARSE ROWS & INSERT INTO DB
    for ($i = $start_row; $i < count($rows); $i++) {
        $row = $rows[$i];
        if (!is_array($row)) continue;

        $raw_date = getVal($row, $excel_map, 'date');
        $member_name = getVal($row, $excel_map, 'member_name');
        
        if (empty($raw_date) && empty($member_name)) continue;

        $t_date = parseDate($raw_date) ?: date('Y-m-d');
        $qty = getVal($row, $excel_map, 'qty');
        $desc = getVal($row, $excel_map, 'item_desc');
        $price = getVal($row, $excel_map, 'price');
        $item_amount = getVal($row, $excel_map, 'item_amount');
        
        $total = cleanNumber(getVal($row, $excel_map, 'total_amount'));
        $dp = cleanNumber(getVal($row, $excel_map, 'downpayment'));
        $invoice = getVal($row, $excel_map, 'invoice');
        $balance = cleanNumber(getVal($row, $excel_map, 'balance'));
        $status = strtoupper(getVal($row, $excel_map, 'status'));

        $items_details = buildItemDetails($qty, $desc, $price, $item_amount);

        // --- NEW FUZZY SEARCH ALGORITHM ---
        $member_id = null;
        if (!empty($member_name)) {
            list($last, $first, $middle) = splitName($member_name);
            
            // Step 1: Find all members with the exact Last Name
            $stmt = $conn->prepare("SELECT member_id, first_name FROM members WHERE last_name = ?");
            $stmt->bind_param("s", $last);
            $stmt->execute();
            $res = $stmt->get_result();
            
            $best_match_id = null;
            $highest_sim = 0;
            
            if ($res->num_rows > 0) {
                while($db_row = $res->fetch_assoc()) {
                    $db_first = strtoupper(trim($db_row['first_name']));
                    if ($db_first === $first) {
                        $best_match_id = $db_row['member_id'];
                        $highest_sim = 100;
                        break;
                    }
                    
                    // Compare typo similarity using Levenshtein / similar_text logic
                    similar_text($first, $db_first, $percent);
                    if ($percent > $highest_sim) {
                        $highest_sim = $percent;
                        $best_match_id = $db_row['member_id'];
                    }
                }
            } else {
                // Step 2 Fallback: If last name has a typo, compare the full string against all members
                $all_stmt = $conn->query("SELECT member_id, last_name, first_name FROM members");
                while($db_row = $all_stmt->fetch_assoc()) {
                    $db_full = strtoupper(trim($db_row['last_name'] . ', ' . $db_row['first_name']));
                    $excel_full = strtoupper(trim($last . ', ' . $first));
                    similar_text($excel_full, $db_full, $percent);
                    if ($percent > $highest_sim) {
                        $highest_sim = $percent;
                        $best_match_id = $db_row['member_id'];
                    }
                }
            }
            if (isset($stmt)) $stmt->close();
            
            // If the math determines they are at least a 70% match, it links them!
            if ($highest_sim >= 70 && $best_match_id !== null) {
                $member_id = $best_match_id;
            }
        }

        $t_type = "PURCHASE";
        if (stripos($items_details, 'share') !== false || stripos($member_name, 'share') !== false) {
            $t_type = "SHARE";
        }

        // Avoid Duplicates by checking date, name, and invoice
        $check = $conn->prepare("SELECT transaction_id FROM transactions WHERE transaction_date = ? AND member_name = ? AND invoice_no = ?");
        $check->bind_param("sss", $t_date, $member_name, $invoice);
        $check->execute();
        $c_res = $check->get_result();

        if ($c_res->num_rows > 0) {
            $tid = $c_res->fetch_assoc()['transaction_id'];
            $upd = $conn->prepare("UPDATE transactions SET member_id=?, items_details=?, payment_status=?, downpayment=?, remaining_balance=?, amount=? WHERE transaction_id=?");
            $upd->bind_param("issdddi", $member_id, $items_details, $status, $dp, $balance, $total, $tid);
            $upd->execute();
        } else {
            $ins = $conn->prepare("INSERT INTO transactions (transaction_date, member_id, member_name, transaction_type, amount, items_details, invoice_no, payment_status, downpayment, remaining_balance) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $ins->bind_param("sissssssdd", $t_date, $member_id, $member_name, $t_type, $total, $items_details, $invoice, $status, $dp, $balance);
            $ins->execute();
        }
    }

    $_SESSION['alert_title'] = "Transactions Uploaded";
    $_SESSION['alert_message'] = "The Excel file was parsed and transactions were linked to existing members automatically, even correcting minor spelling typos!";
    $_SESSION['alert_type'] = "success";
    
    header("Location: transactions.php");
    exit();
}
?>