document.addEventListener("DOMContentLoaded", () => {
  const toggleBtn = document.querySelector(".toggle-btn");
  const sidebar = document.querySelector(".sidebar");

  toggleBtn.addEventListener("click", () => {
    sidebar.classList.toggle("collapsed");
  });
});
//notification drop down
function toggleDropdown() {
  var dropdown = document.getElementById("notificationDropdown");
  dropdown.style.display = (dropdown.style.display === "block") ? "none" : "block";
  }

// Close dropdown if clicked outside
window.onclick = function(e) {
  if (!e.target.closest('.notification-wrapper')) {
    document.getElementById("notificationDropdown").style.display = "none";
    }
  }

  //Active item details
function openAuctionModal(title, description, seller, price, endTime, auctionId) {
  document.getElementById("modalTitle").innerText = title;
  document.getElementById("modalDescription").innerText = description;
  document.getElementById("modalSeller").innerText = seller;
  document.getElementById("modalPrice").innerText = price;
  document.getElementById("modalEnd").innerText = endTime;

  // Fetch highest bid via AJAX
  fetch("get_highest_bid.php?auction_id=" + auctionId)
    .then(response => response.json())
    .then(data => {
      document.getElementById("modalHighest").innerText = data.highest ? data.highest : "No bids yet";
    });

  // Set bid link
  document.getElementById("bidLink").href = "auction_bid.php?auction_id=" + auctionId;

  // Show modal
  document.getElementById("auctionModal").style.display = "flex";
}

function closeAuctionModal() {
  document.getElementById("auctionModal").style.display = "none";
}

// Close modal when clicking outside
window.onclick = function(e) {
  if (e.target.classList.contains("modal")) {
    closeAuctionModal();
  }
}
