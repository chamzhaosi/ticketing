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

document.addEventListener('DOMContentLoaded', function(){
    
    var selected_seat = new Array()
    
    async function loadData() {
        try {
            // First fetch request for seat layout
            const responseLayout = await fetch('./statics/json/seat_info_v.json');
            if (!responseLayout.ok) {
                throw new Error(`Network response was not ok ${responseLayout.statusText}`);
            }
            const seatLayoutData = await responseLayout.json();
            render_seat_layout(seatLayoutData);
    
        } catch (error) {
            console.error('There has been a problem with your fetch operation:', error);
        } finally {
            // Updating UI after both fetch requests are completed
            // document.getElementById('loadingContainer').classList.remove("d-block");
            // document.getElementById('loadingContainer').classList.add("d-none");
            // document.getElementById('contentContainer').classList.remove("d-none");
            // document.getElementById('contentContainer').classList.add("d-block");
        }
    }
    
    loadData();
})

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