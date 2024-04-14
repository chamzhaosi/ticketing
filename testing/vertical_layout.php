<!DOCTYPE html>
<head>
    <title>
        Vertical Layout
    </title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <link rel="stylesheet" type="text/css" href="./statics/css/concert.css"> -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
</head>

<body class="container">
    
<!--Seat Layout-->
<!-- <div class="d-flex flex-row-reverse row justify-content-center m-0">
    <div class="bg-secondary col-1 text-center section_title">
        <h3>Stage</h3>
    </div> -->
    <!--Stall Layout-->
    <!-- <div class="col-8 text-center bg-primary-subtle d-flex row border-start border-secondary">
        <div id="stall" class="col-11"> 

        </div>
        <div class="col-1 section_title h3">Stall</div>
    </div> -->
    <!--Circle Layout-->
    <!-- <div class="bg-primary-subtle col text-center d-flex row m-0">
        <div id="circle" class="col-11"> 

        </div>
        <div class="col-1 section_title h3">Circle</div>
    </div>
</div> -->

    <style>
        .w-seat{
            width: 33px !important;
            height: 30px !important;
            font-size: 0.6em;
            font-weight: 800;
            text-align: center;
            /* padding: 10px; */
            /* box-sizing: border-box; */
        }

        .blue{
            background-color: #26a7e9;
        }

        .yellow{
            background-color: #f7fa0b;
        }

        .gray{
            background-color: #b9b2b9;
        }

        .red{
            background-color: #f3030e;
        }

        .orange{
            background-color: #f39801;
        }

        .green{
            background-color: #1cad50;
        }
    </style>

    <!-- Seat Layout -->
    <div class="d-flex  row justify-content-center m-0">
        <!-- Stage Layout -->
        <div class="text-center bg-secondary text-light">
            <h3>Stage</h3>
        </div>

        <!-- Stall Layout -->
        <div>
            <h3 class="text-center my-2">Stall</h3>
            
            <div id="stall" class="d-flex justify-content-center">
                
            </div>

        </div>

        <!-- Circle Layout -->
        <div class="border-top">
            <h3 class="text-center my-2">Circle</h3>
            
            <div id="circle" class="d-flex justify-content-center">
                
            </div>

        </div>

    </div>



    <script src="./statics/js/vertical.js"></script>
    <script src="https://kit.fontawesome.com/13427233db.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>

</html>