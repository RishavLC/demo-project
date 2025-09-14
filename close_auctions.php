<?php
include "config.php";

// ✅ Step 1: Fetch all expired auctions
$sql = "SELECT id FROM auction_items 
        WHERE end_time < NOW() AND status = 'active'";
$result = $conn->query($sql);

while ($auction = $result->fetch_assoc()) {
    $auction_id = $auction['id'];

    // ✅ Step 2: Find highest bid
    $stmt = $conn->prepare("SELECT bidder_id, bid_amount 
                            FROM bids 
                            WHERE item_id=? 
                            ORDER BY bid_amount DESC 
                            LIMIT 1");
    $stmt->bind_param("i", $auction_id);
    $stmt->execute();
    $bidResult = $stmt->get_result();

    if ($bidResult->num_rows > 0) {
        $topBid = $bidResult->fetch_assoc();
        $winner_id = $topBid['bidder_id'];
        $winning_amount = $topBid['bid_amount'];

        // ✅ Step 3: Update auction with winner
        $update = $conn->prepare("UPDATE auction_items 
                                  SET winner_id=?, current_price=?, status='sold' 
                                  WHERE id=?");
        $update->bind_param("idi", $winner_id, $winning_amount, $auction_id);
        $update->execute();
    } else {
        // ❌ No bids → mark as closed
        $conn->query("UPDATE auction_items 
                      SET status='closed' 
                      WHERE id=$auction_id");
    }
}
echo "✅ Auction status updated.";
?>
