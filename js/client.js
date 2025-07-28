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