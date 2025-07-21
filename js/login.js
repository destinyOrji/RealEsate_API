const email = document.getElementById("email");
const password = document.getElementById("password");


function validate() {
    if(
        email.value.trim() === '' || 
        password.value.trim() === ''
    ){
        alert("All fields must be filled");

        email.value.trim() === '' ? 
        email.classList.add("is-invalid"): 
        email.classList.remove("is-invalid"), 
        email.classList.add("is-valid")

        password.value.trim() === '' ? 
        password.classList.add("is-invalid"): 
        password.classList.remove("is-invalid"), 
        password.classList.add("is-valid")

    }else{
        alert('Login failed! All fields are valid');
    }
}


function formSubmit(e){
    e.preventDefault();
    validate();
}
