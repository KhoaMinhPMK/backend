<?php
// Utility function to generate transaction code
// Format: DDXXXXXXXXXX MMYY (16 digits total)
// DD = day, XXXXXXXXXX = sequence (10 digits), MM = month, YY = year

function generateTransactionCode($conn) {
    // Get current date
    $now = new DateTime();
    $day = $now->format('d');      // 2 digits: 01-31
    $month = $now->format('m');    // 2 digits: 01-12
    $year = $now->format('y');     // 2 digits: 24 for 2024
    
    // Get next sequence number from database
    $sequenceNumber = getNextSequenceNumber($conn);
    
    // Format sequence as 10 digits with leading zeros
    $sequence = str_pad($sequenceNumber, 10, '0', STR_PAD_LEFT);
    
    // Combine: DD + XXXXXXXXXX + MMYY
    $transactionCode = $day . $sequence . $month . $year;
    
    return $transactionCode;
}

function getNextSequenceNumber($conn) {
    try {
        // Get current date for filtering transactions from same day
        $today = date('Y-m-d');
        
        // Get highest sequence number from today's transactions
        $sql = "SELECT premium_key FROM premium_subscriptions_json 
                WHERE DATE(start_date) = ? 
                ORDER BY premium_key DESC 
                LIMIT 1";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$today]);
        $lastTransaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($lastTransaction) {
            // Extract sequence from last transaction code
            // Format: DDXXXXXXXXXX MMYY
            $lastCode = $lastTransaction['premium_key'];
            if (strlen($lastCode) === 16) {
                // Extract 10-digit sequence (positions 2-11)
                $lastSequence = (int)substr($lastCode, 2, 10);
                return $lastSequence + 1;
            }
        }
        
        // If no transactions today or invalid format, start from 1
        return 1;
        
    } catch (Exception $e) {
        // If any error, start from 1
        error_log("Error getting sequence number: " . $e->getMessage());
        return 1;
    }
}

// Test function
function testTransactionCodeGeneration() {
    return [
        'format' => 'DDXXXXXXXXXX MMYY (16 digits)',
        'example' => [
            'day' => '14',
            'sequence' => '0000000001',
            'month_year' => '0225',
            'full_code' => '1400000000010225'
        ],
        'description' => [
            'DD' => 'Day (01-31)',
            'XXXXXXXXXX' => 'Sequence number (10 digits with leading zeros)',
            'MM' => 'Month (01-12)', 
            'YY' => 'Year (24 for 2024, 25 for 2025)'
        ]
    ];
}
?> 