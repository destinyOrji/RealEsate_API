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

//SAVE PAGE

document.addEventListener('DOMContentLoaded', () => {
    // Load saved cards on page load
    const saved = JSON.parse(localStorage.getItem('savedProperties')) || [];
    const savedContainer = document.getElementById('savedContainer');
    saved.forEach(item => {
        if (savedContainer) savedContainer.innerHTML += generateCardHTML(item);
    });

    // Setup click for all bookmark icons
    document.querySelectorAll('.save-icon').forEach(icon => {
        icon.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const card = this.closest('.card');

            let savedList = JSON.parse(localStorage.getItem('savedProperties')) || [];
            const alreadySaved = savedList.find(item => item.id === id);

            if (!alreadySaved) {
                // Save the card
                const cardData = {
                    id: id,
                    img: card.querySelector('img').src,
                    title: card.querySelector('.card-title').innerText,
                    location: card.querySelector('.card-text').innerText,
                    price: card.querySelector('[data-price]')?.getAttribute('data-price') || "#30M",
                    type: card.querySelector('[data-type]')?.getAttribute('data-type') || "3 Bedroom Flat",
                    features: card.querySelector('[data-features]')?.getAttribute('data-features') || "",
                };

                savedList.push(cardData);
                localStorage.setItem('savedProperties', JSON.stringify(savedList));

                this.classList.remove('bi-bookmark');
                this.classList.add('bi-bookmark-fill', 'text-primary');

                if (savedContainer) savedContainer.innerHTML += generateCardHTML(cardData);
            } else {
                // Remove from saved
                savedList = savedList.filter(item => item.id !== id);
                localStorage.setItem('savedProperties', JSON.stringify(savedList));

                this.classList.remove('bi-bookmark-fill', 'text-primary');
                this.classList.add('bi-bookmark');

                // Remove from saved page
                const toRemove = savedContainer.querySelector(`[data-id="${id}"]`);
                if (toRemove) toRemove.remove();
            }
        });
    });
});

// Generates the full card HTML for saved page
function generateCardHTML(data) {
    return `
    <div class="col-12 col-sm-6 col-lg-4" data-id="${data.id}">
        <div class="card h-100">
            <div class="position-relative">
                <img src="${data.img}" class="card-img-top" alt="house">
                <span class="btn btn-sm btn-light position-absolute bottom-0 start-0 m-2 fw-semibold">${data.price}</span>
                <button
                    class="btn btn-sm btn-dark position-absolute bottom-0 end-0 m-2"
                    data-bs-toggle="modal"
                    data-bs-target="#propertyModal"
                    data-title="${data.title}"
                    data-location="${data.location}"
                    data-price="${data.price}"
                    data-type="${data.type}"
                    data-img="${data.img}"
                    data-features="${data.features}">
                    Preview
                </button>
            </div>
            <div class="card-body d-flex justify-content-between align-items-start">
                <div>
                    <h5 class="card-title p-0 m-0">${data.title}</h5>
                    <p class="card-text p-0 m-0">
                        <i class="bi bi-geo-alt-fill text-danger"></i> ${data.location}
                    </p>
                </div>
                <i class="bi bi-bookmark-fill text-primary fs-5 save-icon" data-id="${data.id}" style="cursor:pointer;"></i>
            </div>
            <div class="card-footer">
                <small class="text-body-secondary">${data.type}</small>
            </div>
        </div>
    </div>
    `;
}

