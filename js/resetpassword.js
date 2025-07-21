
const password = document.getElementById("password");
const confirm_password = document.getElementById("confirmpassword");


function validate() {
    let isValid = true;

    if (
        password.value.trim() === '' || 
        confirm_password.value.trim() === ''
) {
  alert("Login failed! All fields must be filled.");

  if (password.value.trim() === '') {
    password.classList.add("is-invalid");
    password.classList.remove("is-valid");
  } else {
    password.classList.remove("is-invalid");
    password.classList.add("is-valid");
  }

  if (confirm_password.value.trim() === '') {
    confirm_password.classList.add("is-invalid");
    confirm_password.classList.remove("is-valid");
  } else {
    confirm_password.classList.remove("is-invalid");
    confirm_password.classList.add("is-valid");
  }

} else if (password.value.trim() !== confirm_password.value.trim()) {
  alert("Login failed! Passwords do not match.");

  password.classList.add("is-invalid");
  confirm_password.classList.add("is-invalid");

} else {
   
  password.classList.remove("is-invalid");
  confirm_password.classList.remove("is-invalid");

  password.classList.add("is-valid");
  confirm_password.classList.add("is-valid");

}
}

function formSubmit(event){
    event.preventDefault();
    if (validate()) {
    const modal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
    modal.show();
  } else {
    alert("Please fix the errors before continuing.");
  }
}


