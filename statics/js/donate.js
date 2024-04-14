document.addEventListener('DOMContentLoaded', ()=>{
    let callDB;
    let record_pre_len = 0;

    if (shouldShowModal) {
        var submitAlertModal = new bootstrap.Modal(document.getElementById('submitAlertModal'));
        submitAlertModal.show();
    }

    if(document.getElementById('not_me_btn')){
        document.getElementById('not_me_btn').addEventListener('click', function(){
            // is not me then set value not me
             // Create a form dynamically
            var form = document.createElement("form");
            form.setAttribute("method", "post");
            form.setAttribute("action", window.location.href); // Replace with your own server script

             // Create an input element for name
            var decisionInput = document.createElement("input");
            decisionInput.setAttribute("type", "hidden"); // Set type as hidden
            // decisionInput.setAttribute("type", "text");
            decisionInput.setAttribute("name", "decisionValue");
            decisionInput.value = "not me";

            // Append elements to the form
            form.appendChild(decisionInput);

            // Append the form to the div
            document.getElementById("decisionFromContainer").appendChild(form);

            // Handle form submission
            form.submit();
        })
    }

    if (document.getElementById('is_me_btn')){
        document.getElementById('is_me_btn').addEventListener('click', function(){
            // is me then set value match
             // Create a form dynamically
             var form = document.createElement("form");
             form.setAttribute("method", "post");
             form.setAttribute("action", window.location.href); // Replace with your own server script
 
              // Create an input element for name
             var decisionInput = document.createElement("input");
             decisionInput.setAttribute("type", "hidden"); // Set type as hidden
            //  decisionInput.setAttribute("type", "text");
             decisionInput.setAttribute("name", "decisionValue");
             decisionInput.value = "is me";
 
             // Append elements to the form
             form.appendChild(decisionInput);
 
             // Append the form to the div
             document.getElementById("decisionFromContainer").appendChild(form);
 
             // Handle form submission
             form.submit();
        })
    }

    document.getElementById('confirmBtn').addEventListener('click', function(){
        event.preventDefault();

        document.getElementById('personal_detial_payment_form').submit();
    })

    document.getElementById('anonymousCheck').addEventListener('change', ()=>{
        console.log(document.getElementById('anonymousCheck').checked);

        if (document.getElementById('anonymousCheck').checked){
            hideOtherInput();

            if(callDB){
                clearInterval(callDB);
            }
        }else{
            showOtherInput();
            callDB = setInterval(()=>{
                console.log("adsfasdf");
                fetch_transaction_detail()
            }, 1000)
        }
    })

    document.getElementById('confirmDelete').addEventListener('click', function() {
        // Place your logic here for what happens when the user confirms deletion.
        console.log('Data deleted'); // Example action
        // Close the modal programmatically
        var cancelModal = bootstrap.Modal.getInstance(document.getElementById('cancelModal'));
        cancelModal.hide();

        // Create a form element
        var form = document.createElement("form");
        form.setAttribute("method", "post");
        form.setAttribute("action", "./");

        // Create a hidden input element for the cancel button click
        var hiddenField = document.createElement("input");
        hiddenField.setAttribute("type", "hidden");
        hiddenField.setAttribute("name", "cancelBtnClicked");
        hiddenField.setAttribute("value", "true");
        form.appendChild(hiddenField);

        // Append the form to the body and submit it
        document.body.appendChild(form);
        form.submit();

        // back to prevous page;
        // window.location.href = "./purchase.php";
    });

    document.getElementById('checkBankAccountName').addEventListener('change', ()=>{
        let check_box = document.getElementById('checkBankAccountName');
        if(check_box.checked == true){
            //get full name 
            let fullname = document.getElementById('fullnameInput').value;

            if (fullname === ""){
                check_box.checked = false;
                create_alert(document.getElementById('fullNameDiv'), "Please full in the <b> Full Name </b> first!")
            }else{
                document.getElementById('bankAccountNameInput').value = fullname;
                document.getElementById('bankAccountNameInput').readOnly  = true;
                document.getElementById('fullnameInput').readOnly  = true;
            }
            
        }else{
            document.getElementById('bankAccountNameInput').value = "";
            document.getElementById('bankAccountNameInput').readOnly  = false;
            document.getElementById('fullnameInput').readOnly  = false;
        }
    })

    document.getElementById('fullnameInput').addEventListener('input', function(event){
        // console.log("Input value changed to:", event.target.value)

        // Get the current value of the input
        var value = event.target.value;

        // Update the input field with the cleaned value
        event.target.value = value.toUpperCase();
    })

    document.getElementById("personal_detial_payment_form").onsubmit = (event) => {
        event.preventDefault();

        if(!document.getElementById('anonymousCheck').checked){
            var noError = true

            // check the full name
            if(!isNonEmptyNoDigits(document.getElementById("fullnameInput").value)){
                create_alert(document.getElementById('fullNameDiv'), "Full name cannot be <b> empty and contain digit </b>!");
                noError = false;
            }

            // check if tick the check, fullname and bank account name must same
            if (document.getElementById("checkBankAccountName").checked){
                if(!isBothValueSame(document.getElementById("fullnameInput").value, document.getElementById("bankAccountNameInput").value)){
                    create_alert(document.getElementById('fullNameDiv'), "Full name and bank account name <b> must be same </b>, if the checkbox is <b> checked </b>!");
                    noError = false;
                }
            }else{
                if(!isNonEmptyNoDigits(document.getElementById("bankAccountNameInput").value)){
                    create_alert(document.getElementById('bankAccountNameInput'), "Bank holder name cannot be <b> empty and contain digit </b>!");
                    noError = false;
                }
            }

            // check the validation of the email
            if(!isValidEmail(document.getElementById("emailInput").value)){
                create_alert(document.getElementById('fullNameDiv'), "Email format <b>invalid</b>!");
                noError = false;
            }

            // check the phone prefix
            // console.log(document.getElementById('phone_prefix').value)
            if (!validPhonePrefix(document.getElementById('phone_prefix').value)){
                create_alert(document.getElementById('fullNameDiv'), "Please choose a valid <b>phone prefix</b>!");
                noError = false;
            }

            // check value phone number
            if(!isValidPhoneNumberLength(document.getElementById('phoneInput').value)){
                create_alert(document.getElementById('fullNameDiv'), "Phone number must not <b>less than 7 digits and more than 8 digits</b>! (Not include prefixe)");
                noError = false;
            }

            if (noError){
                event.target.submit();
            }
        }else{
            event.target.submit();
        }
    }

    if(document.getElementById('anonymousCheck').checked){
        hideOtherInput();
    }else{
        callDB = setInterval(()=>{
            console.log("adsfasdf");
            fetch_transaction_detail()
        }, 1000)
    }
})

