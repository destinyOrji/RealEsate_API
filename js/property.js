
  
  function toggleSave(icon) {
  icon.classList.toggle('bi-bookmark');
  icon.classList.toggle('bi-bookmark-fill');
  icon.style.color = icon.classList.contains('bi-bookmark-fill') ? 'blue' : 'black';
}








  const previewModal = document.getElementById('propertyModal');
  previewModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;

    document.getElementById('propertyModalLabel').textContent = button.getAttribute('data-title');
    document.getElementById('modalImage').src = button.getAttribute('data-img');
    document.getElementById('modalLocation').textContent = button.getAttribute('data-location');
    document.getElementById('modalType').textContent = button.getAttribute('data-type');
    document.getElementById('modalFeatures').textContent = button.getAttribute('data-features');
    document.getElementById('modalPrice').textContent = button.getAttribute('data-price');
  });

  function openInspectionForm() {
    const modal = new bootstrap.Modal(document.getElementById('inspectionFormModal'));
    modal.show();
  }

  function showLoginPrompt(event) {
    event.preventDefault(); // prevent form from submitting normally
    const modal = new bootstrap.Modal(document.getElementById('loginPromptModal'));
    modal.show();
    document.getElementById('inspectionFormModal').classList.remove('show');
    document.querySelector('#inspectionFormModal .modal-backdrop')?.remove();
  }


  // Form validation
  function showLoginPrompt(event) {
    event.preventDefault(); 

    const form = document.getElementById('inspectionForm');

    if (!form.checkValidity()) {
      form.classList.add('was-validated'); 
      return;
    }

    const currentModal = bootstrap.Modal.getInstance(document.getElementById('inspectionFormModal'));
    currentModal.hide();

    document.getElementById('inspectionFormModal').addEventListener('hidden.bs.modal', function () {
      const nextModal = new bootstrap.Modal(document.getElementById('loginPromptModal'));
      nextModal.show();
    }, { once: true });
  }

// FORM TO SHOW DETAILS Agent side
    document.getElementById("inspectionFormModal").addEventListener("submit", function(e) {
        e.preventDefault();

        // Get form values
        const date = document.getElementById("inputDate").value;
        const time = document.getElementById("inputTime").value;

        // Save to localStorage
        localStorage.setItem("viewDate", date);
        localStorage.setItem("viewTime", time);

        // Redirect to the property page
        window.location.href = "property-preview.html"; // your modal page
    });

    // FORM TO SHOW DETAILS Client Side
    document.getElementById("inspectionFormModal").addEventListener("submit", function(e) {
        e.preventDefault();

        // Get form values
        const date = document.getElementById("inputDate").value;
        const time = document.getElementById("inputTime").value;

        // Save to localStorage
        localStorage.setItem("viewDate", date);
        localStorage.setItem("viewTime", time);

        // Redirect to the property page
        window.location.href = "property-preview.html"; // your modal page
    });

   
