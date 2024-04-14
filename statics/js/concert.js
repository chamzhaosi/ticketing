// import data from './seat_detail.json' assert { type: 'json' };;

document.addEventListener('DOMContentLoaded', function(){
    let callDB;
    let record_pre_len = 0;
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))

    console.log("Document is ready!")

    var color_seat = {
        "red" : {
            "total_seat":0,
            "selled_seat" : 0,
        },
        "orange" : {
            "total_seat":0,
            "selled_seat" : 0,
        },
        "yellow" : {
            "total_seat":0,
            "selled_seat" : 0,
        },
        "green" : {
            "total_seat":0,
            "selled_seat" : 0,
        },
        "blue" : {
            "total_seat":0,
            "selled_seat" : 0,
        },
        "gray" : {
            "total_seat":0,
            "selled_seat" : 0,
        },
    }
    var selected_seat = new Array()
    
    // console.log(color_seat)
    // var color_summary_div = document.getElementById("color_summary");

    var current_page = (window.location.pathname).substring(window.location.pathname.lastIndexOf('/')+1)
    // console.log(current_page);
    // console.log(current_page == " ");
    if(current_page != "personal_detail_payment.php" && current_page != "qr_code_scan.php" && current_page != "payment_status.php"){
        async function loadData() {
            try {
                // First fetch request for seat layout
                const responseLayout = await fetch('./statics/json/seat_info_v.json');
                if (!responseLayout.ok) {
                    throw new Error(`Network response was not ok ${responseLayout.statusText}`);
                }
                const seatLayoutData = await responseLayout.json();
                // console.log(seatLayoutData);
                render_seat_layout(seatLayoutData);
                update_locked_seat();
                // Assuming update_selled_seat is an async function or returns a Promise
                await update_selled_seat(); // Wait for update_selled_seat to finish
        
                // Second fetch request for seat price
                const responsePrice = await fetch('./statics/json/seat_price.json');
                if (!responsePrice.ok) {
                    throw new Error(`Network response was not ok ${responsePrice.statusText}`);
                }
                const seatPriceData = await responsePrice.json();
                update_seat_price(seatPriceData);
        
            } catch (error) {
                console.error('There has been a problem with your fetch operation:', error);
            } finally {
                // Updating UI after both fetch requests are completed
                document.getElementById('loadingContainer').classList.remove("d-block");
                document.getElementById('loadingContainer').classList.add("d-none");
                document.getElementById('contentContainer').classList.remove("d-none");
                document.getElementById('contentContainer').classList.add("d-block");
            }
        }
        
        loadData(); // Call the function to start the fetch process
    }
    console.log(current_page === "qr_code_scan.php")

    if (current_page === "index.php" || current_page === ""){
        var concert_video = document.getElementById("concert_video");
        concert_video.volume = 0.1;

        document.getElementById("search_seat_form").onsubmit = (event) => {
            search_seat(event)

            // You can return false to prevent form submission, or handle the submission using AJAX, etc.
            return false;
        }


        document.getElementById("btn_anonymous").addEventListener('click', ()=>{
            // Create the form element
            var form = document.createElement("form");
            form.id = "donationForm";
            form.style.display = "none";
            form.method = "POST";
            form.action = "./donate_form.php"

            // Create the hidden input element
            var hiddenInput = document.createElement("input");
            hiddenInput.type = "hidden";
            hiddenInput.id = "donationType";
            hiddenInput.name = "donationType";

            // Append the hidden input to the form
            form.appendChild(hiddenInput);
            hiddenInput.value = "anonymous";

            // Append the form to the placeholder in the HTML
            document.getElementById("form-container").appendChild(form);

            form.submit();            
        })

        document.getElementById("btn_real").addEventListener('click', ()=>{
            // Create the form element
            var form = document.createElement("form");
            form.id = "donationForm";
            form.style.display = "none";
            form.method = "POST";
            form.action = "./donate_form.php"

            // Create the hidden input element
            var hiddenInput = document.createElement("input");
            hiddenInput.type = "hidden";
            hiddenInput.id = "donationType";
            hiddenInput.name = "donationType";

            // Append the hidden input to the form
            form.appendChild(hiddenInput);
            hiddenInput.value = "real";

            // Append the form to the placeholder in the HTML
            document.getElementById("form-container").appendChild(form);

            form.submit();
        })


    }else if(current_page === "purchase.php"){
        document.getElementById("check_out_btn").onclick = (event) => {
            check_selected_detial(event)
            // You can return false to prevent form submission, or handle the submission using AJAX, etc.
            return false;
        }

    }else if(current_page === "personal_detail_payment.php"){

        let load_into_psn_page_time = document.getElementById('load_into_psn_page_time').textContent.replace(/ /g, "");
        check_time_out(load_into_psn_page_time);

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


        document.getElementById('donation_support').addEventListener('input', function(event){
            // console.log("Input value changed to:", event.target.value)

            // Get the current value of the input
            var value = event.target.value;

            // Remove any characters that are not numbers or a single decimal point
            value = value.replace(/[^0-9\.]/g, '');

             // Check and format decimal places
            if (value.includes('.')) {
                // Split the string at the decimal point
                var parts = value.split('.');
                // Allow only two digits after the decimal
                if (parts[1].length > 2) {
                parts[1] = parts[1].substring(0, 2);
                }
                value = parts[0] + '.' + parts[1];
            }

            // Update the input field with the cleaned value
            event.target.value = value;
            
            let seat_amount = document.getElementById("sub_amount").textContent.replace(/ /g, "").replace(/RM/g, "");
            document.getElementById("total_amount").textContent = "RM " + (Number(value) + Number(seat_amount)) + ".00";
        })

        document.getElementById("personal_detial_payment_form").onsubmit = (event) => {
            event.preventDefault();

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
        }

        document.getElementById('confirmDelete').addEventListener('click', function() {
            // Place your logic here for what happens when the user confirms deletion.
            console.log('Data deleted'); // Example action
            // Close the modal programmatically
            var confirmModal = bootstrap.Modal.getInstance(document.getElementById('confirmModal'));
            confirmModal.hide();

            // Create a form element
            var form = document.createElement("form");
            form.setAttribute("method", "post");
            form.setAttribute("action", "./purchase.php");

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

        document.getElementById('loadingContainer').classList.remove("d-block");
        document.getElementById('loadingContainer').classList.add("d-none");
        document.getElementById('contentContainer').classList.remove("d-none");
        document.getElementById('contentContainer').classList.add("d-block");

    }else if(current_page === "qr_code_scan.php"){
        // let load_into_psn_page_time = document.getElementById('load_into_psn_page_time').textContent.replace(/ /g, "");
        // check_time_out(load_into_psn_page_time);

        // var load_into_qr_page_time = document.getElementById('load_into_qr_page_time').textContent.replace(/ /g, "");
        // var bank_holder_name = document.getElementById('bank_holder_name').textContent.trim();
        check_time_out(load_into_psn_page_time);
        var reference_array = [];

        if (shouldShowModal) {
            var submitAlertModal = new bootstrap.Modal(document.getElementById('submitAlertModal'));
            submitAlertModal.show();
        }

        document.getElementById("back_btn").addEventListener('click', ()=>{
            console.log("fsdf");
            window.location.href = "./personal_detail_payment.php";
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
            form.setAttribute("action", "./purchase.php");

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

            document.getElementById('qr_form').submit();
        })

        callDB = setInterval(()=>{
            console.log("adsfasdf");
            fetch_transaction_detail()
        }, 1000)

        document.getElementById('loadingContainer').classList.remove("d-block");
        document.getElementById('loadingContainer').classList.add("d-none");
        document.getElementById('contentContainer').classList.remove("d-none");
        document.getElementById('contentContainer').classList.add("d-block");

    }else if(current_page === "payment_status.php"){
        document.getElementById('loadingContainer').classList.remove("d-block");
        document.getElementById('loadingContainer').classList.add("d-none");
        document.getElementById('contentContainer').classList.remove("d-none");
        document.getElementById('contentContainer').classList.add("d-block");
    }

    function fetch_transaction_detail(){
        let load_page_time = load_into_qr_page_time;
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
                    console.log('Success:', data.length);
                    // console.log(load_donate_page_time);
                    // data = [[..],[..]]
                    if(data){
                        if (record_pre_len < data.length){
                            console.log("fasdf");
                            let name = data[0]['payer_name'];
                            let money = 0;
                            for (let i = record_pre_len; i<data.length; i++){
                                money += data[i]['received_amount'];
                            }
                            // console.log(name)
                            record_pre_len =  data.length;
                            updateRadioOption(name, money);
                        }
                    }
                })
                .catch((error) => {
                    console.error('Error:', error);
                });
        }
    }

    function updateRadioOption(name, money) {
        console.log("afa")
        // if the bank name same as the received from who's name, and the ref number not same as previous
        // if(bank_holder_name === name && !reference_array.includes(reference)){
        if (!document.getElementById(name.replace(/ /g, "_"))){
            console.log("121212")
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
            console.log("34343")
            const moneyRegex = /RM\s([\d.]+)/; // Matches "RM" followed by a space and then the amount
            let pre_radio_value = document.getElementById(name.replace(/ /g, "_")).value;
            let pre_money_value = parseFloat(pre_radio_value.match(moneyRegex)[1]);
            document.getElementById(name.replace(/ /g, "_")).value = "RM " + (pre_money_value + money).toFixed(2) + " / " + name.replace(/ /g, "_");
    
            document.getElementById("label_"+name.replace(/ /g, "_")).textContent = "Received RM " + (pre_money_value + money).toFixed(2) + " from " + name + "'s account";
        }
    }

    function check_time_out(load_into_psn_page_time){
        let current_time = 0;
        let duration = 0;
        let type = "";

        if(current_page === "personal_detail_payment.php"){
            duration = 5 * 60; // 5 min
            type="form";
        }else{
            duration = 7 * 60 + 30; // 3 min
            type="transaction";
        }
        
        let update_countdown = setInterval(()=>{
            current_time = Math.floor(Date.now() / 1000);

            if (duration - (current_time-load_into_psn_page_time) < 1){
                clearInterval(update_countdown);
                location.replace(window.location.href);
            }

            document.getElementById("time_out_message").innerHTML = 'Please note, you must complete this '+ type +' within <b> '+ (duration - (current_time-load_into_psn_page_time))  +' seconds </b>. Failure to do so will be considered as forfeiting the payment.'

            if(duration - (current_time-load_into_psn_page_time) < 60){
                document.getElementById("time_out_message").classList.remove("alert-warning");
                document.getElementById("time_out_message").classList.add("alert-danger");
            }
        },1000)
    }

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

    function check_selected_detial(event){
        event.preventDefault();

        var amount_text = document.getElementById("selected_amount").textContent;
        var seat_text = document.getElementById("selected_seat_detial").textContent;
        // console.log(Number(amount_text.replace("RM ", "")))
        // console.log(seat_text.split(", ").length)

        var amount = Number(amount_text.replace("RM ", ""));
        var total = 0;
        var seat_array = seat_text.split(", ")

        console.log(selected_seat)

        if(seat_array.length === selected_seat.length){
            var beBreak = false
            seat_array.forEach(( _, index)=>{
                if (seat_array.includes(selected_seat[index]['id'])){
                    total += color_seat[selected_seat[index]["color"]]["price"]
                } else{
                    // create error message 
                    beBreak = true
                    create_alert(document.getElementById('payment_summary'), "Some Error Occurred! Seat <b> " + seat_array[index] + " </b> should not be selected!");
                    return;
                }
            })

            if (!beBreak){
                if (amount === total){
                    // create form and submit
                    create_check_out_form(seat_text, amount)

                }else{
                    // create error message 
                    create_alert(document.getElementById('payment_summary'), "Some Error Occurred! Amount should be <b> RM " + total + " </b>");
                }
            }
            
        }else{
            // create error message 
            create_alert(document.getElementById('payment_summary'), "Some Error Occurred! Selected seat should has <b> " + selected_seat.length + " </b> only");
        }
        
    }

    function create_check_out_form(seat_text, amount){
        // Access the container where the form will be placed
        var container = document.getElementById('formContainer');
        // Create form
        var form = document.createElement('form');
        form.setAttribute('id', 'check_out_form');
        form.setAttribute('method', 'post');
        form.setAttribute('action', window.location.pathname);

        // Create input for 'name'
        var nameInput = document.createElement('input');
        nameInput.setAttribute('type', 'text');
        nameInput.setAttribute('name', 'seat_number');
        nameInput.value = seat_text; // Set the default value

        // Create input for 'email'
        var emailInput = document.createElement('input');
        emailInput.setAttribute('type', 'number');
        emailInput.setAttribute('name', 'amount');
        emailInput.value = amount; // Set the default value

        // Create a submit button
        var submitButton = document.createElement('input');
        submitButton.setAttribute('type', 'submit');
        submitButton.value = 'Submit';

        // Append elements to form
        form.appendChild(nameInput);
        form.appendChild(emailInput);
        form.appendChild(submitButton);

        // Append form to container
        container.appendChild(form);

        form.submit()
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

    function update_locked_seat(){
        fetch('./locked_seat.php',{
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            // body: JSON.stringify({ /* Your data here if needed */ })
        })
            .then(response => response.json())
            .then(data => {
                console.log(data)

                if (data.length > 0){
                    let seat_id_array = data
                    .map(item => item.split(',')) // Split each string by ', ' to create arrays
                    .flat(); // Flatten the array of arrays into a single array

                    seat_id_array.forEach((id)=>{
                        let element = document.getElementById(id);

                        // Update innerHTML
                        element.innerHTML = '<i class="fa-solid fa-lock"></i>';
                    
                        // Update classes
                        element.classList.add("opacity-50");
                        element.classList.add("no-drop");
                        element.classList.remove("pointer");
                    
                        // Remove event listener
                        element.removeEventListener('click', seletedHandle);
                    
                        // Set tooltip data attributes
                        element.setAttribute('data-bs-toggle', 'tooltip');
                        element.setAttribute('data-bs-placement', 'top'); // or your preferred placement
                        element.setAttribute('data-bs-title', 'The seat has been locked.');
                    
                        // Initialize the tooltip
                        new bootstrap.Tooltip(element);
                    })
                }

                // console.log(seat_id_array);
            })
            .catch(error => console.error('Error:', error));
    }

    async function update_selled_seat(){
        return fetch('./selled_seat.php',{
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
        })
            .then(response => response.json())
            .then(data => {
                console.log(data);
                data.forEach((id)=>{
                    // console.log(id)
                    let element = document.getElementById(id);

                    // Update innerHTML
                    element.innerHTML = '<i class="fa-regular fa-user"></i>';
                
                    // Update classes
                    element.classList.add("opacity-50"); 
                    element.classList.add("no-drop"); 
                    element.classList.remove("pointer");
                
                    // Remove event listener
                    element.removeEventListener('click', seletedHandle);
                
                    // Set tooltip data attributes
                    element.setAttribute('data-bs-toggle', 'tooltip');
                    element.setAttribute('data-bs-placement', 'top'); // or your preferred placement
                    element.setAttribute('data-bs-title', 'The seat has been sold.');
                
                    // Initialize the tooltip
                    new bootstrap.Tooltip(element);

                    color_seat[element.classList[4]]["selled_seat"]++;
                    // console.log(color_seat[element.classList[4]]["selled_seat"]);
                    // console.log(element.classList[4]);


                })
            })
            .catch(error => console.error('Error:', error));
    }

    function search_seat(event){
        event.preventDefault(); // Prevent the default form submit action
            
        // Here, you can add what you want to do when the form is submitted
        var inputValue = document.querySelector('#search_seat_form input[name="searchSeat"]').value;
        // console.log(inputValue)

        reset_seat()

        // phone number
        if(!isNaN(Number(inputValue.replace(/-/g, ""))) || inputValue.includes("@") && inputValue.includes(".com")){
            // can be converted to a number
            // console.log(user_submit)
            if (document.getElementById("invalid_search_format") != null){
                document.getElementById("invalid_search_format").remove()
            }

            // Keep the seat
            var keep_seat = new Array()

            // call API to get feeback
            fetch('./search_selled_seat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({"inputValue":inputValue})
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json(); // or response.text() if the server sends non-JSON data
            })
            .then(data => {
                console.log(data); // Process your data here
                data.forEach((data)=>{
                    // console.log(data)
                    document.getElementById(data).classList.add("seat_bought")
                    document.getElementById(data).classList.remove("border-secondary", "border", "opacity-50")
                    document.getElementById(data).innerHTML = data
                    keep_seat.push(data)
                })

                console.log(document.getElementById(keep_seat[0]).parentElement.id)
                document.getElementById(document.getElementById(keep_seat[0]).parentElement.id).scrollIntoView({ behavior: 'smooth', block: 'center' })
            })
            .catch(error => {
                console.error('There has been a problem with your fetch operation:', error);
            });

        }else{
            // invalid format
            console.log("Invalid format!")
            console.log(document.getElementById("invalid_search_format") )
            
            if (document.getElementById("invalid_search_format") === null){
                var message_div = document.createElement('div')
                message_div.className ="alert alert-danger mt-1"
                message_div.innerHTML = "Invalid format!, Please make sure the email or phone number is correct and valid format!"
                message_div.id = "invalid_search_format"
                document.getElementById('search_seat_div').appendChild(message_div)
                message_count = 1
            }
            
        }
    }

    // function render_seat_layout(seat_info){
    //     var section_array = Object.keys(seat_info) // stall
    //     // var stall_section = document.createElement('div');

    //     // stall, circle
    //     section_array.forEach((section) => {
    //         if (section === "stall"){
    //             // find the "stall" div
    //             var main_div = document.getElementById("stall")
    //         }else{
    //             // find the "circle" div
    //             var main_div = document.getElementById("circle")
    //         }
    
    //         // top, section, third
    //         var part_section_array = Object.keys(seat_info[section])
    //         part_section_array.forEach((part)=> {
    //             // console.log(part)
    //             // eg: create a "top" div
    //             var section_div = document.createElement('div');
    //             section_div.className = "d-flex flex-row-reverse justify-content-center align-items-end row mt-2"
    //             section_div.id = part
                
    //             // top right and top left
    //             var part_section_array = Object.keys(seat_info[section][part])
    //             part_section_array.forEach((side)=>{
    
    //                 var side_div = document.createElement('div');
    //                 side_div.id = side
    
    //                 if (side.includes("left")){
    //                     side_div.className = "col-6 m-1"
    //                 }else{
    //                     side_div.className = "col-5 m-1"
    //                 }
    
    //                 // the part of thirt left missing the last row, so u modify the align-items to it, it is different with other
    //                 if (side == "third_left"){
    //                     section_div.className = "d-flex flex-row-reverse justify-content-center align-items-start row mt-2"
    //                 }
    
    //                 if (section === "circle"){
    //                     side_div.className = "col-8 m-1"
    //                 }
    
    //                 // console.log(side)
                    
    //                 // 1_row, 2_row
    //                 var each_row_array = Object.keys(seat_info[section][part][side])
    //                 each_row_array.forEach((row)=>{
    //                     // console.log(row)
    //                     // console.log(seat_info[section][part][side][row])
                        
    //                     var row_div = document.createElement('div');
    //                     row_div.className = "row"
    //                     row_div.id = row;
    
    //                     if (seat_info[section][part][side][row] === "N/A"){
    //                         var empty_seat = document.createElement('br')
    //                         // empty_seat.className = "w-seat"
                            
    //                         side_div.appendChild(empty_seat);
    //                         return;
    //                     }
    
    //                     var each_row_detial = Object.keys(seat_info[section][part][side][row])
    //                     each_row_detial.forEach((detial)=>{
    //                         // console.log(detial)
    
    //                         var each_row_content = document.createElement('div');
    //                         var class_list = seat_info[section][part][side][row][detial] != "N/A" ? "col border border-secondary w-seat "+seat_info[section][part][side][row][detial] + " user-select-none": "col w-seat";
    //                         each_row_content.className = class_list;
    
    //                         if ((window.location.pathname).substring(window.location.pathname.lastIndexOf('/')+1) === "purchase.php"){
    //                             if (seat_info[section][part][side][row][detial] != "gray"){
    //                                 each_row_content.classList.add("pointer")
    //                                 each_row_content.addEventListener('click', seletedHandle)
    //                             }
    //                         }
    
    //                         var content_txt = seat_info[section][part][side][row][detial] != "N/A" ? detial : "";
    
    //                         // each_row_content.textContent = content_txt;
    
    //                         each_row_content.innerHTML = '<b>'+ content_txt +'</b>'
    
    //                         each_row_content.id = detial
    
    //                         row_div.appendChild(each_row_content)
    
    //                         // count each of the seat with color
    //                         if (seat_info[section][part][side][row][detial] != "N/A"){
    //                             color_seat[seat_info[section][part][side][row][detial]]["total_seat"] ++;
    //                         }
    //                     })
    //                     side_div.appendChild(row_div)
    //                 })
    //                 section_div.appendChild(side_div)
    //             })
    //             main_div.appendChild(section_div)
    //         })
    //     })
    // }

    function render_seat_layout(seat_info){
        var section_array = Object.keys(seat_info) // stall
        // var stall_section = document.createElement('div');
    
        // stall, circle
        section_array.forEach((section) => {
            if (section === "stall"){
                // find the "stall" div
                var main_div = document.getElementById("stall")
            }else{
                // find the "circle" div
                var main_div = document.getElementById("circle")
            }
    
            // left, middle, right
            var part_section_array = Object.keys(seat_info[section])
            part_section_array.forEach((part)=> {
                // console.log(part)
                // eg: create a "top" div
                // left 
                var section_div = document.createElement('div');
                section_div.className = "me-2"
                section_div.id = part
                
                // left_top, left_bottom
                var part_section_array = Object.keys(seat_info[section][part])
                part_section_array.forEach((side)=>{
                    // left_top
                    var side_div = document.createElement('div');
                    side_div.className = "d-flex mb-2"
                    side_div.id = side
                    
                    // colunm_1, colunm_2
                    var each_colunm_array = Object.keys(seat_info[section][part][side])
                    each_colunm_array.forEach((colunm)=>{
                        // console.log(colunm)
                        // console.log(seat_info[section][part][side][colunm])
                        
                        // colunm_1
                        var colunm_div = document.createElement('div');
                        // colunm_div.className = "w-seat"
                        colunm_div.id = colunm;
    
                        if (seat_info[section][part][side][colunm] === "N/A"){
                            var empty_seat = document.createElement('br')
                            // empty_seat.className = "w-seat"
                            
                            side_div.appendChild(empty_seat);
                            return;
                        }
    
                        var each_colunm_detial = Object.keys(seat_info[section][part][side][colunm])
                        each_colunm_detial.forEach((detial)=>{
                            // console.log(detial)
    
                            var each_colunm_content = document.createElement('div');
                            var class_list = seat_info[section][part][side][colunm][detial] != "N/A" ? "rounded border border-secondary w-seat "+seat_info[section][part][side][colunm][detial] + " text p-1 user-select-none": "w-seat";
                            each_colunm_content.className = class_list;
    
                            if ((window.location.pathname).substring(window.location.pathname.lastIndexOf('/')+1) === "purchase.php"){
                                if (seat_info[section][part][side][colunm][detial] != "gray"){
                                    each_colunm_content.classList.add("pointer")
                                    each_colunm_content.addEventListener('click', seletedHandle)
                                }
                            }
    
                            var content_txt = seat_info[section][part][side][colunm][detial] != "N/A" ? detial : "";
    
                            // each_colunm_content.textContent = content_txt;
    
                            each_colunm_content.innerHTML = '<b>'+ content_txt +'</b>'
    
                            each_colunm_content.id = detial

                            if(seat_info[section][part][side][colunm][detial] != "N/A"){
                                // Set tooltip data attributes
                                each_colunm_content.setAttribute('data-bs-toggle', 'tooltip');
                                each_colunm_content.setAttribute('data-bs-placement', 'top'); // or your preferred placement
                                each_colunm_content.setAttribute('data-bs-title', detial);

                                // Initialize the tooltip
                                new bootstrap.Tooltip(each_colunm_content);
                            }
                            
                            colunm_div.appendChild(each_colunm_content)
    
                            // count each of the seat with color
                            if (seat_info[section][part][side][colunm][detial] != "N/A"){
                                color_seat[seat_info[section][part][side][colunm][detial]]["total_seat"] ++;
                            }
                        })
                        side_div.appendChild(colunm_div)
                    })
                    section_div.appendChild(side_div)
                })
                main_div.appendChild(section_div)
            })
        })
    }

    function update_seat_price(seat_price){
        var color_array = Object.keys(seat_price)

        color_array.forEach((color)=>{
            color_seat[color]["price"] = seat_price[color];
        })

        var color_seat_array = Object.keys(color_seat);
        color_seat_array.forEach((color) => {
            var each_color_row = document.getElementById(color+"_row");
            each_color_row.innerHTML = "";

            var each_color_td_price = document.createElement("td");
            // console.log(color_seat[color])
            // console.log(color_seat[color]["price"])

            if (color_seat[color]["price"] != "N/A"){
                each_color_td_price.textContent = "RM " + color_seat[color]["price"]
            }else{
                each_color_td_price.textContent = color_seat[color]["price"]
            }

            var each_color_td_balance = document.createElement("td");
            each_color_td_balance.textContent = color_seat[color]["selled_seat"] + " / " + color_seat[color]["total_seat"]
            console.log("afa")

            each_color_row.appendChild(each_color_td_price)
            each_color_row.appendChild(each_color_td_balance)
        })
    }

    function reset_seat(){
        var elements = document.getElementsByClassName("seat_bought");
        var ids = [];
        
        for (var i = 0; i < elements.length; i++) {
            ids.push(elements[i].id);
            // why not remove "seat_bought" and add orginal class?
            // because the elements will always keeps the latest result, 
            // which mean after the index '0' classname be remove the follow element will become index '0' immediatelly
            // it suppose should be index '1'
        }

        ids.forEach((id)=>{
            document.getElementById(id).classList.add("border-secondary", "border", "opacity-50")
            document.getElementById(id).classList.remove("seat_bought")
            document.getElementById(id).innerHTML = '<i class="fa-regular fa-user"></i>'
        })
    }

    function seletedHandle(){
        // find whether the seat has been selected, if yes them return the item, not return undefind
        var item = selected_seat.find((element)=>{
            return element.id === this.id
        })
        console.log(item)

        // if no item, them change the div to select mode
        if (!item){
            selected_seat.push({"id":this.id, "color":this.classList[4]})
            this.className = "rounded border border-light w-seat bg-secondary user-select-none pointer p-1";
        }// if has item, them change to previous mode
        else{
            this.className = "rounded border border-secondary w-seat "+item.color+" user-select-none pointer p-1";
            selected_seat = selected_seat.filter((element)=>{
                return element.id !== this.id
            })
        }

        // console.log(this.className)
        update_purchase_summary()
    }

    function update_purchase_summary(){
        // console.log(selected_seat)
        // console.log(selected_seat.length)
        if (selected_seat.length > 0){
            var seat = ""
            var amount = 0
            selected_seat.forEach((item, index)=>{
                if (index != 0){
                    seat += ", "
                }
                seat += item.id
                amount += color_seat[item.color]["price"]

            })
            document.getElementById("selected_seat_detial").innerHTML = seat
            document.getElementById("selected_amount").innerHTML = "RM "+ amount
            document.getElementById("check_out_btn").disabled = false
        }else{
            document.getElementById("selected_seat_detial").innerHTML = "-"
            document.getElementById("selected_amount").innerHTML = "RM "+ "-"
            document.getElementById("check_out_btn").disabled= true
        }
    }
})