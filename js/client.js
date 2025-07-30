 document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('updateClientPhotoLink').addEventListener('click', function (e) {
            e.preventDefault();
            document.getElementById('profileInput').click();
        });

        document.getElementById('profileInput').addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('profilePreview').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    });

    function openNotificationsTab() {
    const dropdown = bootstrap.Dropdown.getInstance(document.getElementById('notificationDropdown'));
    if (dropdown) dropdown.hide();

    const notifTab = document.querySelector('#v-pills-notifications-tab');
    if (notifTab) {
      notifTab.click();
    }
  }

  function toggleSave(icon) {
  icon.classList.toggle('bi-bookmark');
  icon.classList.toggle('bi-bookmark-fill');
  icon.style.color = icon.classList.contains('bi-bookmark-fill') ? 'blue' : 'black';
}


  const previewModal = document.getElementById('newListingModal');
  previewModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;

    document.getElementById('newListingModalLabel').textContent = button.getAttribute('data-title');
    document.getElementById('modalImage').src = button.getAttribute('data-img');
    document.getElementById('modalLocation').textContent = button.getAttribute('data-location');
    document.getElementById('modalType').textContent = button.getAttribute('data-type');
    document.getElementById('modalFeatures').textContent = button.getAttribute('data-features');
    document.getElementById('modalPrice').textContent = button.getAttribute('data-price');
  });

  function openInspectionForm() {
    const modal = new bootstrap.Modal(document.getElementById('newListingFormModal'));
    modal.show();
  }

  function showLoginPrompt(event) {
    event.preventDefault(); // prevent form from submitting normally
    const modal = new bootstrap.Modal(document.getElementById('loginPromptModal'));
    modal.show();
    document.getElementById('newListingFormModal').classList.remove('show');
    document.querySelector('#newListingFormModal .modal-backdrop')?.remove();
  }


  // Form validation
  function showLoginPrompt(event) {
    event.preventDefault(); 

    const form = document.getElementById('inspectionForm');

    if (!form.checkValidity()) {
      form.classList.add('was-validated'); 
      return;
    }

    const currentModal = bootstrap.Modal.getInstance(document.getElementById('newListingFormModal'));
    currentModal.hide();

    document.getElementById('newListingFormModal').addEventListener('hidden.bs.modal', function () {
      const nextModal = new bootstrap.Modal(document.getElementById('loginPromptModal'));
      nextModal.show();
    }, { once: true });
  }

// SHOW ON SCHEDULE TOUR MODAL
    window.addEventListener("DOMContentLoaded", function () {
        const date = localStorage.getItem("clientviewDate");
        const time = localStorage.getItem("clientviewTime");

        if (name && date && time) {
            document.getElementById("modalclientViewDate").textContent = date;
            document.getElementById("modalclientViewTime").textContent = time;

            // Optionally clear the storage after use:
            // localStorage.removeItem("buyerName");
            // localStorage.removeItem("viewDate");
            // localStorage.removeItem("viewTime");

            // Open the modal automatically
            const modal = new bootstrap.Modal(document.getElementById('propertyModal'));
            modal.show();
        }
    });



  (() => {
    'use strict';

    const form = document.getElementById('newListingModal');

    form.addEventListener('submit', function (event) {
      event.preventDefault(); // Always prevent default first

      if (!form.checkValidity()) {
        event.stopPropagation();
        form.classList.add('was-validated');
        return; // Stop here if invalid
      }

      form.classList.add('was-validated');

      // ✅ Open the next modal here
      const nextModal = new bootstrap.Modal(document.getElementById('confirmsubmitModal'));
      nextModal.show();
    }, false);
  })();


