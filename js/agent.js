
  document.addEventListener('DOMContentLoaded', function () {
    const updatePhotoLink = document.getElementById('updatePhotoLink');
    const profileInput = document.getElementById('profileInput');
    const profilePreview = document.getElementById('profilePreview');

    if (updatePhotoLink && profileInput && profilePreview) {
      // When "Update photo" is clicked
      updatePhotoLink.addEventListener('click', function (e) {
        e.preventDefault();
        profileInput.click(); // Trigger hidden file input
      });

      // When a file is selected
      profileInput.addEventListener('change', function () {
        const file = this.files[0];
        if (file) {
          const reader = new FileReader();
          reader.onload = function (e) {
            profilePreview.src = e.target.result;
          };
          reader.readAsDataURL(file);
        }
      });
    }
  });

   // SHOW ON SCHEDULE TOUR MODAL
    window.addEventListener("DOMContentLoaded", function () {
        const date = localStorage.getItem("viewDate");
        const time = localStorage.getItem("viewTime");

        if (name && date && time) {
            document.getElementById("modalViewDate").textContent = date;
            document.getElementById("modalViewTime").textContent = time;

            // Optionally clear the storage after use:
            // localStorage.removeItem("buyerName");
            // localStorage.removeItem("viewDate");
            // localStorage.removeItem("viewTime");

            // Open the modal automatically
            const modal = new bootstrap.Modal(document.getElementById('propertyModal'));
            modal.show();
        }
    });