function isValidPhoneNumberLength(numberString) {
    return /^[0-9]{7,8}$/.test(numberString);
}

function validPhonePrefix(phone_prefix){
    if(['011','012','013','014','015','016','017','018','019'].includes(phone_prefix)){
        return true;
    }else{
        return false;
    }
}

function isBothValueSame(value_1, value_2){
    if (value_1.trim() === value_2.trim()){
        return true;
    }else{
        return false;
    }
}

function isNonEmptyNoDigits(fullname) {
    var inputValue = fullname.trim();

    // Check if the input is not empty
    if (inputValue === "") {
        return false; // Input is empty
    }

    // Check if the input contains digits
    var digitRegex = /\d/;
    if (digitRegex.test(inputValue)) {
        return false; // Input contains digits
    }

    return true; // Input is non-empty and contains no digits
}

function isValidEmail(email) {
    var regex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    return regex.test(email);
}

function create_alert(doc, message) {
    // Create the main alert div
    var alertDiv = document.createElement("div");
    alertDiv.className = "alert alert-danger alert-dismissible fade show";
    alertDiv.setAttribute("role", "alert");
    alertDiv.innerHTML = message;

    // Create the close button
    var closeButton = document.createElement("button");
    closeButton.setAttribute("type", "button");
    closeButton.className = "btn-close";
    closeButton.setAttribute("data-bs-dismiss", "alert");
    closeButton.setAttribute("aria-label", "Close");

    // Append the close button to the alert div
    alertDiv.appendChild(closeButton);

    // Prepend the alert div to the document
    doc.prepend(alertDiv);
}

let record_pre_len = 0;

function fetch_transaction_detail(){
    let bank_holder_name = document.getElementById('bankAccountNameInput').value.trim()
    let load_page_time = load_donate_page_time;
    if(bank_holder_name != ""){
        fetch('./get_bank_received_detail.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({bank_holder_name, load_page_time})
            })
            .then(response => response.json())
            .then(data => {
                console.log('Success:', data);
                // console.log('Success:', data.length);
                // console.log(load_donate_page_time);
                // data = [[..],[..]]
                if(data){
                    if (record_pre_len < data.length){
                        let name = data[0]['payer_name'];
                        let money = 0;
                        for (let i = record_pre_len; i<data.length; i++){
                            money += data[i]['received_amount'];
                        }
                        // console.log(name)
                        record_pre_len =  data.length;
                        updateRadioOption(name, money);
                        set_session_bank_holder(name);
                    }
                }
            })
            .catch((error) => {
                console.error('Error:', error);
            });
    }
}

