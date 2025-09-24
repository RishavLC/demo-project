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