function set_session_bank_holder(name) {
    fetch('./set_session_bank_holder.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ name })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        // return response.json();
    })
    // .then(data => {
    //     console.log('Success:', data);
    //     // Here you can add additional logic to confirm success based on 'data'
    // })
    // .catch((error) => {
    //     console.error('Error:', error);
    // });
}


function updateRadioOption(name, money) {
    // if the bank name same as the received from who's name, and the ref number not same as previous
    // if(bank_holder_name === name && !reference_array.includes(reference)){
    if (!document.getElementById(name.replace(/ /g, "_"))){
        var container = document.getElementById("radioButtonsContainer");
        // Create a div to wrap the radio button and label
        var radioWrapper = document.createElement("div");
        radioWrapper.className = "form-check";
    
        var radioButton = document.createElement("input");
        radioButton.className = "form-check-input";
        radioButton.type = "radio";
        radioButton.name = "paymentConfirmRadio"; // keep the same name for radio group
        radioButton.id = name.replace(/ /g, "_"); // unique ID for each radio button
        radioButton.value = "RM " + money.toFixed(2) + " / " + name.replace(/ /g, "_");
    
        var label = document.createElement("label");
        label.className = "form-check-label";
        label.id = "label_"+name.replace(/ /g, "_");
        label.htmlFor = radioButton.id; // associate the label with the radio button
        label.appendChild(document.createTextNode("Received RM " + money.toFixed(2) + " from " + name + "'s account"));
    
        // Append the radio button and label to the wrapper
        radioWrapper.appendChild(radioButton);
        radioWrapper.appendChild(label);
    
        // Append the wrapper to the container
        container.appendChild(radioWrapper);
    }else{
        const moneyRegex = /RM\s([\d.]+)/; // Matches "RM" followed by a space and then the amount
        let pre_radio_value = document.getElementById(name.replace(/ /g, "_")).value;
        let pre_money_value = parseFloat(pre_radio_value.match(moneyRegex)[1]);
        document.getElementById(name.replace(/ /g, "_")).value = "RM " + (pre_money_value + money).toFixed(2) + " / " + name.replace(/ /g, "_");

        document.getElementById("label_"+name.replace(/ /g, "_")).textContent = "Received RM " + (pre_money_value + money).toFixed(2) + " from " + name + "'s account";
    }
}


function hideOtherInput(){
    // display:none
    document.getElementById('fullNameDiv').style.display = 'none';
    document.getElementById('checkboxDiv').style.display = 'none';
    document.getElementById('bankAccountNameDiv').style.display = 'none';
    document.getElementById('emailDiv').style.display = 'none';
    document.getElementById('phoneDive').style.display = 'none';
    document.getElementById('importantDiv').style.display = 'none';
    document.getElementById('transactionDiv').style.display = 'none';
    document.getElementById('radioButtonsContainer').style.display = 'none';
    document.getElementById('formDiv').classList.remove("col-lg-6");
    document.getElementById('qrDiv').classList.remove("col-lg-6");

    // Attribute:no required
    document.getElementById('fullnameInput').removeAttribute("required");
    document.getElementById('bankAccountNameInput').removeAttribute("required");
    document.getElementById('emailInput').removeAttribute("required");
    document.getElementById('phoneInput').removeAttribute("required");
}

function showOtherInput(){
    // display:block
    document.getElementById('fullNameDiv').style.display = 'block';
    document.getElementById('checkboxDiv').style.display = 'block';
    document.getElementById('bankAccountNameDiv').style.display = 'block';
    document.getElementById('emailDiv').style.display = 'block';
    document.getElementById('phoneDive').style.display = 'block';
    document.getElementById('importantDiv').style.display = 'block';
    document.getElementById('transactionDiv').style.display = 'block';
    document.getElementById('radioButtonsContainer').style.display = 'block';
    document.getElementById('formDiv').classList.add("col-lg-6");
    document.getElementById('qrDiv').classList.add("col-lg-6");

    // Attribute:no required
    document.getElementById('fullnameInput').setAttribute("required", true);
    document.getElementById('bankAccountNameInput').removeAttribute("required");
    document.getElementById('fullnameInput').removeAttribute("readonly");
    document.getElementById('bankAccountNameInput').setAttribute("readonly", true);
    document.getElementById('emailInput').setAttribute("required", true);
    document.getElementById('phoneInput').setAttribute("required", true);
}